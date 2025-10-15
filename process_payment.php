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

    // Count paid transactions for this guest to determine discount
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as paid_count
        FROM billing_transactions bt
        JOIN reservations r ON bt.reservation_id = r.id
        WHERE r.guest_id = ? AND bt.status = 'Paid'
    ");
    $countStmt->execute([$reservation['guest_id']]);
    $paidCount = (int)$countStmt->fetch()['paid_count'];

    // Determine discount percentage based on paid transaction count (new tier system)
    if ($paidCount >= 100) {
        $discountPercentage = 0.40; // 40% discount for PLATINUM (100+ transactions)
        $tier = 'PLATINUM';
    } elseif ($paidCount >= 50) {
        $discountPercentage = 0.30; // 30% discount for GOLD (50-99 transactions)
        $tier = 'GOLD';
    } elseif ($paidCount >= 20) {
        $discountPercentage = 0.20; // 20% discount for SILVER (20-49 transactions)
        $tier = 'SILVER';
    } else {
        $discountPercentage = 0.0; // No discount for NORMAL (0-19 transactions)
        $tier = 'NORMAL';
    }

    // Calculate discount amount and discounted balance
    $roomRate = (float)$reservation['room_rate'];
    $discountAmount = $roomRate * $discountPercentage;
    $discountedBalance = $roomRate - $discountAmount;

    // Calculate change (payment_amount - discounted_balance)
    $change = $amountFloat - $discountedBalance;

    // Insert billing transaction with discounted balance
    $stmt = $pdo->prepare("
        INSERT INTO billing_transactions (
            reservation_id, transaction_type, amount, payment_amount, balance, `change`,
            payment_method, status, notes, transaction_date
        ) VALUES (?, 'Payment', ?, ?, ?, ?, ?, 'Paid', ?, NOW())
    ");

    $discountNote = $discountPercentage > 0 ? " | Discount: {$discountPercentage}% (₱{$discountAmount})" : '';
    $notesWithDiscount = $notes . $discountNote;

    $result = $stmt->execute([
        $reservation_id,
        $roomRate, // Original amount
        $amountFloat, // payment_amount = amount received
        $discountedBalance, // balance after discount
        max(0, $change), // change (ensure non-negative)
        $payment_method,
        $notesWithDiscount
    ]);

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process payment'
        ]);
        exit;
    }

    // Update reservation payment status to fully paid (do NOT change check-in status here)
    $updateStmt = $pdo->prepare("UPDATE reservations SET payment_status = 'FULLY PAID', updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$reservation_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'transaction_id' => $pdo->lastInsertId(),
            'reservation_id' => $reservation_id,
            'amount_paid' => $amountFloat,
            'original_amount' => $roomRate,
            'discount_amount' => $discountAmount,
            'discounted_balance' => $discountedBalance,
            'change' => max(0, $change),
            'paid_transactions_count' => $paidCount,
            'discount_percentage' => $discountPercentage * 100
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
?>
