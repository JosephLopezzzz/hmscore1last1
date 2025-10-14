<?php
// export_billing_csv.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth(['admin', 'receptionist']);

$db = getPdo();
if (!$db) {
    die("Database connection failed");
}

// Read filters
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$method = $_GET['method'] ?? null;

$sql = "SELECT
            f.folio_id,
            g.name AS guest_name,
            f.room_no,
            f.total_charges,
            f.total_paid,
            f.balance,
            f.status,
            f.created_at AS folio_created,
            t.id AS transaction_id,
            t.amount AS txn_amount,
            t.payment_method,
            t.created_at AS txn_time
        FROM guest_folios f
        LEFT JOIN guests g ON f.guest_id = g.id
        LEFT JOIN transactions t ON f.folio_id = t.folio_id
        WHERE 1=1";

$params = [];

// Optional filters
if ($from) {
    $sql .= " AND f.created_at >= ?";
    $params[] = $from . " 00:00:00";
}
if ($to) {
    $sql .= " AND f.created_at <= ?";
    $params[] = $to . " 23:59:59";
}
if ($status && $status !== 'All') {
    $sql .= " AND f.status = ?";
    $params[] = $status;
}
if ($search) {
    $sql .= " AND g.name LIKE ?";
    $params[] = "%$search%";
}
if ($method && $method !== 'All') {
    $sql .= " AND t.payment_method = ?";
    $params[] = $method;
}

$sql .= " ORDER BY f.created_at DESC, t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$filename = 'BillingReport_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

fputcsv($out, [
    'Folio ID',
    'Guest',
    'Room',
    'Total Charges',
    'Total Paid',
    'Balance',
    'Status',
    'Transaction ID',
    'Txn Amount',
    'Payment Method',
    'Txn Time',
    'Folio Created'
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['folio_id'] ?? '',
        $row['guest_name'] ?? '',
        $row['room_no'] ?? '',
        $row['total_charges'] ?? '',
        $row['total_paid'] ?? '',
        $row['balance'] ?? '',
        $row['status'] ?? '',
        $row['transaction_id'] ?? '',
        $row['txn_amount'] ?? '',
        $row['payment_method'] ?? '',
        $row['txn_time'] ?? '',
        $row['folio_created'] ?? ''
    ]);
}

fclose($out);
exit;
?>
