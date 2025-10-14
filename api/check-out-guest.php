<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['reservation_id'])) {
    $response['message'] = 'Reservation ID is required';
    echo json_encode($response);
    exit;
}

$reservationId = $_POST['reservation_id'];

error_log("Attempting to check out reservation ID: " . $reservationId);

if (updateReservationStatusSimple($reservationId, 'Checked Out')) {
    $response['success'] = true;
    $response['message'] = 'Guest checked out successfully';
    error_log("Successfully checked out reservation ID: " . $reservationId);
} else {
    $response['message'] = 'Failed to check out guest';
    error_log("Failed to check out reservation ID: " . $reservationId);
}

echo json_encode($response);
?>
