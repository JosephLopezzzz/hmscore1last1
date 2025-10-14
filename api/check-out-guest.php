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

error_log("Attempting to check out reservation ID: " . $reservationId);

if (updateReservationStatusSimple($reservationId, 'Checked Out')) {
    // Sync room status with rooms overview and create housekeeping task
    try {
        $pdo = getPdo();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT r.room_id, rm.room_number, rm.status AS room_status FROM reservations r LEFT JOIN rooms rm ON r.room_id = rm.id WHERE r.id = :id");
            $stmt->execute([':id' => $reservationId]);
            $row = $stmt->fetch();
            if ($row && (int)$row['room_id'] > 0) {
                // Mark room for cleaning and clear guest
                $upd = $pdo->prepare("UPDATE rooms SET status = 'Cleaning', guest_name = NULL, housekeeping_status = 'dirty' WHERE id = :room_id");
                $upd->execute([':room_id' => (int)$row['room_id']]);
                // Log status change
                logRoomStatusChange($row['room_number'] ?? '', (string)($row['room_status'] ?? 'Occupied'), 'Cleaning', 'Guest checked out', 'Front Desk');
                // Ensure a housekeeping task exists
                $checkTask = $pdo->prepare("SELECT id FROM housekeeping_tasks WHERE room_id = :room_id AND status != 'completed' LIMIT 1");
                $checkTask->execute([':room_id' => (int)$row['room_id']]);
                if (!$checkTask->fetch()) {
                    createHousekeepingTask([
                        'room_id' => (int)$row['room_id'],
                        'room_number' => $row['room_number'] ?? '',
                        'task_type' => 'cleaning',
                        'status' => 'pending',
                        'priority' => 'normal',
                        'notes' => 'Turnover after checkout'
                    ]);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('check-out room sync failed: ' . $e->getMessage());
    }

    $response['success'] = true;
    $response['message'] = 'Guest checked out successfully';
    error_log("Successfully checked out reservation ID: " . $reservationId);
} else {
    $response['message'] = 'Failed to check out guest';
    error_log("Failed to check out reservation ID: " . $reservationId);
}

echo json_encode($response);
?>
