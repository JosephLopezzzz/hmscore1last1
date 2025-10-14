<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/housekeeping.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['reservation_id'])) {
    $response['message'] = 'Reservation ID is required';
    echo json_encode($response);
    exit;
}

$reservationId = $_POST['reservation_id'];

error_log("Attempting to check in reservation ID: " . $reservationId);

if (updateReservationStatusSimple($reservationId, 'Checked In')) {
    // Sync room status with rooms overview
    try {
        $pdo = getPdo();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT r.room_id, rm.room_number, g.first_name, g.last_name, rm.status AS room_status FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.id LEFT JOIN guests g ON r.guest_id = g.id WHERE r.id = :id");
            $stmt->execute([':id' => $reservationId]);
            $row = $stmt->fetch();
            if ($row && (int)$row['room_id'] > 0) {
                $guestName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $upd = $pdo->prepare("UPDATE rooms SET status = 'Occupied', guest_name = :guest_name WHERE id = :room_id");
                $upd->execute([':guest_name' => $guestName !== '' ? $guestName : null, ':room_id' => (int)$row['room_id']]);
                // Log status change
                logRoomStatusChange($row['room_number'] ?? '', (string)($row['room_status'] ?? 'Vacant'), 'Occupied', 'Guest checked in', 'Front Desk');
            }
        }
    } catch (Throwable $e) {
        error_log('check-in room sync failed: ' . $e->getMessage());
    }

    $response['success'] = true;
    $response['message'] = 'Guest checked in successfully';
    error_log("Successfully checked in reservation ID: " . $reservationId);
} else {
    $response['message'] = 'Failed to check in guest';
    error_log("Failed to check in reservation ID: " . $reservationId);
}

echo json_encode($response);
?>
