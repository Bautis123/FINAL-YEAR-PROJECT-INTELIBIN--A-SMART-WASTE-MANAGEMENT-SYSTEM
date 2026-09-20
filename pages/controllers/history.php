<?php
require_once 'includes/db.php';

$bin_id = isset($_GET['bin_id']) ? (int)$_GET['bin_id'] : 1;
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$per_page = 25;
$page_num = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page_num - 1) * $per_page;

$where = ['r.bin_id = :bin_id', 'DATE(r.recorded_at) BETWEEN :from AND :to'];
$params = [':bin_id' => $bin_id, ':from' => $from, ':to' => $to];

if ($status !== '') {
  $where[] = 'r.status = :status';
  $params[':status'] = $status;
}

$whereSQL = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM readings r WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $pdo->prepare("
  SELECT r.id, r.fill_percent, r.distance_cm, r.status, r.recorded_at,
         b.name AS bin_name
  FROM readings r
  JOIN bins b ON b.id = r.bin_id
  WHERE $whereSQL
  ORDER BY r.recorded_at DESC
  LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$readings = $stmt->fetchAll();
$bins = $pdo->query("SELECT id, name FROM bins ORDER BY id")->fetchAll();

$page_title = 'History';
$page_subtitle = 'All sensor readings, filters, and export records';
$active_nav = 'history';
