<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireCustomer();

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// PROCESS BOOKING
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='book') {
    $vid       = intval($_POST['vehicle_id']);
    $startDate = $_POST['start_date'];
    $endDate   = $_POST['end_date'];
    $pickupDate= $_POST['pickup_date'];
    $pickupTime= $_POST['pickup_time'];
    $wantDriver= isset($_POST['want_driver']) && $_POST['want_driver'] === '1';
    $driverId  = $wantDriver ? ($_POST['driver_id'] ?: null) : null;

    // Validate dates
    if ($endDate < $startDate) { $err = 'End date must be after start date.'; }
    else {
        // Check vehicle availability
        $chk = $pdo->prepare("SELECT COUNT(*) FROM Booking WHERE vehicle_id=? AND status IN ('Confirmed','Pending') AND NOT (end_date < ? OR start_date > ?)");
        $chk->execute([$vid, $startDate, $endDate]);
        if ($chk->fetchColumn() > 0) {
            $err = 'Vehicle is not available for the selected dates.';
        } else {
            try {
                $pdo->beginTransaction();
                // Pick any admin
                $adminId = $pdo->query("SELECT user_id FROM Admin LIMIT 1")->fetchColumn();

                // Calc amount
                $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400);
                $priceRow = $pdo->prepare("SELECT vc.price_per_day FROM Vehicle v JOIN Vehicle_category vc ON v.category_id=vc.category_id WHERE v.vehicle_id=?");
                $priceRow->execute([$vid]); $ppd = $priceRow->fetchColumn();
                $amount = $days * $ppd;

                $pdo->prepare("INSERT INTO Booking (booking_date, start_date, end_date, pickup_date, pickup_time, status, customer_user_id, admin_user_id, vehicle_id, driver_id) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([date('Y-m-d'), $startDate, $endDate, $pickupDate, $pickupTime, 'Pending', $uid, $adminId, $vid, $driverId]);
                $bid = $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO Payment (payment_date, payment_method, payment_status, amount, booking_id) VALUES (?,?,?,?,?)")
                    ->execute([$endDate, null, 'Pending', $amount, $bid]);

                // Log search
                $pdo->prepare("INSERT IGNORE INTO Search (customer_user_id, vehicle_id) VALUES (?,?)")->execute([$uid, $vid]);

                $pdo->commit();
                $msg = 'Booking placed successfully! Booking #'.$bid.' is pending confirmation.';
            } catch(Exception $e) { $pdo->rollBack(); $err = 'Booking failed: '.$e->getMessage(); }
        }
    }
}

// SEARCH FILTERS
$catFilter   = $_GET['category'] ?? '';
$startFilter = $_GET['start_date'] ?? '';
$endFilter   = $_GET['end_date'] ?? '';

$categories = $pdo->query("SELECT * FROM Vehicle_category ORDER BY category_name")->fetchAll();

// Build query — available vehicles
$sql = "SELECT v.*, vc.category_name, vc.price_per_day,
        (SELECT COUNT(*) FROM Booking b WHERE b.vehicle_id=v.vehicle_id AND b.status IN ('Confirmed','Pending')
         ".($startFilter && $endFilter ? "AND NOT (b.end_date < '$startFilter' OR b.start_date > '$endFilter')" : "")."
        ) AS conflict_count
        FROM Vehicle v JOIN Vehicle_category vc ON v.category_id=vc.category_id
        WHERE 1=1";
$params = [];
if ($catFilter) { $sql .= " AND v.category_id=?"; $params[] = $catFilter; }
$sql .= " HAVING conflict_count = 0 ORDER BY vc.price_per_day ASC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Available drivers
$drivers = $pdo->query("SELECT * FROM Driver WHERE availability_status='Available' ORDER BY name")->fetchAll();

