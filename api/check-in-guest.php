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

error_log("Attempting to check in reservation ID: " . $reservationId);

if (updateReservationStatusSimple($reservationId, 'Checked In')) {
    $response['success'] = true;
    $response['message'] = 'Guest checked in successfully';
    error_log("Successfully checked in reservation ID: " . $reservationId);
} else {
    $response['message'] = 'Failed to check in guest';
    error_log("Failed to check in reservation ID: " . $reservationId);
}

echo json_encode($response);
?>
