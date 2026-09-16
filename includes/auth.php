<?php
if (session_status() === PHP_SESSION_NONE) {
  session_name('intelibin_admin');
  session_start();
}

const ADMIN_USER_ID = 'admin';
const ADMIN_EMAIL = 'admin@intelibin.local';
const ADMIN_PASSWORD = 'admin123';

function admin_logged_in(): bool {
  return !empty($_SESSION['admin_user']);
}

function admin_name(): string {
  return $_SESSION['admin_user'] ?? '';
}

function attempt_admin_login(string $userId, string $password): bool {
  $knownUser = hash_equals(ADMIN_USER_ID, $userId) || hash_equals(ADMIN_EMAIL, $userId);
  return $knownUser && hash_equals(ADMIN_PASSWORD, $password);
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
