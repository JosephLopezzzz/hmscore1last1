<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $query = $_GET['q'] ?? '';
    
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }

    // Prepare the search query
    $searchTerm = "%$query%";
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone
        FROM guests
        WHERE first_name LIKE ? 
           OR last_name LIKE ?
           OR email LIKE ?
           OR phone LIKE ?
        ORDER BY last_name, first_name
        LIMIT 10
    ");

    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results as JSON
    echo json_encode($guests);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
