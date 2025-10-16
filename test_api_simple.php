<?php
// Simple API test without authentication
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/db.php';
    
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Test rooms query
    $stmt = $pdo->prepare("SELECT id, room_number, room_type, status FROM rooms ORDER BY room_number");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'message' => 'API working',
        'room_count' => count($rooms),
        'rooms' => $rooms
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
