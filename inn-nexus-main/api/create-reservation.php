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
        'checkOutDate', 'checkOutTime', 'roomType',
        'totalAmount'
    ];
    
    // Room number will be assigned automatically
    
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
        // Format dates for database
        $checkInDate = $input['checkInDate'] . ' ' . $input['checkInTime'] . ':00';
        $checkOutDate = $input['checkOutDate'] . ' ' . $input['checkOutTime'] . ':00';
        
        // Debug: Log the date values
        error_log("Check-in Date: " . $checkInDate);
        error_log("Check-out Date: " . $checkOutDate);
        
        // Find first available room of the selected type
        $stmt = $pdo->prepare("
            SELECT id, room_number, room_type, rate as price_per_night 
            FROM rooms 
            WHERE room_type = ? AND status = 'Vacant'
            ORDER BY room_number ASC
            LIMIT 1
        
        ");
        $stmt->execute([$input['roomType']]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            throw new Exception('No available rooms of the selected type. Please try a different room type.');
        }
        
        // Use the found room
        $input['roomNumber'] = $room['room_number'];

        // Room capacity check removed

        // Check for overlapping reservations for the selected room
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reservations r
            JOIN rooms rm ON r.room_number = rm.room_number
            WHERE rm.room_type = ? 
            AND r.status NOT IN ('Cancelled', 'Checked Out')
            AND (
                (r.check_in_date <= ? AND r.check_out_date >= ?)
                OR (r.check_in_date <= ? AND r.check_out_date >= ?)
                OR (r.check_in_date >= ? AND r.check_out_date <= ?)
            )
            AND rm.id = ?
        
        
        ");
        
        $stmt->execute([
            $input['roomType'],
$checkInDate, $checkInDate,
            $checkOutDate, $checkOutDate,
            $checkInDate, $checkOutDate,
            $room['id']
        ]);
        
        if ($stmt->fetch()['count'] > 0) {
            // If the room is already booked, find another available room
            $pdo->rollBack();
            throw new Exception('The selected room is no longer available. Please try again.');
        }

        // Get guest ID from input
        if (empty($input['guestId'])) {
            throw new Exception('Guest ID is required');
        }
        $guestId = $input['guestId'];
        
        // Verify guest exists
        $stmt = $pdo->prepare("SELECT id FROM guests WHERE id = ?");
        $stmt->execute([$guestId]);
        if (!$stmt->fetch()) {
            throw new Exception('Invalid guest selected');
        }

        // Create reservation
        $stmt = $pdo->prepare("
            INSERT INTO reservations (
                guest_id, room_id, room_number, 
                check_in_date, check_out_date, status, 
                total_amount, paid_amount, balance, 
                special_requests, notes,
                invoice_method, payment_source, created_at, updated_at,
                room_type
            ) VALUES (
                :guest_id, :room_id, :room_number,
                :check_in_date, :check_out_date, 'Confirmed',
                :total_amount, 0, :total_amount,
                :special_requests, :notes,
                :invoice_method, :payment_source, NOW(), NOW(),
                :room_type
            )
        
        
        ");
        
        $stmt->execute([
            ':guest_id' => $guestId,
            ':room_id' => $room['id'],
            ':room_number' => $room['room_number'],
            ':check_in_date' => $checkInDate,
            ':check_out_date' => $checkOutDate,
            ':total_amount' => (float)$input['totalAmount'],
            ':special_requests' => $input['specialRequests'] ?? '',
            ':notes' => $input['notes'] ?? '',
            // Use first selected invoice method or default to 'print'
            ':invoice_method' => !empty($input['invoiceMethod']) ? explode(',', $input['invoiceMethod'])[0] : 'print',
            ':payment_source' => $input['paymentSource'] ?? 'cash',
            ':room_type' => $input['roomType']
        ]);
        
        $reservationId = $pdo->lastInsertId();

        // Update room status to reserved
        $stmt = $pdo->prepare("
            UPDATE rooms 
            SET status = 'Reserved', 
                updated_at = NOW() 
            WHERE id = ?
        
        
        ");
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
