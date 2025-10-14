<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/includes/db.php';

// Error logging function for debugging
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/payment_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $userInfo = $_SESSION['user_email'] ?? 'Unknown';
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';

    $logEntry = "[$timestamp] User: $userInfo | Error: $message$contextStr" . PHP_EOL;
    error_log($logEntry, 3, $logFile);
}

$pdo = getPdo();

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

$processedBy = $_SESSION['user_email'] ?? 'System';

$folio_id        = $_POST['folio_id'] ?? null;
$payment_method  = $_POST['method'] ?? null;
$amount_received = $_POST['amount'] ?? null;
$reference_no    = $_POST['notes'] ?? null;

if (!$folio_id || !$payment_method || !$amount_received) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: folio_id, payment_method, and amount_received are required'
    ]);
    exit;
}

try {
    // Check if reservation_id exists in reservations table to avoid foreign key constraint error
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? LIMIT 1");
    $stmt->execute([$folio_id]);
    $reservationExists = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if folio_id exists in billing_transactions as reservation_id
    $stmt = $pdo->prepare("SELECT id, guest_name, room_number, amount as balance, status FROM billing_transactions WHERE reservation_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$folio_id]);
    $existingTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingTransaction) {
        try {
            // Update existing transaction to mark as paid
            $stmt = $pdo->prepare("
                UPDATE billing_transactions
                SET status = 'Paid', payment_method = ?, notes = CONCAT(COALESCE(notes, ''), ?), updated_at = NOW()
                WHERE id = ?
            ");

            $paymentNote = $reference_no ? " | Payment: $payment_method - $$amount_received" : " | Payment: $payment_method";

            if (!$stmt->execute([$payment_method, $paymentNote, $existingTransaction['id']])) {
                throw new Exception('Failed to execute update statement');
            }

            // STRICT CHECK: Verify that at least one row was actually updated
            $affectedRows = $stmt->rowCount();
            if ($affectedRows === 0) {
                throw new Exception('No records were updated - transaction may not exist or data may be invalid');
            }

            $newBalance = max(0, floatval($existingTransaction['balance']) - floatval($amount_received));

            echo json_encode([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'new_balance' => $newBalance,
                    'transaction_id' => $existingTransaction['id'],
                    'rows_affected' => $affectedRows
                ]
            ]);
        } catch (PDOException $e) {
            // Handle database-specific errors for UPDATE operation
            logError('Database error during payment update', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'folio_id' => $folio_id,
                'payment_method' => $payment_method,
                'amount' => $amount_received
            ]);
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Invalid transaction data or constraint violation'
                ]);
            } elseif ($e->getCode() == '42S22') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Database table structure issue'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Database error during update - ' . $e->getMessage()
                ]);
            }
            exit;
        } catch (Exception $e) {
            // Handle general errors for UPDATE operation
            logError('General error during payment update', [
                'error_message' => $e->getMessage(),
                'folio_id' => $folio_id,
                'payment_method' => $payment_method,
                'amount' => $amount_received
            ]);
            echo json_encode([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ]);
            exit;
        }
    } else {
        try {
            // Create new payment transaction record
            $stmt = $pdo->prepare("
                INSERT INTO billing_transactions (
                    reservation_id, transaction_type, amount, payment_method,
                    status, notes, transaction_date
                ) VALUES (?, 'Payment', ?, ?, 'Paid', ?, NOW())
            ");

            // STRICT VALIDATION: Validate all input data before INSERT operation
            // Validate amount_received
            $amountFloat = floatval($amount_received);
            if ($amountFloat <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }
            if ($amountFloat > 999999.99) {
                throw new Exception('Payment amount cannot exceed $999,999.99');
            }
            // Check for more than 2 decimal places
            if (preg_match('/\.\d{3,}$/', $amount_received)) {
                throw new Exception('Payment amount cannot have more than 2 decimal places');
            }

            // Validate payment_method
            $allowedPaymentMethods = ['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Check', 'Online Payment', 'Other'];
            if (!in_array($payment_method, $allowedPaymentMethods)) {
                throw new Exception('Invalid payment method selected. Allowed methods: ' . implode(', ', $allowedPaymentMethods));
            }

            // Validate reference_no (notes)
            if (strlen($reference_no) > 255) {
                throw new Exception('Reference notes cannot exceed 255 characters');
            }
            // Sanitize reference_no to prevent injection
            $reference_no = htmlspecialchars(strip_tags($reference_no));


            if (!$stmt->execute([
                $folio_id,
                floatval($amount_received),
                $payment_method,
                $reference_no
            ])) {
                throw new Exception('Failed to execute insert statement');
            }

            // STRICT CHECK: Verify that the insert actually created a record
            $newTransactionId = $pdo->lastInsertId();
            if (!$newTransactionId || $newTransactionId === 0) {
                throw new Exception('Failed to create payment record - no ID returned');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'new_balance' => 0,
                    'transaction_id' => $newTransactionId,
                    'insert_id' => $newTransactionId
                ]
            ]);
        } catch (PDOException $e) {
            // Handle database-specific errors for INSERT operation with detailed context
            logError('Database error during payment insert', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'folio_id' => $folio_id,
                'payment_method' => $payment_method,
                'amount' => $amount_received
            ]);
            if ($e->getCode() == '23000') {
                // Integrity constraint violation (duplicate key, foreign key constraint, etc.)
                if (strpos($e->getMessage(), 'reservation_id') !== false) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Payment processing failed: Invalid reservation ID or reservation does not exist'
                    ]);
                } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Payment processing failed: Duplicate transaction record detected'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Payment processing failed: Data constraint violation - ' . $e->getMessage()
                    ]);
                }
            } elseif ($e->getCode() == '42S22') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Database table structure issue - required columns may be missing'
                ]);
            } elseif ($e->getCode() == '42000') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: SQL syntax error or access denied'
                ]);
            } elseif ($e->getCode() == '08000' || $e->getCode() == '08003' || $e->getCode() == '08006') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Database connection issue - please try again'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing failed: Database error during payment creation - Code: ' . $e->getCode() . ' - ' . $e->getMessage()
                ]);
            }
            exit;
        } catch (Exception $e) {
            // Handle general errors for INSERT operation
            logError('General error during payment insert', [
                'error_message' => $e->getMessage(),
                'folio_id' => $folio_id,
                'payment_method' => $payment_method,
                'amount' => $amount_received
            ]);
            echo json_encode([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

} catch (Throwable $e) {
    // Enhanced general error handler with specific failure context
    logError('Unexpected error during payment processing', [
        'error_type' => get_class($e),
        'error_message' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'folio_id' => $folio_id ?? 'unknown',
        'payment_method' => $payment_method ?? 'unknown',
        'amount' => $amount_received ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Determine error type and provide specific guidance
    $errorType = get_class($e);
    $specificMessage = 'An unexpected error occurred while processing the payment';

    if ($errorType === 'PDOException') {
        $specificMessage = 'Database connection or query error occurred during payment processing';
    } elseif ($errorType === 'Exception') {
        $specificMessage = 'A validation or processing error occurred during payment processing';
    } elseif (strpos($errorType, 'Error') !== false) {
        $specificMessage = 'A system error occurred during payment processing';
    }

    echo json_encode([
        'success' => false,
        'message' => $specificMessage . ': ' . $e->getMessage(),
        'error_type' => $errorType,
        'suggestion' => 'Please check your input data and try again. If the problem persists, contact support.'
    ]);
}
?>
