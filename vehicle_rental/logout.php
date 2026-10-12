<?php
require_once 'includes/auth.php';
session_destroy();
header('Location: /vehicle_rental/index.php');
exit;
