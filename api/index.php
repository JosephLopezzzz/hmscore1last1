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

  // Manual sync trigger for schedulers/ops
  case $path === '/api/sync' && $_SERVER['REQUEST_METHOD'] === 'GET':
    // Auth: allow if admin session OR token matches SYNC_TOKEN
    $providedToken = $_GET['token'] ?? ($_SERVER['HTTP_X_API_TOKEN'] ?? '');
    $expectedToken = getenv('SYNC_TOKEN') ?: ($_ENV['SYNC_TOKEN'] ?? ($_SERVER['SYNC_TOKEN'] ?? ''));
    $role = currentUserRole();

    $isAdmin = $role === 'admin';
    $hasValidToken = ($expectedToken !== '' && hash_equals((string)$expectedToken, (string)$providedToken));

    if (!$isAdmin && !$hasValidToken) {
      sendJson(['error' => 'forbidden', 'message' => 'Unauthorized'], 403);
    }

    syncRoomsWithTodaysPendingArrivals();
    sendJson(['ok' => true, 'message' => 'Sync completed']);

  case $path === '/api/guests' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $rows = fetchAllGuests();
    sendJson(['data' => $rows]);

  case $path === '/api/guests' && $_SERVER['REQUEST_METHOD'] === 'POST':
    $pdo = getPdo();
    if (!$pdo) sendJson(['error' => 'no_db'], 500);
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

    // Validate required fields
    $required = ['first_name', 'last_name'];
    foreach ($required as $field) {
      if (empty(trim($payload[$field] ?? ''))) {
        sendJson(['error' => 'invalid_input', 'message' => "$field is required"], 422);
      }
    }

    try {
      $pdo->beginTransaction();

      // Use the database function for consistency
      $guestData = [
        'first_name' => trim($payload['first_name']),
        'last_name' => trim($payload['last_name']),
        'email' => trim($payload['email'] ?? ''),
        'phone' => trim($payload['phone'] ?? ''),
        'address' => trim($payload['address'] ?? ''),
        'city' => trim($payload['city'] ?? ''),
        'country' => trim($payload['country'] ?? ''),
        'id_type' => $payload['id_type'] ?? 'National ID',
        'id_number' => trim($payload['id_number'] ?? ''),
        'date_of_birth' => $payload['date_of_birth'] ?? null,
        'nationality' => trim($payload['nationality'] ?? ''),
        'notes' => trim($payload['notes'] ?? '')
      ];

      $success = createGuest($guestData);

      if (!$success) {
        $pdo->rollBack();
        sendJson(['error' => 'guest_creation_failed'], 500);
      }

      $guestId = $pdo->lastInsertId();
      $pdo->commit();

      sendJson([
        'ok' => true,
        'id' => (int)$guestId,
        'message' => 'Guest created successfully',
        'guest' => [
          'id' => (int)$guestId,
          'first_name' => trim($payload['first_name']),
          'last_name' => trim($payload['last_name']),
          'email' => trim($payload['email'] ?? ''),
          'phone' => trim($payload['phone'] ?? '')
        ]
      ]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      sendJson(['error' => 'guest_creation_failed', 'message' => $e->getMessage()], 500);
    }

  // Get single guest with metrics
  case preg_match('#^/api/guests/(\\d+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET':
    $guestId = (int)$m[1];
    $pdo = getPdo(); if (!$pdo) sendJson(['error' => 'no_db'], 500);
    try {
      $g = $pdo->prepare('SELECT id, first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes FROM guests WHERE id = :id');
      $g->execute([':id' => $guestId]);
      $guest = $g->fetch();
      if (!$guest) sendJson(['error' => 'not_found'], 404);

      $timesStmt = $pdo->prepare("SELECT COUNT(DISTINCT r.id) FROM reservations r WHERE r.guest_id = :id AND r.status = 'Checked In'");
      $timesStmt->execute([':id' => $guestId]);
      $timesCheckedIn = (int)$timesStmt->fetchColumn();

      $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN bt.transaction_type IN ('Room Charge','Service') THEN bt.amount WHEN bt.transaction_type = 'Refund' THEN -bt.amount ELSE 0 END),0)
        FROM billing_transactions bt
        JOIN reservations r ON bt.reservation_id = r.id
        WHERE r.guest_id = :id AND bt.status = 'Paid'");
      $paidStmt->execute([':id' => $guestId]);
      $totalPaid = (float)$paidStmt->fetchColumn();

      sendJson(['data' => $guest, 'metrics' => [ 'timesCheckedIn' => $timesCheckedIn, 'totalPaid' => $totalPaid ]]);
    } catch (Throwable $e) {
      sendJson(['error' => 'guest_query_failed', 'message' => $e->getMessage()], 500);
    }

  // Update guest (partial)
  case preg_match('#^/api/guests/(\\d+)$#', $path, $m) && in_array($_SERVER['REQUEST_METHOD'], ['PATCH','PUT'], true):
    $guestId = (int)$m[1];
    $pdo = getPdo(); if (!$pdo) sendJson(['error' => 'no_db'], 500);
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $allowed = ['first_name','last_name','email','phone','address','city','country','id_type','id_number','date_of_birth','nationality','notes'];
    $update = array_intersect_key($payload, array_flip($allowed));
    if (!$update) sendJson(['error' => 'invalid_input'], 422);
    try {
      $sets = [];
      $params = [ ':id' => $guestId ];
      foreach ($update as $k => $v) { $sets[] = "$k = :$k"; $params[":$k"] = $v; }
      $sql = 'UPDATE guests SET ' . implode(',', $sets) . ' WHERE id = :id';
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      sendJson(['ok' => true]);
    } catch (Throwable $e) {
      sendJson(['error' => 'guest_update_failed', 'message' => $e->getMessage()], 500);
    }


  case $path === '/api/reservations' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $rows = fetchAllReservations();
    sendJson(['data' => $rows]);

  case $path === '/api/reservations' && $_SERVER['REQUEST_METHOD'] === 'POST':
    $pdo = getPdo();
    if (!$pdo) sendJson(['error' => 'no_db'], 500);
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

    // Validate required fields
    $required = ['guest_id', 'room_id', 'check_in_date', 'check_out_date'];
    foreach ($required as $field) {
      if (empty($payload[$field])) {
        sendJson(['error' => 'invalid_input', 'message' => "$field is required"], 422);
      }
    }

    try {
      $pdo->beginTransaction();

      // Check if room is available for the selected dates
      $checkAvailability = $pdo->prepare("
        SELECT COUNT(*) as count FROM reservations
        WHERE room_id = :room_id
        AND status IN ('Pending', 'Checked In', 'Confirmed')
        AND (
          (check_in_date <= :check_out_date AND check_out_date >= :check_in_date)
        )
      ");
      $checkAvailability->execute([
        ':room_id' => $payload['room_id'],
        ':check_in_date' => $payload['check_in_date'],
        ':check_out_date' => $payload['check_out_date']
      ]);
      $conflict = $checkAvailability->fetch();

      if ($conflict['count'] > 0) {
        sendJson(['error' => 'room_not_available', 'message' => 'Room is not available for the selected dates'], 409);
      }

      // Use the database function for consistency
      $reservationData = [
        'guest_id' => $payload['guest_id'],
        'room_id' => $payload['room_id'],
        'check_in_date' => $payload['check_in_date'],
        'check_out_date' => $payload['check_out_date'],
        'status' => $payload['status'] ?? 'Pending',
        'payment_status' => $payload['payment_status'] ?? 'PENDING'
      ];

      $success = createReservation($reservationData);

      if (!$success) {
        sendJson(['error' => 'reservation_creation_failed'], 500);
      }

      $pdo->commit();

      sendJson([
        'ok' => true,
        'message' => 'Reservation created successfully'
      ]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      sendJson(['error' => 'reservation_creation_failed', 'message' => $e->getMessage()], 500);
    }

  case $path === '/api/rooms' && $_SERVER['REQUEST_METHOD'] === 'GET':
    $pdo = getPdo();
    if (!$pdo) sendJson(['data' => []]);
    try {
      // Ensure rooms reflect today's pending arrivals as Reserved
      syncRoomsWithTodaysPendingArrivals();

      $rows = $pdo->query('
        SELECT 
          id, 
          room_number, 
          room_type, 
          floor_number, 
          status, 
          max_guests, 
          rate,
          amenities,
          guest_name,
          maintenance_notes,
          housekeeping_status
        FROM rooms 
        ORDER BY floor_number, room_number
      ')->fetchAll();
      sendJson(['data' => $rows]);
    } catch (Throwable $e) {
      sendJson(['error' => 'rooms_query_failed', 'message' => $e->getMessage()], 500);
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
        $stmt = $pdo->prepare('UPDATE rooms SET status=:status, guest_name=:guest, maintenance_notes=:notes WHERE id=:id');
        $stmt->execute([
          ':status' => $status,
          ':guest' => $guestName,
          ':notes' => $notes,
          ':id' => $roomId
        ]);
      } else {
        $stmt = $pdo->prepare('UPDATE rooms SET status=:status, guest_name=:guest, maintenance_notes=:notes WHERE room_number=:number');
        $stmt->execute([
          ':status' => $status,
          ':guest' => $guestName,
          ':notes' => $notes,
          ':number' => $roomNumber
        ]);
      }
      
      // Auto-create housekeeping task when status changes to Cleaning or Maintenance
      if ($status === 'Cleaning' || $status === 'Maintenance') {
        // Check if task already exists
        $checkTask = $pdo->prepare("SELECT id FROM housekeeping_tasks WHERE room_number = :room_number AND status != 'completed'");
        $checkTask->execute([':room_number' => $roomNumber]);
        
        if (!$checkTask->fetch()) {
          // Create new housekeeping task
          $taskType = $status === 'Maintenance' ? 'maintenance' : 'cleaning';
          $priority = $status === 'Maintenance' ? 'high' : 'normal';
          
          $createTask = $pdo->prepare("
            INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
            VALUES (:room_id, :room_number, :task_type, 'pending', :priority, :guest_name, :notes)
          ");
          $createTask->execute([
            ':room_id' => $roomId,
            ':room_number' => $roomNumber,
            ':task_type' => $taskType,
            ':priority' => $priority,
            ':guest_name' => $guestName,
            ':notes' => $notes ?? "Room needs $taskType"
          ]);
        }
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


