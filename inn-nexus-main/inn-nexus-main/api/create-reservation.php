<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    $required = [
        'firstName', 'lastName', 'checkInDate', 'checkInTime', 
        'checkOutDate', 'checkOutTime', 'roomType', 'roomNumber',
        'occupancy', 'totalAmount'
    ];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $pdo = getPdo();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Format dates
        $checkIn = $input['checkInDate'] . ' ' . $input['checkInTime'] . ':00';
        $checkOut = $input['checkOutDate'] . ' ' . $input['checkOutTime'] . ':00';
        
        // Get room ID and verify availability
        $stmt = $pdo->prepare("
            SELECT id, room_number, room_type, price_per_night, max_occupancy 
            FROM rooms 
            WHERE room_number = ? AND status = 'available'
        ");
        $stmt->execute([$input['roomNumber']]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            throw new Exception('Selected room is not available');
        }

        // Verify room type matches
        if ($room['room_type'] !== $input['roomType']) {
            throw new Exception('Room type does not match selection');
        }

        // Verify occupancy
        if ($input['occupancy'] > $room['max_occupancy']) {
            throw new Exception('Occupancy exceeds room capacity');
        }

        // Check for overlapping reservations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE room_number = ? 
            AND status NOT IN ('cancelled', 'completed')
            AND (
                (check_in_date <= ? AND check_out_date >= ?)
                OR (check_in_date <= ? AND check_out_date >= ?)
                OR (check_in_date >= ? AND check_out_date <= ?)
            )
        ");
        
        $stmt->execute([
            $input['roomNumber'],
            $checkIn, $checkIn,
            $checkOut, $checkOut,
            $checkIn, $checkOut
        ]);
        
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception('Selected dates are not available for this room');
        }

        // Create guest record
        $stmt = $pdo->prepare("
            INSERT INTO guests (
                first_name, last_name, email, phone, birthdate, 
                address, city, country, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, '', '', '', NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['firstName'],
            $input['lastName'],
            $input['email'] ?? null,
            $input['phone'] ?? null,
            !empty($input['birthdate']) ? $input['birthdate'] : null
        ]);
        
        $guestId = $pdo->lastInsertId();

        // Create reservation
        $stmt = $pdo->prepare("
            INSERT INTO reservations (
                guest_id, room_id, room_number, guest_name, 
                check_in_date, check_out_date, status, 
                total_amount, paid_amount, balance, 
                occupancy, special_requests, notes,
                invoice_method, payment_source, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, 0, ?, ?, ?, '', ?, ?, NOW(), NOW())
        ");
        
        $guestName = trim($input['firstName'] . ' ' . $input['lastName']);
        $balance = (float)$input['totalAmount'];
        
        $stmt->execute([
            $guestId,
            $room['id'],
            $input['roomNumber'],
            $guestName,
            $checkIn,
            $checkOut,
            $input['totalAmount'],
            $balance,
            $input['occupancy'],
            $input['specialRequests'] ?? '',
            $input['invoiceMethod'] ?? 'print',
            $input['paymentSource'] ?? 'cash'
        ]);
        
        $reservationId = $pdo->lastInsertId();

        // Update room status
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmt->execute([$room['id']]);

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'reservationId' => $reservationId,
            'message' => 'Reservation created successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
