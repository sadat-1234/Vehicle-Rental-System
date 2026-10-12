<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireCustomer();

$uid = $_SESSION['user_id'];

$totalBookings  = $pdo->prepare("SELECT COUNT(*) FROM Booking WHERE customer_user_id=?"); $totalBookings->execute([$uid]); $totalBookings = $totalBookings->fetchColumn();
$activeBookings = $pdo->prepare("SELECT COUNT(*) FROM Booking WHERE customer_user_id=? AND status IN ('Confirmed','Pending')"); $activeBookings->execute([$uid]); $activeBookings = $activeBookings->fetchColumn();
$pendingPay     = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM Payment p JOIN Booking b ON p.booking_id=b.booking_id WHERE b.customer_user_id=? AND p.payment_status='Pending'"); $pendingPay->execute([$uid]); $pendingPay = $pendingPay->fetchColumn();
$totalPenalties = $pdo->prepare("SELECT COALESCE(SUM(pen.amount),0) FROM Penalty pen JOIN Booking b ON pen.booking_id=b.booking_id WHERE b.customer_user_id=?"); $totalPenalties->execute([$uid]); $totalPenalties = $totalPenalties->fetchColumn();

// Recent bookings
$stmt = $pdo->prepare("
  SELECT b.*, v.brand, v.model, v.registration_no, vc.category_name, vc.price_per_day,
         d.name AS driver_name, p.payment_status, p.amount AS pay_amount
  FROM Booking b
  JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
  JOIN Vehicle_category vc ON v.category_id=vc.category_id
  LEFT JOIN Driver d ON b.driver_id=d.driver_id
  LEFT JOIN Payment p ON b.booking_id=p.booking_id
  WHERE b.customer_user_id=?
  ORDER BY b.booking_id DESC LIMIT 5
");
$stmt->execute([$uid]);
$recentBookings = $stmt->fetchAll();

function sb($s){$m=['Confirmed'=>'badge-success','Pending'=>'badge-warning','Completed'=>'badge-info','Cancelled'=>'badge-danger'];return '<span class="badge '.($m[$s]??'badge-neutral').'">'.htmlspecialchars($s).'</span>';}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">CUSTOMER PORTAL</span></div></nav>
<?php include '../includes/customer_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <div class="page-header">
    <div class="header-row">
      <div><h2>My Dashboard</h2><p>Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>! Ready to hit the road?</p></div>
      <a href="search.php" class="btn btn-primary">🔍 Find a Vehicle</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total Bookings</div><div class="stat-value"><?= $totalBookings ?></div><div class="stat-icon">📋</div></div>
    <div class="stat-card blue"><div class="stat-label">Active Bookings</div><div class="stat-value"><?= $activeBookings ?></div><div class="stat-icon">🚗</div></div>
    <div class="stat-card orange"><div class="stat-label">Payment Due</div><div class="stat-value" style="font-size:1.5rem;">৳<?= number_format($pendingPay) ?></div><div class="stat-icon">💳</div></div>
    <div class="stat-card red"><div class="stat-label">Penalties</div><div class="stat-value" style="font-size:1.5rem;">৳<?= number_format($totalPenalties) ?></div><div class="stat-icon">⚠️</div></div>
  </div>

  <?php if($pendingPay > 0): ?>
  <div class="alert alert-warning">⚠️ You have <strong>৳<?= number_format($pendingPay) ?></strong> in pending payments. <a href="payments.php" style="color:inherit;font-weight:700;text-decoration:underline;">Pay now →</a></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Bookings</div>
      <a href="bookings.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <?php if(empty($recentBookings)): ?>
    <div class="empty-state">
      <span class="empty-icon">🚗</span>
      <p>No bookings yet. <a href="search.php" style="color:var(--accent)">Search for a vehicle</a> to get started!</p>
    </div>
    <?php else: ?>
    <div class="table-wrapper" style="border:none;">
      <table>
        <thead><tr><th>#</th><th>Vehicle</th><th>Dates</th><th>Driver</th><th>Amount</th><th>Status</th><th>Payment</th></tr></thead>
        <tbody>
        <?php foreach($recentBookings as $b):
          $days = max(1,(strtotime($b['end_date'])-strtotime($b['start_date']))/86400);
          $total = $days * $b['price_per_day'];
        ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $b['booking_id'] ?></td>
          <td><strong><?= htmlspecialchars($b['brand'].' '.$b['model']) ?></strong><br><span class="mono" style="font-size:.72rem;color:var(--text3)"><?= htmlspecialchars($b['registration_no']) ?></span></td>
          <td><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
          <td><?= $b['driver_name'] ? htmlspecialchars($b['driver_name']) : '<span class="text-muted">None</span>' ?></td>
          <td>৳<?= number_format($total) ?></td>
          <td><?= sb($b['status']) ?></td>
          <td><?php
            $pm=['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger'];
            echo $b['payment_status'] ? '<span class="badge '.($pm[$b['payment_status']]??'badge-neutral').'">'.htmlspecialchars($b['payment_status']).'</span>' : '<span class="text-muted">—</span>';
          ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div></div>
</body>
</html>
