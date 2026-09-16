<?php
require_once 'includes/auth.php';

$error = '';
$userId = '';

if (admin_logged_in()) {
  header('Location: /intelibin/dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = trim($_POST['user_id'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if (attempt_admin_login($userId, $password)) {
    login_admin($userId);
    header('Location: /intelibin/dashboard.php');
    exit;
  }

  $error = 'Invalid User ID or password.';
}
