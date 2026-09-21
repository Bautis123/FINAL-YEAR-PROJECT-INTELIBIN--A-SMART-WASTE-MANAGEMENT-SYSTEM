<?php
require_once 'includes/auth.php';

$error = null;
$userId = '';
$loginMaxAttempts = 5;
$loginWindowSeconds = 300;

if (empty($_SESSION['login_csrf_token'])) {
  $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

function login_recent_attempts(int $windowSeconds): array {
  $now = time();
  $attempts = $_SESSION['login_attempts'] ?? [];
  $attempts = array_values(array_filter($attempts, fn($time) => is_int($time) && ($now - $time) < $windowSeconds));
  $_SESSION['login_attempts'] = $attempts;
  return $attempts;
}

function login_register_failed_attempt(int $windowSeconds): void {
  $attempts = login_recent_attempts($windowSeconds);
  $attempts[] = time();
  $_SESSION['login_attempts'] = $attempts;
}

if (admin_logged_in()) {
  header('Location: /intelibin/dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = trim($_POST['user_id'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $csrfToken = (string)($_POST['login_csrf_token'] ?? '');
  $attempts = login_recent_attempts($loginWindowSeconds);

  $csrfOk = hash_equals($_SESSION['login_csrf_token'], $csrfToken);
  $rateLimited = count($attempts) >= $loginMaxAttempts;

  if (!$rateLimited && $csrfOk && attempt_admin_login($userId, $password)) {
    unset($_SESSION['login_attempts'], $_SESSION['login_csrf_token']);
    login_admin($userId);
    header('Location: /intelibin/dashboard.php');
    exit;
  }

  if (!$rateLimited) {
    login_register_failed_attempt($loginWindowSeconds);
  }

  $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
  $error = 'Incorrect email or password.';
}

$csrfToken = $_SESSION['login_csrf_token'];
