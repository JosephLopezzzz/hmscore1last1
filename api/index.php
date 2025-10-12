<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

function sendJson($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$path = preg_replace('#^/.*/api#', '/api', $path); // normalize if mounted under subdir

// Routes
switch (true) {
  case $path === '/api/health':
    sendJson(['status' => 'ok', 'time' => date('c')]);

  case $path === '/api/guests' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $rows = fetchAllGuests();
    sendJson(['data' => $rows]);

  case $path === '/api/reservations' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $rows = fetchAllReservations();
    sendJson(['data' => $rows]);

  case $path === '/api/rooms' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $pdo = getPdo();
    if (!$pdo) sendJson(['data' => []]);
    try {
      $rows = $pdo->query('SELECT id, number, floor, status FROM rooms ORDER BY floor, number')->fetchAll();
      sendJson(['data' => $rows]);
    } catch (Throwable $e) {
      sendJson(['error' => 'rooms_query_failed'], 500);
    }

  case $path === '/api/rooms' && in_array($_SERVER['REQUEST_METHOD'], ['POST','PATCH','PUT'], true):
    $pdo = getPdo();
    if (!$pdo) sendJson(['error' => 'no_db'], 500);
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
    $number = trim($payload['number'] ?? '');
    $status = $payload['status'] ?? null;
    if ($number === '' || !$status) sendJson(['error' => 'invalid_input'], 422);
    try {
      $stmt = $pdo->prepare('UPDATE rooms SET status=:status WHERE number=:number');
      $stmt->execute([':status' => $status, ':number' => $number]);
      sendJson(['ok' => true]);
    } catch (Throwable $e) {
      sendJson(['error' => 'rooms_update_failed'], 500);
    }

  default:
    sendJson(['error' => 'not_found', 'path' => $path], 404);
}


