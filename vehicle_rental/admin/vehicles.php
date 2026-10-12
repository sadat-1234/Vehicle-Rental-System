<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    try {
        $pdo->prepare("INSERT INTO Vehicle (registration_no, category_id, model, brand, admin_user_id) VALUES (?,?,?,?,?)")
            ->execute([sanitize($_POST['reg']), $_POST['category_id'], sanitize($_POST['model']), sanitize($_POST['brand']), $_SESSION['user_id']]);
        $msg = 'Vehicle added successfully.';
    } catch (Exception $e) { $err = 'Failed: '.$e->getMessage(); }
}

// DELETE VEHICLE
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    try {
        $pdo->prepare("DELETE FROM Vehicle WHERE vehicle_id=?")->execute([$_POST['vehicle_id']]);
        $msg = 'Vehicle removed.';
    } catch (Exception $e) { $err = 'Cannot delete — vehicle may have existing bookings.'; }
}

// FETCH
$categories = $pdo->query("SELECT * FROM Vehicle_category ORDER BY category_name")->fetchAll();
$catFilter = $_GET['cat'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT v.*, vc.category_name, vc.price_per_day,
        (SELECT COUNT(*) FROM Booking b WHERE b.vehicle_id=v.vehicle_id AND b.status IN ('Confirmed','Pending') AND b.end_date >= CURDATE()) AS active_bookings,
        (SELECT MAX(m.next_service_date) FROM Maintenance m WHERE m.vehicle_id=v.vehicle_id) AS next_service
        FROM Vehicle v JOIN Vehicle_category vc ON v.category_id=vc.category_id";
$params = [];
if ($catFilter) { $sql .= " WHERE v.category_id=?"; $params[] = $catFilter; }
$sql .= " ORDER BY v.vehicle_id DESC";
$vehicles = $pdo->prepare($sql); $vehicles->execute($params); $vehicles = $vehicles->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicles — DriveX Admin</title>
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
      <div><h2>Vehicles</h2><p>Manage your fleet, monitor availability and bookings.</p></div>
      <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">+ Add Vehicle</button>
    </div>
  </div>

  <!-- FILTERS -->
  <div class="search-section">
    <form method="GET" class="search-row">
      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="cat" class="form-control">
          <option value="">All Categories</option>
          <?php foreach($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>" <?= $catFilter==$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="vehicles.php" class="btn btn-secondary" style="margin-left:8px;">Reset</a>
      </div>
    </form>
  </div>

  <!-- TABLE -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Registration</th><th>Brand / Model</th><th>Category</th><th>Price/Day</th><th>Active Bookings</th><th>Next Service</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($vehicles as $v):
          $available = $v['active_bookings'] == 0;
          $serviceOverdue = $v['next_service'] && strtotime($v['next_service']) < time();
        ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $v['vehicle_id'] ?></td>
          <td><strong><?= htmlspecialchars($v['registration_no']) ?></strong></td>
          <td><?= htmlspecialchars($v['brand'].' '.$v['model']) ?></td>
          <td><span class="badge badge-neutral"><?= htmlspecialchars($v['category_name']) ?></span></td>
          <td>৳<?= number_format($v['price_per_day']) ?></td>
          <td><?= $v['active_bookings'] > 0 ? '<span class="badge badge-warning">'.$v['active_bookings'].' active</span>' : '<span class="badge badge-success">Free</span>' ?></td>
          <td><?= $v['next_service'] ? '<span class="'.($serviceOverdue?'text-danger':'').'">'.$v['next_service'].'</span>' : '<span class="text-muted">—</span>' ?></td>
          <td><?= $available && !$serviceOverdue ? '<span class="badge badge-success">Available</span>' : (!$available ? '<span class="badge badge-warning">Booked</span>' : '<span class="badge badge-danger">Service Due</span>') ?></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this vehicle?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="vehicle_id" value="<?= $v['vehicle_id'] ?>">
              <button class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($vehicles)): ?><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)">No vehicles found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add New Vehicle</div>
      <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Registration No.</label>
          <input type="text" name="reg" class="form-control" placeholder="DHK-XXXX" required>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Brand</label>
            <input type="text" name="brand" class="form-control" placeholder="Toyota" required>
          </div>
          <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" placeholder="Corolla" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-control" required>
            <option value="">Select Category</option>
            <?php foreach($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?> — ৳<?= number_format($c['price_per_day']) ?>/day</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Vehicle</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
