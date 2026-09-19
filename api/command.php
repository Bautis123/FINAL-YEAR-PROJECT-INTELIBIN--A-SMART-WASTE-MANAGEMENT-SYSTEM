<?php
// api/command.php
// POST endpoint — operator issues a command to a bin.
// Body (JSON or form): { bin_id, command, issued_by, override }

require_once '../includes/auth.php';
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

// Accept JSON body or form POST
$input     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$bin_id    = (int)  ($input['bin_id']    ?? 1);
$command   = trim(   $input['command']   ?? '');
$issued_by = trim(   $input['issued_by'] ?? 'Operator');
$override  = (int)  ($input['override']  ?? 0);

$allowed = ['open', 'close', 'reset'];
if (!in_array($command, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => "Invalid command. Allowed: " . implode(', ', $allowed)]);
    exit;
}

try {
    $stmt = $pdo->prepare("CALL issue_command(:bin_id, :command, :issued_by, :override)");
    $stmt->execute([
        ':bin_id'    => $bin_id,
        ':command'   => $command,
        ':issued_by' => $issued_by,
        ':override'  => $override,
    ]);
    $result = $stmt->fetch();
    echo json_encode([
        'success' => true,
        'log_id'  => $result['log_id'] ?? null,
        'message' => "Command '{$command}' queued for bin {$bin_id}."
    ]);
} catch (PDOException $e) {
    // Stored procedure raises SIGNAL for blocked commands (e.g. bin full, no override)
    http_response_code(409);
    echo json_encode(['error' => $e->getMessage()]);
}
