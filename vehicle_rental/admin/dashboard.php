<?php
require_once '../includes/auth.php'; // Ensure this matches your actual filename
require_once '../includes/db.php';

// Check if user is logged in as an admin
requireAdmin(); 

$msg = ''; 
$err = '';

// Data Fetching Logic[cite: 46]
$totalVehicles  = $pdo->query("SELECT COUNT(*) FROM Vehicle")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM Customer")->fetchColumn();
$totalBookings  = $pdo->query("SELECT COUNT(*) FROM Booking")->fetchColumn();
$pendingPayments= $pdo->query("SELECT COUNT(*) FROM Payment WHERE payment_status='Pending'")->fetchColumn();
$pendingBookings= $pdo->query("SELECT COUNT(*) FROM Booking WHERE status='Pending'")->fetchColumn();
$maintenance    = $pdo->query("SELECT COUNT(*) FROM Maintenance WHERE next_service_date >= CURDATE()")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Payment WHERE payment_status='Paid'")->fetchColumn();
$totalPenalties = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM Penalty")->fetchColumn();

// Recent bookings[cite: 46]
$recentBookings = $pdo->query("
  SELECT b.*, u.first_name, u.last_name, v.brand, v.model, v.registration_no, vc.category_name,
         p.payment_status, p.amount AS payment_amount
  FROM Booking b
  JOIN User u ON b.customer_user_id = u.user_id
  JOIN Vehicle v ON b.vehicle_id = v.vehicle_id
  JOIN Vehicle_category vc ON v.category_id = vc.category_id
  LEFT JOIN Payment p ON b.booking_id = p.booking_id
  ORDER BY b.booking_date DESC LIMIT 8
")->fetchAll();

// Upcoming maintenance[cite: 46]
$upcomingMaint = $pdo->query("
  SELECT m.*, v.brand, v.model, v.registration_no
  FROM Maintenance m JOIN Vehicle v ON m.vehicle_id = v.vehicle_id
  ORDER BY m.next_service_date ASC LIMIT 5
")->fetchAll();

// Badge formatting functions[cite: 46]
function statusBadge($s) {
    $map = ['Confirmed'=>'badge-success','Pending'=>'badge-warning','Completed'=>'badge-info','Cancelled'=>'badge-danger'];
    return '<span class="badge '.($map[$s]??'badge-neutral').'">'.htmlspecialchars($s).'</span>';
}
function payBadge($s) {
    $map = ['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger'];
    return '<span class="badge '.($map[$s]??'badge-neutral').'">'.($s??'—').'</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="container">
    <a href="#" class="logo">Drive<span>X</span></a>
    <span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">ADMIN PANEL</span>
  </div>
</nav>

<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content">
<div class="content-area">

  <div class="page-header">
    <div class="header-row">
      <div><h2>Dashboard</h2><p>Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>. Here's your fleet overview.</p></div>
      <span class="badge badge-success">● System Online</span>
    </div>
  </div>

  <!-- STATS GRID[cite: 46] -->
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total Vehicles</div><div class="stat-value"><?= $totalVehicles ?></div><div class="stat-icon">🚗</div></div>
    <div class="stat-card blue"><div class="stat-label">Total Customers</div><div class="stat-value"><?= $totalCustomers ?></div><div class="stat-icon">👥</div></div>
    <div class="stat-card green"><div class="stat-label">Total Bookings</div><div class="stat-value"><?= $totalBookings ?></div><div class="stat-icon">📋</div></div>
    <div class="stat-card orange"><div class="stat-label">Pending Payments</div><div class="stat-value"><?= $pendingPayments ?></div><div class="stat-icon">💳</div></div>
    <div class="stat-card"><div class="stat-label">Revenue Collected</div><div class="stat-value" style="font-size:1.6rem;">৳<?= number_format($totalRevenue) ?></div><div class="stat-icon">💰</div></div>
    <div class="stat-card red"><div class="stat-label">Total Penalties</div><div class="stat-value" style="font-size:1.6rem;">৳<?= number_format($totalPenalties) ?></div><div class="stat-icon">⚠️</div></div>
  </div>

  <!-- QUICK ACTIONS[cite: 46] -->
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px;">
    <a href="vehicles.php" class="btn btn-secondary">🚗 Manage Vehicles</a>
    <a href="bookings.php" class="btn btn-secondary">📋 View Bookings</a>
    <a href="drivers.php" class="btn btn-secondary">👨‍💼 Manage Drivers</a>
    <a href="maintenance.php" class="btn btn-secondary">🔧 Maintenance</a>
    <a href="payments.php" class="btn btn-primary">💳 Pending Payments (<?= $pendingPayments ?>)</a>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">

    <!-- RECENT BOOKINGS[cite: 46] -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Recent Bookings</div>
        <a href="bookings.php" class="btn btn-secondary btn-sm">View All</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Status</th><th>Payment</th></tr></thead>
          <tbody>
          <?php foreach ($recentBookings as $b): ?>
          <tr>
            <td><strong><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></strong></td>
            <td><?= htmlspecialchars($b['brand'].' '.$b['model']) ?><br><span class="mono" style="font-size:.7rem;color:var(--text3)"><?= htmlspecialchars($b['registration_no']) ?></span></td>
            <td><?= $b['start_date'] ?> → <?= $b['end_date'] ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td><?= payBadge($b['payment_status']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- UPCOMING MAINTENANCE[cite: 46] -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Maintenance Due</div>
        <a href="maintenance.php" class="btn btn-secondary btn-sm">View All</a>
      </div>
      <?php foreach ($upcomingMaint as $m): ?>
      <div style="padding:12px 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:start;">
          <div>
            <strong style="font-size:.88rem;"><?= htmlspecialchars($m['brand'].' '.$m['model']) ?></strong>
            <div class="mono" style="font-size:.7rem;color:var(--text3)"><?= htmlspecialchars($m['registration_no']) ?></div>
          </div>
          <span class="badge <?= strtotime($m['next_service_date']) <= strtotime('+7 days') ? 'badge-danger' : 'badge-warning' ?>"><?= $m['next_service_date'] ?></span>
        </div>
        <div style="font-size:.8rem;color:var(--text3);margin-top:4px;"><?= htmlspecialchars($m['description']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($upcomingMaint)): ?>
      <div class="empty-state"><span class="empty-icon">✅</span><p>No maintenance scheduled</p></div>
      <?php endif; ?>
    </div>
  </div>

</div>
</div>
</body>
</html>