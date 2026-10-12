<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $err = '';

// ADD PENALTY
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    try {
        $bid = intval($_POST['booking_id']);
        $nextId = $pdo->prepare("SELECT COALESCE(MAX(penalty_id),0)+1 FROM Penalty WHERE booking_id=?");
        $nextId->execute([$bid]); $nid = $nextId->fetchColumn();
        $pdo->prepare("INSERT INTO Penalty (penalty_id, booking_id, penalty_date, amount, reason) VALUES (?,?,?,?,?)")
            ->execute([$nid, $bid, $_POST['penalty_date'], $_POST['amount'], sanitize($_POST['reason'])]);
        $msg = 'Penalty added.';
    } catch (Exception $e) { $err = $e->getMessage(); }
}

$penalties = $pdo->query("
  SELECT pen.*, b.start_date, b.end_date, u.first_name, u.last_name, v.brand, v.model
  FROM Penalty pen
  JOIN Booking b ON pen.booking_id=b.booking_id
  JOIN User u ON b.customer_user_id=u.user_id
  JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
  ORDER BY pen.penalty_date DESC
")->fetchAll();

// For modal: bookings list
$bookingsList = $pdo->query("
  SELECT b.booking_id, u.first_name, u.last_name, v.brand, v.model
  FROM Booking b JOIN User u ON b.customer_user_id=u.user_id JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
  ORDER BY b.booking_id DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Penalties — DriveX Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">ADMIN PANEL</span></div></nav>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-content"><div class="content-area">
  <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger">⚠️ <?= $err ?></div><?php endif; ?>

  <div class="page-header">
    <div class="header-row">
      <div><h2>Penalties</h2><p>Manage late return fines and damage charges.</p></div>
      <button class="btn btn-primary" onclick="document.getElementById('addPenModal').style.display='flex'">+ Add Penalty</button>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Booking #</th><th>Customer</th><th>Vehicle</th><th>Date</th><th>Amount</th><th>Reason</th></tr></thead>
        <tbody>
        <?php foreach($penalties as $p): ?>
        <tr>
          <td class="mono">#<?= $p['booking_id'] ?></td>
          <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
          <td><?= htmlspecialchars($p['brand'].' '.$p['model']) ?></td>
          <td><?= $p['penalty_date'] ?></td>
          <td><strong class="text-danger">৳<?= number_format($p['amount']) ?></strong></td>
          <td><?= htmlspecialchars($p['reason']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($penalties)): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text3)">No penalties recorded.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>

<!-- ADD PENALTY MODAL -->
<div class="modal-overlay" id="addPenModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Add Penalty</div><button class="modal-close" onclick="document.getElementById('addPenModal').style.display='none'">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Select Booking</label>
          <select name="booking_id" class="form-control" required>
            <option value="">Choose booking...</option>
            <?php foreach($bookingsList as $bl): ?>
            <option value="<?= $bl['booking_id'] ?>">#<?= $bl['booking_id'] ?> — <?= htmlspecialchars($bl['first_name'].' '.$bl['last_name']) ?> (<?= htmlspecialchars($bl['brand'].' '.$bl['model']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Penalty Date</label>
            <input type="date" name="penalty_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (৳)</label>
            <input type="number" name="amount" class="form-control" min="0" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason</label>
          <input type="text" name="reason" class="form-control" placeholder="Late return / Damage / etc." required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addPenModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-danger">Add Penalty</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
