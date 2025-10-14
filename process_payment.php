<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/includes/db.php';

$pdo = getPdo();

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

$reservation_id = $_POST['folio_id'] ?? null;
$payment_method = $_POST['method'] ?? null;
$amount_received = $_POST['amount'] ?? null;
$notes = $_POST['notes'] ?? null;

if (!$reservation_id || !$payment_method || !$amount_received) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: reservation_id, payment_method, and amount_received are required'
    ]);
    exit;
}

try {
    // Fetch reservation details
    $stmt = $pdo->prepare("
        SELECT r.id, r.guest_id, r.room_id, r.status as reservation_status,
               CONCAT(g.first_name, ' ', g.last_name) as guest_name,
               rm.room_number, rm.rate as room_rate
        FROM reservations r
        JOIN guests g ON r.guest_id = g.id
        JOIN rooms rm ON r.room_id = rm.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode([
            'success' => false,
            'message' => 'Reservation not found'
        ]);
        exit;
    }

    // Validate payment amount
    $amountFloat = floatval($amount_received);
    if ($amountFloat <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment amount must be greater than zero'
        ]);
        exit;
    }

    // Insert billing transaction
    $stmt = $pdo->prepare("
        INSERT INTO billing_transactions (
            reservation_id, transaction_type, amount, payment_method,
            status, notes, transaction_date
        ) VALUES (?, 'Payment', ?, ?, 'Paid', ?, NOW())
    ");

    $result = $stmt->execute([
        $reservation_id,
        $amountFloat,
        $payment_method,
        $notes
    ]);

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process payment'
        ]);
        exit;
    }

    // Update reservation status to Checked In
    $updateStmt = $pdo->prepare("UPDATE reservations SET status = 'Checked In', updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$reservation_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'transaction_id' => $pdo->lastInsertId(),
            'reservation_id' => $reservation_id,
            'amount_paid' => $amountFloat
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
?>
