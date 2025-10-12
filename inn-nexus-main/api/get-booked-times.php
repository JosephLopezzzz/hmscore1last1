<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['room_type']) || !isset($_GET['date'])) {
        throw new Exception('Room type and date are required');
    }

    $roomType = $_GET['room_type'];
    $date = $_GET['date'];
    $pdo = getPdo();

    // Get all reservations for the selected room type and date
    $stmt = $pdo->prepare("
        SELECT 
            TIME(checkin) as checkin_time,
            TIME(checkout) as checkout_time
        FROM reservations r
        JOIN rooms rm ON r.room = rm.room_number
        WHERE rm.room_type = :room_type
        AND DATE(r.checkin) = :date
        AND r.status != 'cancelled'
        ORDER BY checkin_time
    ");

    $stmt->execute([
        ':room_type' => $roomType,
        ':date' => $date
    ]);

    $bookedSlots = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bookedSlots[] = [
            'start' => $row['checkin_time'],
            'end' => $row['checkout_time']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'booked_slots' => $bookedSlots
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
