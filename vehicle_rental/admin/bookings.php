<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $err = '';

// UPDATE STATUS
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update_status') {
    $pdo->prepare("UPDATE Booking SET status=? WHERE booking_id=?")
        ->execute([sanitize($_POST['status']), $_POST['booking_id']]);
    $msg = 'Booking status updated.';
}

// FILTER
$statusF = $_GET['status'] ?? '';
$sql = "SELECT b.*, u.first_name, u.last_name, v.brand, v.model, v.registration_no,
               vc.category_name, vc.price_per_day,
               d.name AS driver_name,
               p.payment_status, p.amount AS paid_amount, p.payment_method
        FROM Booking b
        JOIN User u ON b.customer_user_id=u.user_id
        JOIN Vehicle v ON b.vehicle_id=v.vehicle_id
        JOIN Vehicle_category vc ON v.category_id=vc.category_id
        LEFT JOIN Driver d ON b.driver_id=d.driver_id
        LEFT JOIN Payment p ON b.booking_id=p.booking_id";
$params = [];
if ($statusF) { $sql .= " WHERE b.status=?"; $params[] = $statusF; }
$sql .= " ORDER BY b.booking_id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$bookings = $stmt->fetchAll();

function sb($s){$m=['Confirmed'=>'badge-success','Pending'=>'badge-warning','Completed'=>'badge-info','Cancelled'=>'badge-danger'];return '<span class="badge '.($m[$s]??'badge-neutral').'">'.htmlspecialchars($s).'</span>';}
function pb($s){$m=['Paid'=>'badge-success','Pending'=>'badge-warning','Overdue'=>'badge-danger'];return '<span class="badge '.($m[$s]??'badge-neutral').'">'.htmlspecialchars($s??'N/A').'</span>';}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings — DriveX Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">ADMIN PANEL</span></div></nav>
<?php include '../includes/admin_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

  <div class="page-header">
    <div class="header-row">
      <div><h2>All Bookings</h2><p>Monitor and manage every booking in the system.</p></div>
    </div>
  </div>

  <!-- FILTER -->
  <div class="search-section">
    <form method="GET" class="search-row">
      <div class="form-group">
        <label class="form-label">Filter by Status</label>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="bookings.php" class="btn btn-secondary" style="margin-left:8px;">Reset</a>
      </div>
    </form>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Dates</th><th>Driver</th><th>Total</th><th>Status</th><th>Payment</th><th>Update</th></tr></thead>
        <tbody>
        <?php foreach($bookings as $b):
          $days = max(1, (strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400);
          $total = $days * $b['price_per_day'];
        ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $b['booking_id'] ?></td>
          <td><strong><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></strong></td>
          <td><?= htmlspecialchars($b['brand'].' '.$b['model']) ?><br><span class="mono" style="font-size:.7rem;color:var(--text3)"><?= htmlspecialchars($b['registration_no']) ?></span></td>
          <td><?= $b['start_date'] ?><br><span style="color:var(--text3);font-size:.8rem;">→ <?= $b['end_date'] ?></span></td>
          <td><?= $b['driver_name'] ? htmlspecialchars($b['driver_name']) : '<span class="text-muted">None</span>' ?></td>
          <td><strong>৳<?= number_format($total) ?></strong><br><span style="font-size:.75rem;color:var(--text3)"><?= $days ?> day<?= $days>1?'s':'' ?></span></td>
          <td><?= sb($b['status']) ?></td>
          <td><?= pb($b['payment_status']) ?></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
              <select name="status" class="form-control" style="padding:5px 8px;font-size:.8rem;">
                <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary btn-sm">✓</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($bookings)): ?><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)">No bookings found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
</body>
</html>
