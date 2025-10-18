<?php
/**
 * Events API Endpoint
 * Handles all event-related API requests
 */

require_once __DIR__ . '/../includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS if needed
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($path);
            break;
        case 'POST':
            handlePostRequest($path);
            break;
        case 'PATCH':
            handlePatchRequest($path);
            break;
        case 'DELETE':
            handleDeleteRequest($path);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($path) {
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    if (empty($path)) {
        // Get all events
        $status = $_GET['status'] ?? null;
        
        $sql = "
            SELECT 
                e.id,
                e.event_name,
                e.event_type,
                e.start_date,
                e.end_date,
                e.status,
                e.expected_guests,
                e.notes,
                e.created_at,
                e.updated_at,
                COUNT(er.room_id) as rooms_count
            FROM events e
            LEFT JOIN event_rooms er ON e.id = er.event_id
            WHERE 1=1
        ";
        
        $params = [];
        if ($status) {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY e.id ORDER BY e.start_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        echo json_encode([
            'ok' => true,
            'data' => $events
        ]);
    } else {
        // Get specific event
        $eventId = (int)$path;
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.event_name,
                e.event_type,
                e.start_date,
                e.end_date,
                e.status,
                e.expected_guests,
                e.notes,
                e.created_at,
                e.updated_at
            FROM events e
            WHERE e.id = ?
        ");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            return;
        }
        
        echo json_encode([
            'ok' => true,
            'data' => $event
        ]);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($path) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO events (event_name, event_type, start_date, end_date, status, expected_guests, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['event_name'] ?? '',
            $input['event_type'] ?? 'conference',
            $input['start_date'] ?? null,
            $input['end_date'] ?? null,
            $input['status'] ?? 'pending',
            $input['expected_guests'] ?? 0,
            $input['notes'] ?? null
        ]);
        
        $eventId = $pdo->lastInsertId();
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'id' => $eventId,
            'message' => 'Event created successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle PATCH requests
 */
function handlePatchRequest($path) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    $eventId = (int)$path;
    
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    try {
        $pdo->beginTransaction();
        
        $fields = [];
        $params = [];
        
        if (isset($input['event_name'])) {
            $fields[] = "event_name = ?";
            $params[] = $input['event_name'];
        }
        if (isset($input['event_type'])) {
            $fields[] = "event_type = ?";
            $params[] = $input['event_type'];
        }
        if (isset($input['start_date'])) {
            $fields[] = "start_date = ?";
            $params[] = $input['start_date'];
        }
        if (isset($input['end_date'])) {
            $fields[] = "end_date = ?";
            $params[] = $input['end_date'];
        }
        if (isset($input['status'])) {
            $fields[] = "status = ?";
            $params[] = $input['status'];
        }
        if (isset($input['expected_guests'])) {
            $fields[] = "expected_guests = ?";
            $params[] = $input['expected_guests'];
        }
        if (isset($input['notes'])) {
            $fields[] = "notes = ?";
            $params[] = $input['notes'];
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $eventId;
        
        $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => 'Event updated successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($path) {
    $eventId = (int)$path;
    
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete event rooms first
        $stmt = $pdo->prepare("DELETE FROM event_rooms WHERE event_id = ?");
        $stmt->execute([$eventId]);
        
        // Delete the event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $success = $stmt->execute([$eventId]);
        
        $pdo->commit();
        
        if ($success) {
            echo json_encode([
                'ok' => true,
                'message' => 'Event deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete event']);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
