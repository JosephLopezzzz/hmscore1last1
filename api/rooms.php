<?php
/**
 * Rooms API Endpoint
 * Handles all room-related API requests
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
        case 'PATCH':
            handlePatchRequest($path);
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
        // Get all rooms
        $stmt = $pdo->query("
            SELECT 
                r.id,
                r.room_number,
                r.room_type,
                r.floor_number,
                r.status,
                r.guest_name,
                r.housekeeping_status,
                r.last_cleaned,
                r.maintenance_notes,
                r.updated_at,
                COUNT(ht.id) as task_count
            FROM rooms r
            LEFT JOIN housekeeping_tasks ht ON r.id = ht.room_id AND ht.status != 'completed'
            GROUP BY r.id
            ORDER BY r.floor_number, r.room_number
        ");
        $rooms = $stmt->fetchAll();
        
        echo json_encode([
            'ok' => true,
            'data' => $rooms
        ]);
    } else {
        // Get specific room
        $roomId = (int)$path;
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.room_number,
                r.room_type,
                r.floor_number,
                r.status,
                r.guest_name,
                r.housekeeping_status,
                r.last_cleaned,
                r.maintenance_notes,
                r.updated_at
            FROM rooms r
            WHERE r.id = ?
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        
        if (!$room) {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
            return;
        }
        
        echo json_encode([
            'ok' => true,
            'data' => $room
        ]);
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
    
    $roomId = (int)$path;
    $status = $input['status'] ?? null;
    $guestName = $input['guestName'] ?? null;
    $notes = $input['notes'] ?? null;
    
    if (!$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Status is required']);
        return;
    }
    
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update room status
        $stmt = $pdo->prepare("
            UPDATE rooms 
            SET status = ?, guest_name = ?, maintenance_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $guestName, $notes, $roomId]);
        
        // Log the status change
        if (function_exists('logRoomStatusChange')) {
            $roomNumber = $pdo->query("SELECT room_number FROM rooms WHERE id = $roomId")->fetchColumn();
            logRoomStatusChange($roomNumber, 'Previous Status', $status, 'Status updated via API', 'System');
        }
        
        $pdo->commit();
        
        echo json_encode([
            'ok' => true,
            'message' => 'Room status updated successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
