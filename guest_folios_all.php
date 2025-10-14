<?php
// guest_folios_all.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth(['admin','receptionist']);

$db = getPdo();

// Simple pagination (page param)
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Optional filters (basic)
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? null;

$where = [];
$params = [];

if ($status && $status !== 'All') {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($search) {
    $where[] = 'guest_name LIKE ?';
    $params[] = '%' . $search . '%';
}

$sql = '
    SELECT gf.*, g.name AS guest_name
    FROM guest_folios gf
    LEFT JOIN guests g ON gf.guest_id = g.id
';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';

$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$folios = $stmt->fetchAll();

// count for pagination total (simple)
$countSql = 'SELECT COUNT(*) FROM guest_folios' . ($where ? ' WHERE ' . implode(' AND ', array_slice($where,0,count($where))) : '');
$countParams = array_slice($params, 0, max(0, count($params)-2)); // remove limit/offset for count
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalCount / $limit);

include __DIR__ . '/includes/header.php';
?>
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
<main class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">All Guest Folios</h1>
        <a href="billing.php" class="text-sm rounded border px-3 py-2">Back to Billing</a>
    </div>

    <form method="get" class="mb-4 flex gap-2">
        <input type="text" name="search" placeholder="Search guest..." value="<?=htmlspecialchars($search)?>" class="rounded border px-2 py-1">
        <select name="status" class="rounded border px-2 py-1">
            <option value="All">All</option>
            <option value="open" <?=($status==='open'?'selected':'')?>>Open</option>
            <option value="partial" <?=($status==='partial'?'selected':'')?>>Partial</option>
            <option value="paid" <?=($status==='paid'?'selected':'')?>>Paid</option>
        </select>
        <button class="rounded bg-primary px-3 py-1 text-primary-foreground">Filter</button>
        <a class="rounded border px-3 py-1" href="export_billing_csv.php?<?=http_build_query(['search'=>$search,'status'=>$status])?>">Export CSV</a>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full table-auto border">
            <thead>
                <tr class="bg-muted">
                    <th class="p-2">Folio ID</th>
                    <th class="p-2">Guest</th>
                    <th class="p-2">Room</th>
                    <th class="p-2">Charges</th>
                    <th class="p-2">Paid</th>
                    <th class="p-2">Balance</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($folios as $f): ?>
                <tr>
                    <td class="p-2"><?=htmlspecialchars($f['folio_id'])?></td>
                    <td class="p-2"><a href="guest_profile.php?id=<?=htmlspecialchars($f['guest_id'])?>" class="text-blue-600 hover:underline"><?=htmlspecialchars($f['guest_name'])?></a></td>
                    <td class="p-2"><?=htmlspecialchars($f['room_no'])?></td>
                    <td class="p-2">₱<?=number_format($f['total_charges'], 2)?></td>
                    <td class="p-2">₱<?=number_format($f['total_paid'], 2)?></td>
                    <td class="p-2">₱<?=number_format($f['balance'], 2)?></td>
                    <td class="p-2">
                        <span class="px-2 py-1 rounded text-xs
                            <?php if($f['status'] === 'paid'): ?>bg-green-100 text-green-800
                            <?php elseif($f['status'] === 'partial'): ?>bg-yellow-100 text-yellow-800
                            <?php else: ?>bg-red-100 text-red-800
                            <?php endif; ?>">
                            <?=ucfirst(htmlspecialchars($f['status']))?>
                        </span>
                    </td>
                    <td class="p-2"><?php echo date('M d, Y', strtotime($f['created_at'])) ?></td>
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
