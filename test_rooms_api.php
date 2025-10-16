<?php
// Test rooms API without authentication
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/db.php';
    
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Get rooms (same query as in event_actions.php)
    $stmt = $pdo->prepare("SELECT id, room_number, room_type, status FROM rooms ORDER BY room_number");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $rooms]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
