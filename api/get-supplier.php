<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('API Access Denied: No user_id in session');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// Get user role from session or database if not in session
$userRole = $_SESSION['role'] ?? '';
if (empty($userRole)) {
    // Try to get role from database if not in session
    $pdo = getPdo();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $userRole = $user['role'];
            $_SESSION['role'] = $userRole; // Cache in session for future requests
        }
    }
}

// Check if user has required role
$allowed_roles = ['admin', 'manager', 'staff'];
if (!in_array(strtolower($userRole), $allowed_roles)) {
    error_log(sprintf('Insufficient permissions for user %s (role: %s)', $_SESSION['user_id'], $userRole));
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

// Get supplier ID from query string
$supplier_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$supplier_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Supplier ID is required']);
    exit;
}

try {
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get supplier details
    $stmt = $pdo->prepare("SELECT * FROM inventory_suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        exit;
    }

    // Get items supplied by this supplier with their stock information
    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.sku, COALESCE(s.current_stock, 0) as current_stock 
        FROM inventory_items i
        LEFT JOIN inventory_stock s ON i.id = s.item_id
        WHERE i.supplier_id = ? AND i.is_active = 1
    ");
    $stmt->execute([$supplier_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $supplier['items'] = $items;
    $supplier['item_count'] = count($items);

    echo json_encode([
        'success' => true,
        'data' => $supplier
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching supplier data: ' . $e->getMessage()
    ]);
}
