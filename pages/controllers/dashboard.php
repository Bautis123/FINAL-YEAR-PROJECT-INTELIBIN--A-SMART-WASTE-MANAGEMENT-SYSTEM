<?php
require_once 'includes/db.php';
require_once 'includes/ui.php';

date_default_timezone_set('Africa/Lusaka');

$allBins = $pdo->query("SELECT id, name, location, height_cm FROM bins ORDER BY id ASC")->fetchAll();
$validIds = array_map('intval', array_column($allBins, 'id'));
$binId = isset($_GET['bin_id']) ? (int)$_GET['bin_id'] : ($validIds[0] ?? 1);
if (!in_array($binId, $validIds, true)) $binId = $validIds[0] ?? 1;

$currentBin = $allBins[array_search($binId, $validIds, true)] ?? [
  'id' => 1,
  'name' => 'InteliBin #1',
  'location' => '',
  'height_cm' => 30,
];

$stmt = $pdo->prepare("SELECT * FROM latest_reading WHERE bin_id = :id");
$stmt->execute([':id' => $binId]);
$initial = $stmt->fetch() ?: [];

$fill = isset($initial['fill_percent']) ? (float)$initial['fill_percent'] : null;
$lidStatus = $initial['lid_status'] ?? null;
$lastReadingTs = !empty($initial['recorded_at']) ? strtotime($initial['recorded_at']) : null;
$now = time();

$stmt = $pdo->prepare("
  SELECT fill_percent, recorded_at
  FROM readings
  WHERE bin_id = :id AND recorded_at >= NOW() - INTERVAL 7 DAY
  ORDER BY recorded_at ASC
");
$stmt->execute([':id' => $binId]);
$historyRows = $stmt->fetchAll();
$history = array_map(fn($r) => [
  't' => strtotime($r['recorded_at']),
  'p' => (float)$r['fill_percent'],
], $historyRows);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM readings WHERE bin_id = :id");
$stmt->execute([':id' => $binId]);
$totalReadings = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM person_events WHERE bin_id = :id AND DATE(detected_at) = CURDATE()");
$stmt->execute([':id' => $binId]);
$visitorsToday = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM person_events WHERE bin_id = :id");
$stmt->execute([':id' => $binId]);
$visitorsTotal = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT command, issued_at, executed_at, outcome
  FROM command_log
  WHERE bin_id = :id
  ORDER BY issued_at DESC
  LIMIT 5
");
$stmt->execute([':id' => $binId]);
$commandRows = $stmt->fetchAll();

$events = [];
foreach ($commandRows as $cmd) {
  $kind = match ($cmd['command']) {
    'open' => 'lid_open',
    'close', 'reset' => 'lid_closed',
    default => 'info',
  };
  $events[] = [
    't' => strtotime($cmd['executed_at'] ?: $cmd['issued_at']),
    'kind' => $kind,
    'title' => ucfirst(str_replace('_', ' ', $cmd['command'])) . ' command',
    'detail' => ucfirst($cmd['outcome']) . ' command from dashboard',
  ];
}

$hasRecentReading = $lastReadingTs !== null && ($now - $lastReadingTs) <= 10 * 60;
$pipelineState = $fill === null ? 'idle' : ($fill >= 95 ? 'full' : ($fill >= 80 ? 'almost' : ($fill >= 60 ? 'filling' : 'ready')));

$bins = [];
foreach ($allBins as $bin) {
  $stmt = $pdo->prepare("SELECT fill_percent FROM latest_reading WHERE bin_id = :id");
  $stmt->execute([':id' => (int)$bin['id']]);
  $latest = $stmt->fetch();
  $bins[] = [
    'id' => (int)$bin['id'],
    'name' => $bin['name'],
    'location' => $bin['location'],
    'fill' => isset($latest['fill_percent']) ? (float)$latest['fill_percent'] : null,
    'href' => '/intelibin/dashboard.php?bin_id=' . (int)$bin['id'],
  ];
}

$vm = [
  'bin' => [
    'id' => (int)$binId,
    'name' => $currentBin['name'],
    'location' => $currentBin['location'] ?: 'No location set',
    'height_cm' => (int)($currentBin['height_cm'] ?? 30),
    'dead_zone_cm' => 4,
    'alert_at' => 80,
  ],
  'bins' => $bins,
  'fill' => $fill,
  'total_readings' => $totalReadings,
  'last_reading_ts' => $lastReadingTs,
  'lid' => in_array($lidStatus, ['open', 'closed'], true) ? $lidStatus : ($lidStatus === 'locked' ? 'closed' : null),
  'visits_today' => $visitorsToday,
  'visits_all' => $visitorsTotal,
  'device' => [
    'sensor_online' => $hasRecentReading,
    'controller_online' => $hasRecentReading,
    'sensor_label' => 'HC-SR04 pair',
    'controller_label' => 'Arduino Uno',
  ],
  'history' => $history,
  'events' => $events,
  'pipeline' => [
    ['label' => 'Arduino Uno', 'detail' => $hasRecentReading ? 'Serial active' : 'Waiting for reading', 'state' => $hasRecentReading ? 'ready' : 'idle'],
    ['label' => 'USB Serial', 'detail' => 'Python bridge', 'state' => $hasRecentReading ? 'ready' : 'idle'],
    ['label' => 'MySQL DB', 'detail' => $totalReadings . ' readings', 'state' => $pipelineState],
    ['label' => 'Dashboard', 'detail' => 'Live polling', 'state' => $pipelineState],
  ],
  'nav' => [
    ['label' => 'Dashboard', 'href' => '/intelibin/dashboard.php', 'icon' => 'dash', 'current' => true],
    ['label' => 'Bins', 'href' => '/intelibin/bins.php', 'icon' => 'bin'],
    ['label' => 'History', 'href' => '/intelibin/history.php', 'icon' => 'hist'],
    ['label' => 'Alerts', 'href' => '/intelibin/alerts.php', 'icon' => 'bell', 'badge' => ($fill !== null && $fill >= 80)],
    ['label' => 'Settings', 'href' => '/intelibin/settings.php', 'icon' => 'sliders'],
    ['label' => 'About', 'href' => '/intelibin/about.php', 'icon' => 'info'],
  ],
  'home_href' => '/intelibin/dashboard.php',
  'alerts_href' => '/intelibin/alerts.php',
  'history_href' => '/intelibin/history.php',
  'logout_href' => '/intelibin/logout.php',
  'asset_base' => '/intelibin/assets',
  'now' => $now,
];

$page_title = 'Dashboard';
$active_nav = 'dashboard';
$extra_css = '/intelibin/assets/css/pages/dashboard.css';
$extra_head = '
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Hanken+Grotesk:wght@400..700&display=swap" rel="stylesheet">
';
