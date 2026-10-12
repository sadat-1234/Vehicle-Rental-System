<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add') {
    try {
        $pdo->prepare("INSERT INTO Maintenance (service_date, description, next_service_date, cost, vehicle_id) VALUES (?,?,?,?,?)")
            ->execute([$_POST['service_date'], sanitize($_POST['description']), $_POST['next_service_date'], $_POST['cost'], $_POST['vehicle_id']]);
        $msg = 'Maintenance record added.';
    } catch(Exception $e) { $err = $e->getMessage(); }
}

$vehicles = $pdo->query("SELECT vehicle_id, brand, model, registration_no FROM Vehicle ORDER BY brand")->fetchAll();
$maintenance = $pdo->query("
  SELECT m.*, v.brand, v.model, v.registration_no
  FROM Maintenance m JOIN Vehicle v ON m.vehicle_id=v.vehicle_id
  ORDER BY m.next_service_date ASC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Maintenance — DriveX Admin</title>
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
      <div><h2>Maintenance</h2><p>Track service schedules and vehicle repair history.</p></div>
      <button class="btn btn-primary" onclick="document.getElementById('addMaintModal').style.display='flex'">+ Add Record</button>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>#</th><th>Vehicle</th><th>Service Date</th><th>Description</th><th>Cost</th><th>Next Service</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($maintenance as $m):
          $overdue = strtotime($m['next_service_date']) < time();
          $soon = !$overdue && strtotime($m['next_service_date']) <= strtotime('+14 days');
        ?>
        <tr>
          <td class="mono" style="color:var(--text3)">#<?= $m['maintenance_id'] ?></td>
          <td><strong><?= htmlspecialchars($m['brand'].' '.$m['model']) ?></strong><br><span class="mono" style="font-size:.72rem;color:var(--text3)"><?= htmlspecialchars($m['registration_no']) ?></span></td>
          <td><?= $m['service_date'] ?></td>
          <td><?= htmlspecialchars($m['description']) ?></td>
          <td>৳<?= number_format($m['cost']) ?></td>
          <td><?= $m['next_service_date'] ?></td>
          <td>
            <?php if($overdue): ?>
            <span class="badge badge-danger">⚠ Overdue</span>
            <?php elseif($soon): ?>
            <span class="badge badge-warning">Due Soon</span>
            <?php else: ?>
            <span class="badge badge-success">On Schedule</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addMaintModal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Add Maintenance Record</div><button class="modal-close" onclick="document.getElementById('addMaintModal').style.display='none'">✕</button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Vehicle</label>
          <select name="vehicle_id" class="form-control" required>
            <option value="">Select Vehicle</option>
            <?php foreach($vehicles as $v): ?>
            <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['brand'].' '.$v['model']) ?> (<?= htmlspecialchars($v['registration_no']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" placeholder="Oil change / Brake fix..." required>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Service Date</label>
            <input type="date" name="service_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Next Service Date</label>
            <input type="date" name="next_service_date" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Cost (৳)</label>
          <input type="number" name="cost" class="form-control" min="0" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addMaintModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Record</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
