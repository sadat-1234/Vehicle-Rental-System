<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireCustomer();

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT b.*, v.brand, v.model, v.registration_no, vc.category_name, vc.price_per_day,
         d.name AS driver_name, d.driver_license_no,
         p.payment_status, p.amount AS pay_amount, p.payment_method, p.payment_date,
         (SELECT SUM(pen.amount) FROM Penalty pen WHERE pen.booking_id=b.booking_id) AS penalties
  FROM Booking b
  JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
  JOIN Vehicle_category vc ON v.category_id=vc.category_id
  LEFT JOIN Driver d ON b.driver_id=d.driver_id
  LEFT JOIN Payment p ON b.booking_id=p.booking_id
  WHERE b.customer_user_id=?
  ORDER BY b.booking_id DESC
");
$stmt->execute([$uid]);
$bookings = $stmt->fetchAll();

function sb($s){$m=['Confirmed'=>'badge-success','Pending'=>'badge-warning','Completed'=>'badge-info','Cancelled'=>'badge-danger'];return '<span class="badge '.($m[$s]??'badge-neutral').'">'.htmlspecialchars($s).'</span>';}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">CUSTOMER PORTAL</span></div></nav>
<?php include '../includes/customer_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <div class="page-header">
    <div class="header-row">
      <div><h2>My Bookings</h2><p>Your complete rental history and current reservations.</p></div>
      <a href="search.php" class="btn btn-primary">+ New Booking</a>
    </div>
  </div>

  <?php if(empty($bookings)): ?>
  <div class="empty-state card">
    <span class="empty-icon">📋</span>
    <p>No bookings yet. <a href="search.php" style="color:var(--accent)">Search for a vehicle</a> to get started!</p>
  </div>
  <?php else: ?>

  <?php foreach($bookings as $b):
    $days = max(1,(strtotime($b['end_date'])-strtotime($b['start_date']))/86400);
    $total = $days * $b['price_per_day'];
  ?>
  <div class="card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
      <div>
        <span class="mono" style="font-size:.75rem;color:var(--text3)">BOOKING #<?= $b['booking_id'] ?></span>
        <h3 style="margin-top:4px;"><?= htmlspecialchars($b['brand'].' '.$b['model']) ?></h3>
        <span class="mono" style="font-size:.78rem;color:var(--text3)"><?= htmlspecialchars($b['registration_no']) ?> · <?= htmlspecialchars($b['category_name']) ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?= sb($b['status']) ?>
        <?php if($b['payment_status']): ?>
        <?php $pm=['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger']; ?>
        <span class="badge <?= $pm[$b['payment_status']]??'badge-neutral' ?>"><?= htmlspecialchars($b['payment_status']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-grid" style="margin-bottom:16px;">
      <div class="detail-item"><div class="detail-key">Start Date</div><div class="detail-val"><?= $b['start_date'] ?></div></div>
      <div class="detail-item"><div class="detail-key">End Date</div><div class="detail-val"><?= $b['end_date'] ?></div></div>
      <div class="detail-item"><div class="detail-key">Pickup</div><div class="detail-val"><?= $b['pickup_date'] ?> at <?= date('g:i A', strtotime($b['pickup_time'])) ?></div></div>
      <div class="detail-item"><div class="detail-key">Duration</div><div class="detail-val"><?= $days ?> day<?= $days>1?'s':'' ?></div></div>
      <div class="detail-item"><div class="detail-key">Driver</div><div class="detail-val"><?= $b['driver_name'] ? htmlspecialchars($b['driver_name']) : '—' ?></div></div>
      <div class="detail-item"><div class="detail-key">Booked On</div><div class="detail-val"><?= $b['booking_date'] ?></div></div>
    </div>

    <div style="border-top:1px solid var(--border);padding-top:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
      <div>
        <span style="font-size:.8rem;color:var(--text3);">Rental: ৳<?= number_format($b['price_per_day']) ?>/day × <?= $days ?> days</span>
        <?php if($b['penalties']): ?>
        <span style="margin-left:16px;font-size:.8rem;color:var(--danger);">Penalty: ৳<?= number_format($b['penalties']) ?></span>
        <?php endif; ?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:.78rem;color:var(--text3);">TOTAL DUE</div>
        <div style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;color:var(--accent);">৳<?= number_format($total + ($b['penalties']??0)) ?></div>
      </div>
    </div>

    <?php if($b['payment_status']==='Pending'): ?>
    <div style="margin-top:12px;">
      <a href="payments.php" class="btn btn-warning btn-sm">💳 Pay Now</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div></div>
</body>
</html>
