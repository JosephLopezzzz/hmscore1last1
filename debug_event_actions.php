<?php
// Debug version of event_actions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/auth.php';
    
    echo json_encode(['success' => true, 'message' => 'Debug: Files loaded successfully']);
    exit;
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Debug Error: ' . $e->getMessage()]);
    exit;
}
?>
