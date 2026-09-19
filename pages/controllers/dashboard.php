<?php
require_once 'includes/db.php';
require_once 'includes/ui.php';

$allBins = $pdo->query("SELECT id, name, location, height_cm FROM bins ORDER BY id ASC")->fetchAll();
$validIds = array_column($allBins, 'id');
$binId = isset($_GET['bin_id']) ? (int)$_GET['bin_id'] : ($validIds[0] ?? 1);
if (!in_array($binId, $validIds, true)) $binId = $validIds[0] ?? 1;

$currentBin = $allBins[array_search($binId, $validIds, true)] ?? ['name' => 'Bin', 'location' => '', 'height_cm' => 30];
$stmt = $pdo->prepare("SELECT * FROM latest_reading WHERE bin_id = :id");
$stmt->execute([':id' => $binId]);
$initial = $stmt->fetch();

$fill = (int)($initial['fill_percent'] ?? 0);
$status = $initial['status'] ?? 'ready';
$lidStatus = $initial['lid_status'] ?? 'closed';
$binHeight = (int)($currentBin['height_cm'] ?? $initial['height_cm'] ?? 30);

$stmt = $pdo->prepare("SELECT fill_percent, recorded_at FROM readings WHERE bin_id=:id AND recorded_at >= NOW() - INTERVAL 24 HOUR ORDER BY recorded_at ASC");
$stmt->execute([':id' => $binId]);
$chartRows = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM readings WHERE bin_id = :id");
$stmt->execute([':id' => $binId]);
$totalReadings = (int)$stmt->fetchColumn();

$logPerPage = 10;
$logPage = max(1, (int)($_GET['log_page'] ?? 1));
$eventFilter = "
  fill_percent > 0
  AND (
    prev_fill IS NULL
    OR prev_fill <> fill_percent
    OR prev_status <> status
  )
";

$eventSource = "
  SELECT
    id,
    fill_percent,
    status,
    recorded_at,
    LAG(fill_percent) OVER (ORDER BY recorded_at ASC, id ASC) AS prev_fill,
    LAG(status) OVER (ORDER BY recorded_at ASC, id ASC) AS prev_status
  FROM readings
  WHERE bin_id = :id
";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ($eventSource) events WHERE $eventFilter");
$stmt->execute([':id' => $binId]);
$totalEvents = (int)$stmt->fetchColumn();

$logTotalPages = max(1, (int)ceil($totalEvents / $logPerPage));
$logPage = min($logPage, $logTotalPages);
$logOffset = ($logPage - 1) * $logPerPage;
$logStart = $totalEvents > 0 ? $logOffset + 1 : 0;

$stmt = $pdo->prepare("
  SELECT fill_percent, status, recorded_at
  FROM ($eventSource) events
  WHERE $eventFilter
  ORDER BY recorded_at DESC, id DESC
  LIMIT $logPerPage OFFSET $logOffset
");
$stmt->execute([':id' => $binId]);
$logRows = $stmt->fetchAll();
$logEnd = min($logOffset + count($logRows), $totalEvents);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM person_events WHERE bin_id=:id AND DATE(detected_at)=CURDATE()");
$stmt->execute([':id' => $binId]);
$visitorsToday = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM person_events WHERE bin_id=:id");
$stmt->execute([':id' => $binId]);
$visitorsTotal = (int)$stmt->fetchColumn();

$page_title = 'Dashboard';
$active_nav = 'dashboard';
$extra_css = '/intelibin/assets/css/pages/dashboard.css';
$extra_head = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>';
