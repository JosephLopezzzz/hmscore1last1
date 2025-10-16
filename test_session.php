<?php
// Test session status
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_role' => $_SESSION['user_role'] ?? 'not_set',
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
]);
?>
