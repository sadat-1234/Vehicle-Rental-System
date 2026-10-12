<aside class="sidebar">
  <nav class="sidebar-nav">
    <div class="sidebar-label">Overview</div>
    <a href="/vehicle_rental/admin/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <div class="sidebar-label">Fleet</div>
    <a href="/vehicle_rental/admin/vehicles.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='vehicles.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v5"/><circle cx="15.5" cy="18.5" r="2.5"/><circle cx="5.5" cy="18.5" r="2.5"/></svg>
      Vehicles
    </a>
    <a href="/vehicle_rental/admin/maintenance.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='maintenance.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      Maintenance
    </a>
    <a href="/vehicle_rental/admin/drivers.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='drivers.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Drivers
    </a>

    <div class="sidebar-label">Bookings</div>
    <a href="/vehicle_rental/admin/bookings.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='bookings.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      All Bookings
    </a>
    <a href="/vehicle_rental/admin/payments.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='payments.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
      Payments
    </a>
    <a href="/vehicle_rental/admin/penalties.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='penalties.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Penalties
    </a>

    <div class="sidebar-label">Users</div>
    <a href="/vehicle_rental/admin/customers.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])=='customers.php'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Customers
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($_SESSION['first_name'].' '.$_SESSION['last_name']) ?></div>
    <div class="user-role">Administrator</div>
    <a href="/vehicle_rental/logout.php" class="btn btn-danger btn-sm btn-block mt-2" style="margin-top:10px;">Sign Out</a>
  </div>
</aside>
