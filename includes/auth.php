<?php
if (session_status() === PHP_SESSION_NONE) {
  session_name('intelibin_admin');
  session_start();
}

function admin_logged_in(): bool {
  return !empty($_SESSION['admin_user']);
}

function admin_name(): string {
  return $_SESSION['admin_user'] ?? '';
}

function attempt_admin_login(string $userId, string $password): bool {
  global $pdo;
  require_once __DIR__ . '/db.php';

  $stmt = $pdo->prepare("
    SELECT password_hash
    FROM admin_users
    WHERE active = 1 AND (user_id = :user_id OR email = :email)
    LIMIT 1
  ");
  $stmt->execute([':user_id' => $userId, ':email' => $userId]);
  $admin = $stmt->fetch();

  return $admin && password_verify($password, $admin['password_hash']);
}

function require_admin_login(): void {
  if (!admin_logged_in()) {
    header('Location: /intelibin/login.php');
    exit;
  }
}

function login_admin(string $userId): void {
  session_regenerate_id(true);
  $_SESSION['admin_user'] = $userId;
}

function logout_admin(): void {
  $_SESSION = [];
  session_destroy();
}
