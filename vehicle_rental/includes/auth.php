<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('DRIVEX_SESSION'); // MUST match index.php
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/vehicle_rental/index.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/vehicle_rental/customer/dashboard.php');
        exit;
    }
}

function requireCustomer() {
    if (!isLoggedIn()) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/vehicle_rental/index.php');
        exit;
    }
    if (!isCustomer()) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . '/vehicle_rental/admin/dashboard.php');
        exit;
    }
}

function redirect($url) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . $url);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Clean up: Helper alias for old checkAdmin calls
function checkAdmin() {
    requireAdmin();
}

function isReporter() {
    return false; // Safely returns false so code doesn't crash
}