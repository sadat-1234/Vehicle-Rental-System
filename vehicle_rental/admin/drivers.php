<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO Driver (driver_license_no, name, availability_status, admin_user_id) VALUES (?,?,?,?)")
            ->execute([sanitize($_POST['license']), sanitize($_POST['name']), 'Available', $_SESSION['user_id']]);
        $did = $pdo->lastInsertId();
        if (!empty($_POST['phone'])) {
            $pdo->prepare("INSERT INTO Driver_phone (driver_id, contact) VALUES (?,?)")->execute([$did, sanitize($_POST['phone'])]);
        }
        $pdo->commit();
        $msg = 'Driver added.';
    } catch(Exception $e) { $pdo->rollBack(); $err = 'Failed: '.$e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='toggle') {
    $newStatus = $_POST['current'] === 'Available' ? 'Unavailable' : 'Available';
    $pdo->prepare("UPDATE Driver SET availability_status=? WHERE driver_id=?")->execute([$newStatus, $_POST['driver_id']]);
    $msg = 'Driver status updated.';
}

$drivers = $pdo->query("
  SELECT 
    d.*, 
    (SELECT GROUP_CONCAT(dp.contact SEPARATOR ', ') 
     FROM Driver_phone dp 
     WHERE dp.driver_id = d.driver_id) AS phones,
    (SELECT COUNT(*) 
     FROM Booking b 
     WHERE b.driver_id = d.driver_id) AS total_bookings
  FROM Driver d
  ORDER BY d.driver_id DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drivers — DriveX Admin</title>
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
      <div><h2>Drivers</h2><p>Manage driver profiles and availability status.</p></div>
      <button class="btn btn-primary" onclick="document.getElementById('addDrvModal').style.display='flex'">+ Add Driver</button>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Name</th><th>License</th><th>Phone(s)</th><th>Total Bookings</th><th>Status</th><th>Toggle</th></tr></thead>
        <tbody>
        <?php foreach($drivers as $d): ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $d['driver_id'] ?></td>
          <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
          <td class="mono" style="font-size:.82rem;"><?= htmlspecialchars($d['driver_license_no']) ?></td>
          <td style="font-size:.83rem;"><?= htmlspecialchars($d['phones']??'—') ?></td>
          <td><?= $d['total_bookings'] ?></td>
          <td><?= $d['availability_status']==='Available' ? '<span class="badge badge-success">Available</span>' : '<span class="badge badge-danger">Unavailable</span>' ?></td>
          <td>
            <form method="POST">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="driver_id" value="<?= $d['driver_id'] ?>">
              <input type="hidden" name="current" value="<?= $d['availability_status'] ?>">
              <button class="btn btn-secondary btn-sm"><?= $d['availability_status']==='Available' ? 'Mark Unavailable' : 'Mark Available' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>

<!-- ADD DRIVER MODAL -->
<div class="modal-overlay" id="addDrvModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Add New Driver</div><button class="modal-close" onclick="document.getElementById('addDrvModal').style.display='none'">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Driver License No.</label><input type="text" name="license" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="019XXXXXXXX"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDrvModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Driver</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
