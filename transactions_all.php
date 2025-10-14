<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getPdo();
requireAuth(['admin','receptionist']);

// Pagination + filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$method = $_GET['method'] ?? null;
$search = $_GET['search'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = [];
$params = [];

// ✅ Correct SQL (NO notes column)
$sql = "SELECT
            t.id,
            t.folio_id,
            t.payment_method,
            t.amount,
            t.created_at,
            g.name AS guest_name
        FROM transactions t
        LEFT JOIN guest_folios f ON t.folio_id = f.folio_id
        LEFT JOIN guests g ON f.guest_id = g.id
        WHERE 1=1";

if ($method && $method !== 'All') {
    $sql .= " AND t.payment_method = ?";
    $params[] = $method;
}
if ($search) {
    $sql .= " AND (g.name LIKE ? OR t.folio_id LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($from) {
    $sql .= " AND t.created_at >= ?";
    $params[] = $from . ' 00:00:00';
}
if ($to) {
    $sql .= " AND t.created_at <= ?";
    $params[] = $to . ' 23:59:59';
}

$sql .= " ORDER BY t.id ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$txns = $stmt->fetchAll();

// ✅ Count query
$countSql = "SELECT COUNT(*)
             FROM transactions t
             LEFT JOIN guest_folios f ON t.folio_id = f.folio_id
             LEFT JOIN guests g ON f.guest_id = g.id
             WHERE 1=1";

$countWhere = [];
$countParams = [];

if ($method && $method !== 'All') {
    $countWhere[] = "t.payment_method = ?";
    $countParams[] = $method;
}
if ($search) {
    $countWhere[] = "(g.name LIKE ? OR t.folio_id LIKE ?)";
    $countParams[] = '%' . $search . '%';
    $countParams[] = '%' . $search . '%';
}
if ($from) {
    $countWhere[] = "t.created_at >= ?";
    $countParams[] = $from . ' 00:00:00';
}
if ($to) {
    $countWhere[] = "t.created_at <= ?";
    $countParams[] = $to . ' 23:59:59';
}

if ($countWhere) {
    $countSql .= " AND " . implode(' AND ', $countWhere);
}

$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalCount / $limit);

// ✅ Output HTML
include __DIR__ . '/includes/header.php';
?>
<link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="./public/css/tokens.css" />

<main class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">All Transactions</h1>
        <a href="billing.php" class="text-sm rounded border px-3 py-2">Back to Billing</a>
    </div>

    <form method="get" class="mb-4 flex gap-2">
        <input type="text" name="search" placeholder="Search guest or folio..." value="<?=htmlspecialchars($search)?>" class="rounded border px-2 py-1">
        <select name="method" class="rounded border px-2 py-1">
            <option value="All">All</option>
            <option value="Cash" <?=($method==='Cash'?'selected':'')?>>Cash</option>
            <option value="Card" <?=($method==='Card'?'selected':'')?>>Card</option>
            <option value="GCash" <?=($method==='GCash'?'selected':'')?>>GCash</option>
            <option value="Bank Transfer" <?=($method==='Bank Transfer'?'selected':'')?>>Bank Transfer</option>
        </select>
        <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="rounded border px-2 py-1">
        <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="rounded border px-2 py-1">
        <button class="rounded bg-primary px-3 py-1 text-primary-foreground">Filter</button>
        <a class="rounded border px-3 py-1" href="export_billing_csv.php?<?=http_build_query(['method'=>$method,'search'=>$search,'from'=>$from,'to'=>$to])?>">Export CSV</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full table-auto border">
            <thead>
                <tr class="bg-muted">
                    <th class="p-2">Transaction ID</th>
                    <th class="p-2">Folio</th>
                    <th class="p-2">Guest</th>
                    <th class="p-2">Method</th>
                    <th class="p-2">Amount</th>
                    <th class="p-2">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($txns as $t): ?>
                <tr>
                    <td class="p-2"><?php echo htmlspecialchars($t['id'])?></td>
                    <td class="p-2"><?php echo htmlspecialchars($t['folio_id'])?></td>
                    <td class="p-2"><?php echo htmlspecialchars($t['guest_name'] ?? '')?></td>
                    <td class="p-2"><?php echo htmlspecialchars($t['payment_method'])?></td>
                    <td class="p-2">₱<?php echo number_format($t['amount'], 2)?></td>
                    <td class="p-2"><?php echo htmlspecialchars($t['created_at'])?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <p>Page <?=$page?> of <?=$totalPages?></p>
        <?php if ($page > 1): ?>
            <a href="?<?=http_build_query(array_merge($_GET,['page'=>$page-1]))?>" class="mr-2">Prev</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?<?=http_build_query(array_merge($_GET,['page'=>$page+1]))?>">Next</a>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
