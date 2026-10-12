<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$customers = $pdo->query("
  SELECT u.user_id, u.first_name, u.last_name, u.email,
         c.license_no, c.house_no, c.street, c.city,
         GROUP_CONCAT(up.phone SEPARATOR ', ') AS phones,
         COUNT(DISTINCT b.booking_id) AS total_bookings,
         SUM(CASE WHEN p.payment_status='Pending' THEN p.amount ELSE 0 END) AS pending_amount
  FROM Customer c
  JOIN User u ON c.user_id=u.user_id
  LEFT JOIN User_phone up ON u.user_id=up.user_id
  LEFT JOIN Booking b ON b.customer_user_id=c.user_id
  LEFT JOIN Payment p ON p.booking_id=b.booking_id
  GROUP BY u.user_id ORDER BY u.user_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers — DriveX Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">ADMIN PANEL</span></div></nav>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <div class="page-header">
    <h2>Customers</h2>
    <p>View all registered customers, their bookings and outstanding payments.</p>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>License</th><th>Address</th><th>Bookings</th><th>Pending Due</th></tr></thead>
        <tbody>
        <?php foreach($customers as $c): ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $c['user_id'] ?></td>
          <td><strong><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></strong></td>
          <td><?= htmlspecialchars($c['email']) ?><br><span class="mono" style="font-size:.75rem;color:var(--text3)"><?= htmlspecialchars($c['phones']??'—') ?></span></td>
          <td class="mono" style="font-size:.82rem;"><?= htmlspecialchars($c['license_no']) ?></td>
          <td style="font-size:.83rem;"><?= htmlspecialchars(implode(', ', array_filter([$c['house_no'], $c['street'], $c['city']]))) ?></td>
          <td><span class="badge badge-info"><?= $c['total_bookings'] ?> bookings</span></td>
          <td><?= $c['pending_amount'] > 0 ? '<span class="badge badge-danger">৳'.number_format($c['pending_amount']).'</span>' : '<span class="badge badge-success">Clear</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
</body>
</html>
