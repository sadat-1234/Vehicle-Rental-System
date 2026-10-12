<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireCustomer();

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE User SET first_name=?, last_name=?, email=? WHERE user_id=?")
            ->execute([
                sanitize($_POST['first_name']),
                sanitize($_POST['last_name']),
                sanitize($_POST['email']),
                $uid
            ]);

        $pdo->prepare("UPDATE Customer SET license_no=?, house_no=?, street=?, city=? WHERE user_id=?")
            ->execute([
                sanitize($_POST['license_no']),
                sanitize($_POST['house_no']),
                sanitize($_POST['street']),
                sanitize($_POST['city']),
                $uid
            ]);

        // Update phone — delete old, insert new
        if (!empty($_POST['phone'])) {
            $pdo->prepare("DELETE FROM User_phone WHERE user_id=?")->execute([$uid]);
            $pdo->prepare("INSERT INTO User_phone (user_id, phone) VALUES (?,?)")
                ->execute([$uid, sanitize($_POST['phone'])]);
        }

        $pdo->commit();

        // Update session name
        $_SESSION['first_name'] = sanitize($_POST['first_name']);
        $_SESSION['last_name']  = sanitize($_POST['last_name']);
        $_SESSION['email']      = sanitize($_POST['email']);

        $msg = 'Profile updated successfully!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = 'Update failed: ' . ($e->getCode() == 23000 ? 'Email already in use.' : $e->getMessage());
    }
}

// CHANGE PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM User WHERE user_id=?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();

    if ($current !== $row['password']) {
        $err = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $err = 'New passwords do not match.';
    } elseif (strlen($new) < 4) {
        $err = 'New password must be at least 4 characters.';
    } else {
        $pdo->prepare("UPDATE User SET password=? WHERE user_id=?")->execute([$new, $uid]);
        $msg = 'Password changed successfully!';
    }
}

// FETCH profile data
$stmt = $pdo->prepare("
    SELECT u.*, c.license_no, c.house_no, c.street, c.city,
           GROUP_CONCAT(up.phone SEPARATOR ', ') AS phones
    FROM User u
    JOIN Customer c ON u.user_id = c.user_id
    LEFT JOIN User_phone up ON u.user_id = up.user_id
    WHERE u.user_id = ?
    GROUP BY u.user_id
");
$stmt->execute([$uid]);
$profile = $stmt->fetch();

// FETCH stats
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(b.booking_id) AS total_bookings,
        SUM(CASE WHEN b.status IN ('Confirmed','Pending') THEN 1 ELSE 0 END) AS active_bookings,
        SUM(CASE WHEN b.status = 'Completed' THEN 1 ELSE 0 END) AS completed_bookings,
        COALESCE(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END), 0) AS total_spent
    FROM Booking b
    LEFT JOIN Payment p ON b.booking_id = p.booking_id
    WHERE b.customer_user_id = ?
");
$stmtStats->execute([$uid]);
$stats = $stmtStats->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — DriveX</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="#" class="logo">Drive<span>X</span></a>
        <span style="font-size:.8rem;color:var(--text3);font-family:'Space Mono',monospace;">CUSTOMER PORTAL</span>
    </div>
</nav>
<?php include '../includes/customer_sidebar.php'; ?>

<div class="main-content"><div class="content-area">

    <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h2>My Profile</h2>
        <p>View and update your personal information.</p>
    </div>

    <!-- PROFILE HEADER CARD -->
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div style="width:72px;height:72px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;color:#000;flex-shrink:0;">
                <?= strtoupper(substr($profile['first_name'],0,1).substr($profile['last_name'],0,1)) ?>
            </div>
            <div style="flex:1;">
                <h3 style="font-size:1.4rem;margin-bottom:4px;"><?= htmlspecialchars($profile['first_name'].' '.$profile['last_name']) ?></h3>
                <div style="color:var(--text2);font-size:.88rem;"><?= htmlspecialchars($profile['email']) ?></div>
                <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="badge badge-neutral">📋 License: <?= htmlspecialchars($profile['license_no']) ?></span>
                    <span class="badge badge-info">📍 <?= htmlspecialchars($profile['city']) ?></span>
                </div>
            </div>
            <!-- STATS -->
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div style="text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--accent);font-family:'Bebas Neue',sans-serif;"><?= $stats['total_bookings'] ?></div>
                    <div style="font-size:.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;font-family:'Space Mono',monospace;">Total Trips</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--success);font-family:'Bebas Neue',sans-serif;"><?= $stats['completed_bookings'] ?></div>
                    <div style="font-size:.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;font-family:'Space Mono',monospace;">Completed</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--info);font-family:'Bebas Neue',sans-serif;">৳<?= number_format($stats['total_spent']) ?></div>
                    <div style="font-size:.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:.1em;font-family:'Space Mono',monospace;">Total Spent</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

        <!-- EDIT PROFILE FORM -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Personal Information</div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($profile['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($profile['last_name']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($profile['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($profile['phones'] ?? '') ?>"
                           placeholder="017XXXXXXXX">
                </div>

                <div class="form-group">
                    <label class="form-label">Driver's License No.</label>
                    <input type="text" name="license_no" class="form-control"
                           value="<?= htmlspecialchars($profile['license_no']) ?>" required>
                </div>

                <div class="divider"></div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">House No.</label>
                        <input type="text" name="house_no" class="form-control"
                               value="<?= htmlspecialchars($profile['house_no'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Street</label>
                        <input type="text" name="street" class="form-control"
                               value="<?= htmlspecialchars($profile['street'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= htmlspecialchars($profile['city'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Save Changes →</button>
            </form>
        </div>

        <!-- RIGHT COLUMN -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- CHANGE PASSWORD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Change Password</div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-block">Update Password</button>
                </form>
            </div>

            <!-- ACCOUNT INFO (read-only) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Account Details</div>
                </div>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <div class="detail-key">User ID</div>
                        <div class="detail-val mono">#<?= $profile['user_id'] ?></div>
                    </div>
                    <div>
                        <div class="detail-key">Account Type</div>
                        <div class="detail-val">Customer</div>
                    </div>
                    <div>
                        <div class="detail-key">Active Bookings</div>
                        <div class="detail-val"><?= $stats['active_bookings'] ?> booking<?= $stats['active_bookings'] != 1 ? 's' : '' ?></div>
                    </div>
                    <div>
                        <div class="detail-key">Full Address</div>
                        <div class="detail-val">
                            <?= htmlspecialchars(implode(', ', array_filter([
                                $profile['house_no'],
                                $profile['street'],
                                $profile['city']
                            ]))) ?: '—' ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div></div>
</body>
</html>
