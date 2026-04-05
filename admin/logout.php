<?php
/**
 * Admin Logout
 */
require_once __DIR__ . '/../includes/auth.php';

logout();
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
