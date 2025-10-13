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

    // Get room type from query parameters
    $roomType = $_GET['type'] ?? '';
    
    // Validate required parameter
    if (empty($roomType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Room type is required']);
        exit;
    }
    
    // Get available rooms of the specified type that are currently vacant
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            room_number,
            floor_number,
            rate,
            max_guests,
            status
        FROM rooms 
        WHERE room_type = :roomType 
        AND status = 'Vacant'
        ORDER BY room_number ASC
    
    ");
    
    $stmt->execute([':roomType' => $roomType]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the list of available rooms
    echo json_encode([
        'success' => true,
        'data' => array_map(function($room) {
            return [
                'id' => $room['id'],
                'room_number' => $room['room_number'],
                'floor_number' => $room['floor_number'],
                'rate' => (float)$room['rate'],
                'max_guests' => (int)$room['max_guests']
            ];
        }, $rooms)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
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
    
    // Add floor filter if specified, otherwise use the default floors for the room type
    if ($floor !== null) {
        $query .= " AND r.floor_number = :floor";
    } else {
        // If no floor specified, only show rooms on the default floors for this room type
        $floors = $roomTypeFloors[$roomType];
        $placeholders = rtrim(str_repeat('?,', count($floors)), ',');
        $query .= " AND r.floor_number IN ($placeholders)";
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
        ':checkIn' => $checkInDate,
        ':checkOut' => $checkOutDate
    ];
    
    if ($floor !== null) {
        $params[':floor'] = $floor;
    } else {
        // Bind floor parameters if using IN clause
        foreach ($floors as $i => $f) {
            $params["floor$i"] = $f;
        }
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
