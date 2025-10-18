<?php
/**
 * Housekeeping API Endpoint
 * Handles all housekeeping-related API requests
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/housekeeping.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS if needed
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Parse the path from the URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $path = str_replace($scriptName, '', $requestUri);
    $path = trim($path, '/');
    
    
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
    if (empty($path)) {
        // Get all housekeeping tasks
        $status = $_GET['status'] ?? null;
        $tasks = fetchHousekeepingTasks($status);
        $stats = getHousekeepingStats();
        
        echo json_encode([
            'ok' => true,
            'data' => $tasks,
            'stats' => $stats
        ]);
    } else {
        // Get specific task
        $taskId = (int)$path;
        $pdo = getPdo();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                ht.id,
                ht.room_id,
                ht.room_number,
                ht.task_type,
                ht.status,
                ht.priority,
                ht.assigned_to,
                ht.guest_name,
                ht.notes,
                ht.started_at,
                ht.completed_at,
                ht.created_at,
                ht.updated_at,
                r.room_type,
                r.floor_number,
                r.status as room_status
            FROM housekeeping_tasks ht
            LEFT JOIN rooms r ON ht.room_id = r.id
            WHERE ht.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
            return;
        }
        
        echo json_encode([
            'ok' => true,
            'data' => $task
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
    
    // Create new housekeeping task
    $taskId = createHousekeepingTask($input);
    
    if ($taskId) {
        echo json_encode([
            'ok' => true,
            'id' => $taskId,
            'message' => 'Task created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create task']);
    }
}

/**
 * Handle PATCH requests
 */
function handlePatchRequest($path) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) {
        $jsonError = json_last_error_msg();
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input: ' . $jsonError]);
        return;
    }
    
    $taskId = (int)$path;
    $status = $input['status'] ?? null;
    $assignedTo = $input['assignedTo'] ?? null;
    
    if (!$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Status is required']);
        return;
    }
    
    $success = updateHousekeepingTask($taskId, $status, $assignedTo);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'message' => 'Task updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update task']);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($path) {
    $taskId = (int)$path;
    
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    $stmt = $pdo->prepare("DELETE FROM housekeeping_tasks WHERE id = ?");
    $success = $stmt->execute([$taskId]);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'message' => 'Task deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete task']);
    }
}
?>
