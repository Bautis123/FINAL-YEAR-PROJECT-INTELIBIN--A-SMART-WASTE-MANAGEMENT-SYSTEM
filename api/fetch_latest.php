<?php
// api/fetch_latest.php
// Returns the latest reading for a bin as JSON.
// Called by the dashboard every 30 seconds.

require_once '../includes/db.php';
header('Content-Type: application/json');

$bin_id = isset($_GET['bin_id']) ? (int)$_GET['bin_id'] : 1;

$stmt = $pdo->prepare("SELECT * FROM latest_reading WHERE bin_id = :id");
$stmt->execute([':id' => $bin_id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'No readings found for bin ' . $bin_id]);
    exit;
}

echo json_encode($row);
