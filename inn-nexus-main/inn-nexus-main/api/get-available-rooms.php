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

    // Get query parameters
    $roomType = $_GET['type'] ?? '';
    $floor = $_GET['floor'] ?? null;
    $checkIn = $_GET['checkIn'] ?? '';
    $checkOut = $_GET['checkOut'] ?? '';
    $showAll = isset($_GET['showAll']) && $_GET['showAll'] === '1';

    // Validate required parameters
    if (empty($roomType) || empty($checkIn) || empty($checkOut)) {
        throw new Exception('Missing required parameters');
    }
    
    // Convert floor to integer if provided
    $floor = $floor !== null ? (int)$floor : null;

    // Determine floors based on room type
    $floors = [];
    switch ($roomType) {
        case 'general':
            $floors = [1, 2, 3];
            break;
        case 'deluxe':
            $floors = [4];
            break;
        case 'executive':
        case 'luxury':
            $floors = [5];
            break;
        default:
            throw new Exception('Invalid room type');
    }

    // Convert dates to proper format for SQL
    $checkInDate = date('Y-m-d', strtotime($checkIn));
    $checkOutDate = date('Y-m-d', strtotime($checkOut));

    // Build the base query
    $query = "
        SELECT 
            r.id,
            r.room_number as number,
            r.floor_number as floor,
            r.room_type as type,
            CASE 
                WHEN r.status = 'available' AND EXISTS (
                    SELECT 1 
                    FROM reservations res 
                    WHERE res.room_id = r.id 
                    AND res.status NOT IN ('cancelled', 'completed')
                    AND (
                        (res.check_in_date <= :checkOut AND res.check_out_date >= :checkIn)
                        OR (res.check_in_date <= :checkOut AND res.check_out_date >= :checkIn)
                        OR (res.check_in_date >= :checkIn AND res.check_out_date <= :checkOut)
                    )
                    LIMIT 1
                ) THEN 'occupied'
                ELSE r.status
            END as status,
            r.price_per_night as rate,
            r.max_occupancy
        FROM 
            rooms r
        WHERE 
            r.room_type = :roomType
    ";
    
    // Add floor filter if specified
    if ($floor !== null) {
        $query .= " AND r.floor_number = :floor";
    }
    
    // Only show available rooms if not showing all
    if (!$showAll) {
        $query .= " AND r.status = 'available'";
    }
    
    // Order by room number
    $query .= " ORDER BY r.room_number";

    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    
    // Bind parameters
    $params = [
        ':roomType' => $roomType,
        ':checkIn' => $checkIn,
        ':checkOut' => $checkOut
    ];
    
    if ($floor !== null) {
        $params[':floor'] = $floor;
    }
    
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process the results
    $availableRooms = array_map(function($room) {
        return [
            'id' => $room['id'],
            'number' => $room['number'],
            'floor' => $room['floor'],
            'type' => $room['type'],
            'status' => $room['status'],
            'rate' => (float)$room['rate'],
            'max_occupancy' => (int)$room['max_occupancy']
        ];
    }, $rooms);

    // Return the available rooms
    echo json_encode($availableRooms);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
