<aside class="sidebar">
  <nav class="sidebar-nav">
    <div class="sidebar-label">Overview</div>
    <a href="/vehicle_rental/customer/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="sidebar-label">Vehicles</div>
    <a href="/vehicle_rental/customer/search.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='search.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      Search & Book
    </a>

    <div class="sidebar-label">My Activity</div>
    <a href="/vehicle_rental/customer/bookings.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='bookings.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      My Bookings
    </a>
    <a href="/vehicle_rental/customer/payments.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='payments.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
      Payments
    </a>
    <a href="/vehicle_rental/customer/profile.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      My Profile
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($_SESSION['first_name'].' '.$_SESSION['last_name']) ?></div>
    <div class="user-role">Customer</div>
    <a href="/vehicle_rental/logout.php" class="btn btn-danger btn-sm btn-block mt-2" style="margin-top:10px;">Sign Out</a>
  </div>
</aside>
