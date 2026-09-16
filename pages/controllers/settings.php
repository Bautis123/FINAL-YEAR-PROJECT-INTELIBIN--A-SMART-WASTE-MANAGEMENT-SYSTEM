<?php
require_once 'includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_bin') {
  $id = (int)$_POST['bin_id'];
  $name = trim($_POST['name'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $height = (int)$_POST['height_cm'];

  if ($name === '') {
    $error = 'Bin name cannot be empty.';
  } elseif ($height < 5 || $height > 200) {
    $error = 'Height must be between 5 and 200 cm.';
  } else {
    $stmt = $pdo->prepare("UPDATE bins SET name=:name, location=:location, height_cm=:height WHERE id=:id");
    $stmt->execute([':name' => $name, ':location' => $location, ':height' => $height, ':id' => $id]);
    $success = 'Bin settings saved.';
  }
}

$bins = $pdo->query("SELECT * FROM bins ORDER BY id")->fetchAll();

$page_title = 'Settings';
$active_nav = 'settings';
