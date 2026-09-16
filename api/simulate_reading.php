<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'POST required']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$binId = max(1, (int)($input['bin_id'] ?? 1));
$fill = max(0, min(100, (int)($input['fill_percent'] ?? 0)));
$height = max(5, min(200, (float)($input['height_cm'] ?? 30)));
$distance = round(max(0, $height * (1 - ($fill / 100))), 1);

try {
  $stmt = $pdo->prepare("CALL insert_reading(:bin_id, :fill_percent, :distance_cm)");
  $stmt->execute([
    ':bin_id' => $binId,
    ':fill_percent' => $fill,
    ':distance_cm' => $distance,
  ]);

  echo json_encode([
    'success' => true,
    'message' => "Reading sent: {$fill}% fill, {$distance} cm distance.",
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
