<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireCustomer();

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// SUBMIT PAYMENT
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='pay') {
    $pid = intval($_POST['payment_id']);
    $method = sanitize($_POST['method']);
    $pdo->prepare("UPDATE Payment SET payment_status='Paid', payment_date=CURDATE(), payment_method=? WHERE payment_id=?")->execute([$method, $pid]);
    $msg = 'Payment submitted successfully!';
}

$stmt = $pdo->prepare("
  SELECT p.*, b.start_date, b.end_date, b.booking_id,
         v.brand, v.model, vc.price_per_day,
         (SELECT SUM(pen.amount) FROM Penalty pen WHERE pen.booking_id=b.booking_id) AS penalties
  FROM Payment p
  JOIN Booking b ON p.booking_id=b.booking_id
  JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
  JOIN Vehicle_category vc ON v.category_id=vc.category_id
  WHERE b.customer_user_id=?
  ORDER BY p.payment_id DESC
");
$stmt->execute([$uid]);
$payments = $stmt->fetchAll();

$totalDue = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM Payment p JOIN Booking b ON p.booking_id=b.booking_id WHERE b.customer_user_id=? AND p.payment_status='Pending'");
$totalDue->execute([$uid]); $totalDue = $totalDue->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">CUSTOMER PORTAL</span></div></nav>
<?php include '../includes/customer_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger">⚠️ <?= $err ?></div><?php endif; ?>

  <div class="page-header"><h2>Payments</h2><p>View and manage all your rental payments.</p></div>

  <?php if($totalDue > 0): ?>
  <div class="alert alert-warning">⚠️ You have <strong>৳<?= number_format($totalDue) ?></strong> in outstanding payments. Unpaid bills may attract penalty charges.</div>
  <?php endif; ?>

  <?php if(empty($payments)): ?>
  <div class="empty-state card"><span class="empty-icon">💳</span><p>No payment records yet.</p></div>
  <?php else: ?>

  <?php foreach($payments as $p):
    $days = max(1,(strtotime($p['end_date'])-strtotime($p['start_date']))/86400);
  ?>
  <div class="card" style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
      <div>
        <div class="mono" style="font-size:.72rem;color:var(--text3);">PAYMENT #<?= $p['payment_id'] ?> · BOOKING #<?= $p['booking_id'] ?></div>
        <h4 style="margin:6px 0;"><?= htmlspecialchars($p['brand'].' '.$p['model']) ?></h4>
        <div style="font-size:.83rem;color:var(--text2);"><?= $p['start_date'] ?> → <?= $p['end_date'] ?> (<?= $days ?> day<?= $days>1?'s':'' ?>)</div>
      </div>
      <div style="text-align:right;">
        <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:var(--accent);">৳<?= number_format($p['amount'] + ($p['penalties']??0)) ?></div>
        <?php if($p['penalties']): ?>
        <div style="font-size:.78rem;color:var(--danger);">inc. ৳<?= number_format($p['penalties']) ?> penalty</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="divider"></div>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <?php $pm=['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger']; ?>
        <div><div class="detail-key">Status</div><div style="margin-top:4px;"><span class="badge <?= $pm[$p['payment_status']]??'badge-neutral' ?>"><?= htmlspecialchars($p['payment_status']) ?></span></div></div>
        <?php if($p['payment_method']): ?><div><div class="detail-key">Method</div><div class="detail-val" style="margin-top:4px;"><?= htmlspecialchars($p['payment_method']) ?></div></div><?php endif; ?>
        <?php if($p['payment_date'] && $p['payment_status']==='Paid'): ?><div><div class="detail-key">Paid On</div><div class="detail-val" style="margin-top:4px;"><?= $p['payment_date'] ?></div></div><?php endif; ?>
      </div>

      <?php if($p['payment_status']==='Pending'): ?>
      <button class="btn btn-primary" onclick="openPayModal(<?= $p['payment_id'] ?>, <?= $p['amount'] + ($p['penalties']??0) ?>)">Pay Now →</button>
      <?php else: ?>
      <span class="badge badge-success">✓ Paid</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div></div>

<!-- PAY MODAL -->
<div class="modal-overlay" id="payModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Make Payment</div><button class="modal-close" onclick="document.getElementById('payModal').style.display='none'">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="pay">
        <input type="hidden" name="payment_id" id="modalPayId">
        <div style="text-align:center;padding:20px 0;border-bottom:1px solid var(--border);margin-bottom:20px;">
          <div style="font-size:.75rem;color:var(--text3);font-family:'Space Mono',monospace;">AMOUNT DUE</div>
          <div style="font-family:'Bebas Neue',sans-serif;font-size:3rem;color:var(--accent);" id="modalAmount">৳0</div>
        </div>
        <div class="form-group">
          <label class="form-label">Select Payment Method</label>
          <select name="method" class="form-control" required>
            <option value="bKash">📱 bKash</option>
            <option value="Nagad">📱 Nagad</option>
            <option value="Cash">💵 Cash</option>
            <option value="Card">💳 Debit/Credit Card</option>
            <option value="Bank Transfer">🏦 Bank Transfer</option>
          </select>
        </div>
        <div class="alert alert-info" style="font-size:.82rem;">After selecting the method, confirm to mark this payment as submitted. Admin will verify the transaction.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('payModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Confirm Payment →</button>
      </div>
    </form>
  </div>
</div>
<script>
function openPayModal(id, amount) {
  document.getElementById('modalPayId').value = id;
  document.getElementById('modalAmount').textContent = '৳' + amount.toLocaleString('en-IN');
  document.getElementById('payModal').style.display = 'flex';
}
</script>
</body>
</html>