$emojiMap = ['Sedan'=>'🚗','SUV'=>'🚙','Bike'=>'🏍️','Microbus'=>'🚐'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Vehicles — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.vehicle-card { cursor: pointer; }
.vehicle-card:hover { border-color: var(--accent); }
.vehicle-card.selected { border-color: var(--accent); background: rgba(232,160,16,0.05); }
</style>
</head>
<body>
<nav class="navbar"><div class="container"><a href="#" class="logo">Drive<span>X</span></a><span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">CUSTOMER PORTAL</span></div></nav>
<?php include '../includes/customer_sidebar.php'; ?>

<div class="main-content"><div class="content-area">
  <?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="page-header"><h2>Search & Book</h2><p>Find the perfect vehicle for your trip. Filter by type and dates.</p></div>

  <!-- SEARCH FILTERS -->
  <div class="search-section">
    <h3>🔍 FILTER VEHICLES</h3>
    <form method="GET" class="search-row">
      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
          <option value="">All Types</option>
          <?php foreach($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>" <?= $catFilter==$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?> — ৳<?= number_format($c['price_per_day']) ?>/day</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startFilter) ?>" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endFilter) ?>" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="search.php" class="btn btn-secondary" style="margin-left:8px;">Reset</a>
      </div>
    </form>
  </div>

  <!-- RESULTS -->
  <?php if(empty($vehicles)): ?>
  <div class="empty-state card"><span class="empty-icon">🔍</span><p>No available vehicles match your search. Try different dates or category.</p></div>
  <?php else: ?>
  <div style="margin-bottom:16px;color:var(--text3);font-size:.85rem;font-family:'Space Mono',monospace;"><?= count($vehicles) ?> VEHICLE<?= count($vehicles)!==1?'S':'' ?> AVAILABLE</div>
  <div class="vehicles-grid" id="vehicleGrid">
    <?php foreach($vehicles as $v):
      $emoji = $emojiMap[$v['category_name']] ?? '🚗';
    ?>
    <div class="vehicle-card" onclick="selectVehicle(<?= $v['vehicle_id'] ?>, '<?= addslashes($v['brand'].' '.$v['model']) ?>', <?= $v['price_per_day'] ?>)">
      <div class="vehicle-card-img"><?= $emoji ?></div>
      <div class="vehicle-card-body">
        <div class="vehicle-name"><?= htmlspecialchars($v['brand'].' '.$v['model']) ?></div>
        <div class="vehicle-reg"><?= htmlspecialchars($v['registration_no']) ?></div>
        <div class="vehicle-meta">
          <span class="badge badge-neutral"><?= htmlspecialchars($v['category_name']) ?></span>
          <div class="vehicle-price">৳<?= number_format($v['price_per_day']) ?><span>/day</span></div>
        </div>
        <button class="btn btn-primary btn-block" type="button" onclick="event.stopPropagation();selectVehicle(<?= $v['vehicle_id'] ?>, '<?= addslashes($v['brand'].' '.$v['model']) ?>', <?= $v['price_per_day'] ?>)">
          Book This Vehicle →
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div></div>

<!-- BOOKING MODAL -->
<div class="modal-overlay" id="bookingModal" style="display:none;" onclick="if(event.target===this)closeModal()">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title">Book Vehicle</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" id="bookingForm">
      <div class="modal-body">
        <input type="hidden" name="action" value="book">
        <input type="hidden" name="vehicle_id" id="selectedVehicleId">

        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:20px;">
          <div style="font-size:.75rem;color:var(--text3);font-family:'Space Mono',monospace;margin-bottom:4px;">SELECTED VEHICLE</div>
          <div style="font-size:1.1rem;font-weight:700;" id="selectedVehicleName">—</div>
          <div style="color:var(--accent);font-family:'Bebas Neue',sans-serif;font-size:1.2rem;" id="selectedVehiclePrice"></div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" id="bookStartDate" class="form-control" min="<?= date('Y-m-d') ?>" required onchange="calcTotal()">
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" id="bookEndDate" class="form-control" min="<?= date('Y-m-d') ?>" required onchange="calcTotal()">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Pickup Date</label>
            <input type="date" name="pickup_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Pickup Time</label>
            <input type="time" name="pickup_time" class="form-control" value="10:00" required>
          </div>
        </div>

        <!-- DRIVER OPTION -->
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:16px;">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:12px;">
            <input type="checkbox" id="wantDriverCheck" onchange="toggleDriver(this)" style="width:16px;height:16px;accent-color:var(--accent)">
            <span style="font-weight:600;">Add a Driver <span style="color:var(--text3);font-size:.8rem;">(optional)</span></span>
          </label>
          <div id="driverSelect" style="display:none;">
            <input type="hidden" name="want_driver" id="wantDriverHidden" value="0">
            <select name="driver_id" class="form-control">
              <option value="">Auto-assign available driver</option>
              <?php foreach($drivers as $d): ?>
              <option value="<?= $d['driver_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="form-hint">Driver will be assigned based on availability.</p>
          </div>
        </div>

        <!-- TOTAL ESTIMATE -->
        <div id="totalEstimate" style="display:none;background:rgba(232,160,16,0.08);border:1px solid rgba(232,160,16,0.2);border-radius:var(--radius);padding:14px;text-align:center;">
          <div style="font-size:.75rem;color:var(--text3);font-family:'Space Mono',monospace;">ESTIMATED TOTAL</div>
          <div style="font-family:'Bebas Neue',sans-serif;font-size:2.5rem;color:var(--accent);" id="totalAmount">৳0</div>
          <div style="font-size:.8rem;color:var(--text2);" id="totalDays"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">Confirm Booking →</button>
      </div>
    </form>
  </div>
</div>

<script>
let selectedPPD = 0;

function selectVehicle(id, name, ppd) {
  selectedPPD = ppd;
  document.getElementById('selectedVehicleId').value = id;
  document.getElementById('selectedVehicleName').textContent = name;
  document.getElementById('selectedVehiclePrice').textContent = '৳' + ppd.toLocaleString() + ' / day';
  // Pre-fill dates from filter
  const start = '<?= $startFilter ?>';
  const end = '<?= $endFilter ?>';
  if (start) { document.getElementById('bookStartDate').value = start; document.querySelector('[name=pickup_date]').value = start; }
  if (end)   { document.getElementById('bookEndDate').value = end; }
  calcTotal();
  document.getElementById('bookingModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('bookingModal').style.display = 'none';
}

function toggleDriver(cb) {
  const ds = document.getElementById('driverSelect');
  const hw = document.getElementById('wantDriverHidden');
  ds.style.display = cb.checked ? 'block' : 'none';
  hw.value = cb.checked ? '1' : '0';
}

function calcTotal() {
  const s = document.getElementById('bookStartDate').value;
  const e = document.getElementById('bookEndDate').value;
  if (!s || !e) { document.getElementById('totalEstimate').style.display='none'; return; }
  const days = Math.max(1, Math.ceil((new Date(e) - new Date(s)) / 86400000));
  const total = days * selectedPPD;
  document.getElementById('totalAmount').textContent = '৳' + total.toLocaleString('en-IN');
  document.getElementById('totalDays').textContent = days + ' day' + (days > 1 ? 's' : '') + ' × ৳' + selectedPPD.toLocaleString();
  document.getElementById('totalEstimate').style.display = 'block';
}
</script>
</body>
</html>
