<?php
// Test authentication status
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

initSession();
$role = $_SESSION['user_role'] ?? null;

echo json_encode([
    'authenticated' => !empty($role),
    'role' => $role,
    'session_id' => session_id(),
    'message' => $role ? 'User is authenticated' : 'User is not authenticated'
]);
?>
