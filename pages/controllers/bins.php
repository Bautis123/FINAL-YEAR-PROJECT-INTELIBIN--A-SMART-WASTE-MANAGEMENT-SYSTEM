<?php
require_once 'includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['bin_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $height = (int)($_POST['height_cm'] ?? 30);

  if (in_array($action, ['add_bin', 'edit_bin'], true) && $name === '') {
    $error = 'Bin name is required.';
  } elseif (in_array($action, ['add_bin', 'edit_bin'], true) && ($height < 5 || $height > 200)) {
    $error = 'Height must be between 5 and 200 cm.';
  } elseif ($action === 'add_bin') {
    $stmt = $pdo->prepare("INSERT INTO bins (name, location, height_cm) VALUES (:n, :l, :h)");
    $stmt->execute([':n' => $name, ':l' => $location, ':h' => $height]);
    $pdo->prepare("INSERT IGNORE INTO bin_state (bin_id) VALUES (:id)")->execute([':id' => $pdo->lastInsertId()]);
    $success = "Bin \"{$name}\" added successfully.";
  } elseif ($action === 'edit_bin') {
    $stmt = $pdo->prepare("UPDATE bins SET name=:n, location=:l, height_cm=:h WHERE id=:id");
    $stmt->execute([':n' => $name, ':l' => $location, ':h' => $height, ':id' => $id]);
    $success = "Bin \"{$name}\" updated successfully.";
  } elseif ($action === 'delete_bin') {
    $pdo->prepare("DELETE FROM bins WHERE id = :id")->execute([':id' => $id]);
    $success = 'Bin deleted successfully.';
  }
}

$bins = $pdo->query("
  SELECT b.id, b.name, b.location, b.height_cm, b.created_at,
         r.fill_percent, r.status, r.recorded_at AS last_reading,
         (SELECT MAX(a.resolved_at) FROM alerts a WHERE a.bin_id = b.id AND a.resolved = 1) AS last_emptied
  FROM bins b
  LEFT JOIN readings r ON r.id = (
    SELECT id FROM readings WHERE bin_id = b.id ORDER BY recorded_at DESC LIMIT 1
  )
  ORDER BY b.id ASC
")->fetchAll();

$page_title = 'Bins';
$page_subtitle = 'Fill levels, emptying history, and bin management';
$active_nav = 'bins';
