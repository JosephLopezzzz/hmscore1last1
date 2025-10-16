<?php
// Test script to debug room loading
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/db.php';
    
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Test 1: Check if rooms table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'rooms'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Rooms table does not exist']);
        exit;
    }
    
    // Test 2: Get room count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM rooms");
    $count = $stmt->fetch()['count'];
    
    // Test 3: Get sample rooms
    $stmt = $pdo->prepare("SELECT id, room_number, room_type, status FROM rooms LIMIT 5");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Rooms table exists',
        'count' => $count,
        'sample_rooms' => $rooms
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
