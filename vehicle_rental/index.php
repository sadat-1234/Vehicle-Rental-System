<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('DRIVEX_SESSION'); 
    session_start();
}
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Redirect if already logged in[cite: 30]
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/vehicle_rental/admin/dashboard.php');
    } else {
        redirect('/vehicle_rental/customer/dashboard.php');
    }
}

$error = '';
$success = '';

// --- LOGIN LOGIC ---[cite: 30]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT u.*, a.user_id AS admin_id, c.user_id AS cust_id FROM User u LEFT JOIN Admin a ON u.user_id=a.user_id LEFT JOIN Customer c ON u.user_id=c.user_id WHERE u.email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Fix: Check password and properly close braces[cite: 30]
        if ($user && $password === $user['password']) {
            // STEP 1: Set session variables FIRST[cite: 30]
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name']  = $user['last_name'];
            $_SESSION['email']      = $user['email'];

            // STEP 2: Handle Roles
            if ($user['admin_id']) {
                $_SESSION['role'] = 'admin';
                redirect('/vehicle_rental/admin/dashboard.php');
            } else {
                $_SESSION['role'] = 'customer';
                redirect('/vehicle_rental/customer/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }

    // --- REGISTER LOGIC ---[cite: 30]
    if ($_POST['action'] === 'register') {
        $fname   = sanitize($_POST['first_name']);
        $lname   = sanitize($_POST['last_name']);
        $email   = sanitize($_POST['email']);
        $pass    = $_POST['password'];
        $phone   = sanitize($_POST['phone']);
        $license = sanitize($_POST['license_no']);
        $house   = sanitize($_POST['house_no']);
        $street  = sanitize($_POST['street']);
        $city    = sanitize($_POST['city']);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO User (first_name, last_name, email, password) VALUES (?,?,?,?)");
            $stmt->execute([$fname, $lname, $email, $pass]);
            $uid = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO User_phone (user_id, phone) VALUES (?,?)")->execute([$uid, $phone]);
            $pdo->prepare("INSERT INTO Customer (user_id, license_no, house_no, street, city) VALUES (?,?,?,?,?)")->execute([$uid, $license, $house, $street, $city]);

            $pdo->commit();
            $success = 'Account created! Please log in.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Registration failed: ' . ($e->getCode() == 23000 ? 'Email or license already exists.' : $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DriveX — Vehicle Rental</title>
<link rel="stylesheet" href="css/style.css">
<style>
.landing-nav { position:fixed;top:0;left:0;right:0;z-index:100;padding:0 40px;height:64px;display:flex;align-items:center;justify-content:space-between;background:rgba(10,10,15,0.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--border); }
.hero { padding-top: 64px; }
</style>
</head>
<body>
<nav class="landing-nav">
  <a href="index.php" class="logo">Drive<span>X</span></a>
  <div style="display:flex;gap:10px;">
    <a href="#login" class="btn btn-secondary btn-sm">Sign In</a>
    <a href="#register" class="btn btn-primary btn-sm">Register</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">🏎️ <span>Premium</span> Vehicle Rental Platform</div>
      <div class="hero-eyebrow">// Bangladesh's Finest Fleet</div>
      <h1 class="hero-title">YOUR JOURNEY<em>STARTS HERE</em></h1>
      <p class="hero-sub">Browse hundreds of vehicles, hire professional drivers, and manage your bookings — all from one platform.</p>
      <div class="hero-actions">
        <a href="#login" class="btn btn-primary btn-lg">Get Started</a>
        <a href="#vehicles-preview" class="btn btn-secondary btn-lg">View Fleet</a>
      </div>
    </div>
  </div>
</section>

<section class="features" id="vehicles-preview">
  <div class="container">
    <div class="features-header">
      <div class="section-label">// Our Fleet</div>
      <h2>Vehicles For Every Need</h2>
    </div>
    <div class="features-grid">
      <div class="feature-card"><span class="feature-icon">🚗</span><div class="feature-title">Sedan</div><div class="feature-desc">Comfortable sedans for city travel and airport transfers. Starting from ৳3,000/day.</div></div>
      <div class="feature-card"><span class="feature-icon">🚙</span><div class="feature-title">SUV</div><div class="feature-desc">Spacious SUVs for family trips and off-road adventures. Starting from ৳5,000/day.</div></div>
      <div class="feature-card"><span class="feature-icon">🏍️</span><div class="feature-title">Bike</div><div class="feature-desc">Nimble bikes to beat traffic in busy city streets. Starting from ৳800/day.</div></div>
      <div class="feature-card"><span class="feature-icon">🚐</span><div class="feature-title">Microbus</div><div class="feature-desc">Large microbuses for group travel and corporate events. Starting from ৳7,000/day.</div></div>
    </div>
  </div>
</section>

<!-- AUTH SECTION -->
<section id="login" style="padding:80px 0;background:var(--bg);">
  <div class="container" style="max-width:900px;">

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;">

      <!-- LOGIN -->
      <div class="card">
        <div class="card-header"><div class="card-title">Sign In</div></div>
        <form method="POST">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="you@email.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Sign In →</button>
          <p class="form-hint mt-2 text-center">Demo: admin1@gmail.com / pass123 &nbsp;|&nbsp; john@gmail.com / 1234</p>
        </form>
      </div>

      <!-- REGISTER -->
      <div class="card" id="register">
        <div class="card-header"><div class="card-title">Create Account</div></div>
        <form method="POST">
          <input type="hidden" name="action" value="register">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX" required>
            </div>
            <div class="form-group">
              <label class="form-label">License No.</label>
              <input type="text" name="license_no" class="form-control" required>
            </div>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">House No.</label>
              <input type="text" name="house_no" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Street</label>
              <input type="text" name="street" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" value="Dhaka">
          </div>
          <button type="submit" class="btn btn-primary btn-block">Create Account →</button>
        </form>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container"><div class="footer-inner">
    <div class="footer-copy">© 2026 DriveX Vehicle Rental. CSE370 Group 11.</div>
    <div class="footer-links"><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Support</a></div>
  </div></div>
</footer>
</body>
</html>
