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
require_once __DIR__ . '/../includes/housekeeping.php';

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
    $roomNumber = trim($payload['number'] ?? $payload['roomNumber'] ?? '');
    $roomId = (int)($payload['id'] ?? 0);
    $status = $payload['status'] ?? null;
    $guestName = $payload['guestName'] ?? $payload['guest_name'] ?? null;
    $notes = $payload['notes'] ?? null;
    
    if (($roomNumber === '' && $roomId === 0) || !$status) {
      sendJson(['error' => 'invalid_input'], 422);
    }
    
    try {
      $pdo->beginTransaction();
      
      // Get old status for logging
      if ($roomId > 0) {
        $oldStatus = $pdo->prepare('SELECT status, room_number FROM rooms WHERE id = :id');
        $oldStatus->execute([':id' => $roomId]);
        $oldData = $oldStatus->fetch();
        $roomNumber = $oldData['room_number'] ?? $roomNumber;
      } else {
        $oldStatus = $pdo->prepare('SELECT status FROM rooms WHERE room_number = :number');
        $oldStatus->execute([':number' => $roomNumber]);
        $oldData = $oldStatus->fetch();
      }
      
      // Update room
      if ($roomId > 0) {
        $stmt = $pdo->prepare('UPDATE rooms SET status=:status, guest_name=:guest, maintenance_notes=:notes, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
          ':status' => $status,
          ':guest' => $guestName,
          ':notes' => $notes,
          ':id' => $roomId
        ]);
      } else {
        $stmt = $pdo->prepare('UPDATE rooms SET status=:status, guest_name=:guest, maintenance_notes=:notes, updated_at=NOW() WHERE room_number=:number');
        $stmt->execute([
          ':status' => $status,
          ':guest' => $guestName,
          ':notes' => $notes,
          ':number' => $roomNumber
        ]);
      }
      
      // Log the change
      if ($oldData && $oldData['status'] !== $status) {
        logRoomStatusChange($roomNumber, $oldData['status'], $status, $notes ?? 'Status updated via API', 'API');
      }
      
      $pdo->commit();
      sendJson(['ok' => true, 'roomNumber' => $roomNumber]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      sendJson(['error' => 'rooms_update_failed', 'message' => $e->getMessage()], 500);
    }

  // Get housekeeping tasks
  case $path === '/api/housekeeping' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $status = $_GET['status'] ?? null;
    $tasks = fetchHousekeepingTasks($status);
    $stats = getHousekeepingStats();
    sendJson(['data' => $tasks, 'stats' => $stats]);

  // Update housekeeping task
  case preg_match('#^/api/housekeeping/(\d+)$#', $path, $matches) && in_array($_SERVER['REQUEST_METHOD'], ['PATCH','PUT'], true):
    $taskId = (int)$matches[1];
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $status = $payload['status'] ?? null;
    $assignedTo = $payload['assignedTo'] ?? $payload['assigned_to'] ?? null;
    
    if (!$status) sendJson(['error' => 'invalid_input'], 422);
    
    $result = updateHousekeepingTask($taskId, $status, $assignedTo);
    if ($result) {
      sendJson(['ok' => true, 'message' => 'Task updated successfully']);
    } else {
      sendJson(['error' => 'task_update_failed'], 500);
    }

  // Create housekeeping task
  case $path === '/api/housekeeping' && $_SERVER['REQUEST_METHOD'] === 'POST':
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $taskId = createHousekeepingTask($payload);
    
    if ($taskId) {
      sendJson(['ok' => true, 'id' => $taskId, 'message' => 'Task created successfully']);
    } else {
      sendJson(['error' => 'task_creation_failed'], 500);
    }

  // Get rooms needing housekeeping
  case $path === '/api/rooms/housekeeping' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $rooms = fetchRoomsNeedingHousekeeping();
    sendJson(['data' => $rooms]);

  default:
    sendJson(['error' => 'not_found', 'path' => $path], 404);
}


