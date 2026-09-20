<?php
require_once 'includes/db.php';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve') {
  $id = (int)$_POST['alert_id'];
  $pdo->prepare("UPDATE alerts SET resolved = 1, resolved_at = NOW() WHERE id = :id")
      ->execute([':id' => $id]);
  $success = 'Alert marked as resolved - bin emptied.';
}

$totalAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$openAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE resolved = 0")->fetchColumn();
$resolvedAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE resolved = 1")->fetchColumn();
$avgResponse = $pdo->query("
  SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, triggered_at, resolved_at)))
  FROM alerts WHERE resolved = 1 AND resolved_at IS NOT NULL
")->fetchColumn();

$open = $pdo->query("
  SELECT a.*, b.name AS bin_name, b.location,
         TIMESTAMPDIFF(MINUTE, a.triggered_at, NOW()) AS mins_open
  FROM alerts a
  JOIN bins b ON b.id = a.bin_id
  WHERE a.resolved = 0
  ORDER BY a.triggered_at ASC
")->fetchAll();

$resolved = $pdo->query("
  SELECT a.*, b.name AS bin_name, b.location,
         TIMESTAMPDIFF(MINUTE, a.triggered_at, a.resolved_at) AS mins_to_resolve
  FROM alerts a
  JOIN bins b ON b.id = a.bin_id
  WHERE a.resolved = 1
  ORDER BY a.resolved_at DESC
  LIMIT 30
")->fetchAll();

$page_title = 'Alerts';
$page_subtitle = 'Full bin events, open alerts, and response records';
$active_nav = 'alerts';
