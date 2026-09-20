<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!admin_logged_in()) {
  http_response_code(401);
  echo json_encode(['error' => 'Authentication required']);
  exit;
}

$_GET['bin_id'] = $_GET['bin_id'] ?? ($_GET['bin'] ?? 1);

try {
  chdir(dirname(__DIR__));
  require_once __DIR__ . '/../pages/controllers/dashboard.php';
  require_once __DIR__ . '/../components/dashboard/_helpers.php';
  echo ib_state_json(ib_vm($vm ?? []));
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
