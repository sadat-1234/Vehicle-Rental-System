<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = '';

// Mark as paid
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='mark_paid') {
    $pdo->prepare("UPDATE Payment SET payment_status='Paid', payment_date=CURDATE(), payment_method=? WHERE payment_id=?")
        ->execute([sanitize($_POST['method']), $_POST['payment_id']]);
    $msg = 'Payment marked as Paid.';
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT p.*, b.start_date, b.end_date, b.booking_id,
               u.first_name, u.last_name, u.email,
               v.brand, v.model, v.registration_no,
               vc.price_per_day,
               (SELECT SUM(pen.amount) FROM Penalty pen WHERE pen.booking_id=b.booking_id) AS penalty_total
        FROM Payment p
        JOIN Booking b ON p.booking_id=b.booking_id
        JOIN User u ON b.customer_user_id=u.user_id
        JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
        JOIN Vehicle_category vc ON v.category_id=vc.category_id";
$params = [];
if ($filter) { $sql .= " WHERE p.payment_status=?"; $params[] = $filter; }
$sql .= " ORDER BY p.payment_id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$payments = $stmt->fetchAll();

$pending = $pdo->query("SELECT COUNT(*) FROM Payment WHERE payment_status='Pending'")->fetchColumn();
$totalPaid = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE payment_status='Paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — DriveX Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">ADMIN PANEL</span></div></nav>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

  <div class="page-header"><h2>Payments</h2><p>Track all payments and outstanding balances.</p></div>

  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
    <div class="stat-card orange"><div class="stat-label">Pending Payments</div><div class="stat-value"><?= $pending ?></div></div>
    <div class="stat-card green"><div class="stat-label">Revenue Collected</div><div class="stat-value" style="font-size:1.5rem;">৳<?= number_format($totalPaid) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Records</div><div class="stat-value"><?= count($payments) ?></div></div>
  </div>

  <div class="search-section">
    <form method="GET" class="search-row">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          <?php foreach(['Pending','Paid','Overdue'] as $s): ?>
          <option value="<?=$s?>" <?= $filter===$s?'selected':'' ?>><?=$s?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button class="btn btn-secondary">Filter</button>
        <a href="payments.php" class="btn btn-secondary" style="margin-left:8px;">Reset</a>
      </div>
    </form>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Pay #</th><th>Customer</th><th>Vehicle</th><th>Booking</th><th>Amount</th><th>Penalty</th><th>Method</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($payments as $p): ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $p['payment_id'] ?></td>
          <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong><br><span style="font-size:.75rem;color:var(--text3)"><?= htmlspecialchars($p['email']) ?></span></td>
          <td><?= htmlspecialchars($p['brand'].' '.$p['model']) ?></td>
          <td class="mono" style="font-size:.8rem;">#<?= $p['booking_id'] ?><br><?= $p['start_date'] ?> → <?= $p['end_date'] ?></td>
          <td><strong>৳<?= number_format($p['amount']) ?></strong></td>
          <td><?= $p['penalty_total'] > 0 ? '<span class="text-danger">৳'.number_format($p['penalty_total']).'</span>' : '<span class="text-muted">—</span>' ?></td>
          <td><?= $p['payment_method'] ? htmlspecialchars($p['payment_method']) : '<span class="text-muted">—</span>' ?></td>
          <td><?php
            $pm = ['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger'];
            echo '<span class="badge '.($pm[$p['payment_status']]??'badge-neutral').'">'.htmlspecialchars($p['payment_status']).'</span>';
          ?></td>
          <td>
            <?php if($p['payment_status']==='Pending'): ?>
            <button class="btn btn-success btn-sm" onclick="openPayModal(<?= $p['payment_id'] ?>)">Mark Paid</button>
            <?php else: ?><span class="text-muted" style="font-size:.8rem;"><?= $p['payment_date'] ?></span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>

<!-- Mark Paid Modal -->
<div class="modal-overlay" id="payModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Mark Payment as Paid</div><button class="modal-close" onclick="document.getElementById('payModal').style.display='none'">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="mark_paid">
        <input type="hidden" name="payment_id" id="payModalId">
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select name="method" class="form-control" required>
            <option value="Cash">Cash</option>
            <option value="bKash">bKash</option>
            <option value="Nagad">Nagad</option>
            <option value="Card">Card</option>
            <option value="Bank Transfer">Bank Transfer</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('payModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-success">Confirm Payment</button>
      </div>
    </form>
  </div>
</div>
<script>
function openPayModal(id) {
  document.getElementById('payModalId').value = id;
  document.getElementById('payModal').style.display = 'flex';
}
</script>
</body>
</html>
