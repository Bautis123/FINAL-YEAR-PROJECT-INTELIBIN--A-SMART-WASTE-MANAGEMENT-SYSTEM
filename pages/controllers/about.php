<?php
require_once 'includes/db.php';

$totalReadings = (int)$pdo->query("SELECT COUNT(*) FROM readings")->fetchColumn();
$totalBins = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
$totalAlerts = (int)$pdo->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
$firstReading = $pdo->query("SELECT MIN(recorded_at) FROM readings")->fetchColumn();
$lastReading = $pdo->query("SELECT MAX(recorded_at) FROM readings")->fetchColumn();

$page_title = 'About';
$page_subtitle = 'System overview, data pipeline, and hardware specifications';
$active_nav = 'about';
