<?php
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin','receptionist']);

$db = getPdo();
if (!$db) {
    die("Database connection failed.");
}

$guestId = intval($_GET['id'] ?? 0);
if ($guestId <= 0) {
    die("Invalid guest.");
}

// Fetch guest info
$stmt = $db->prepare("SELECT * FROM guests WHERE id = :id");
$stmt->execute([':id' => $guestId]);
$guest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guest) {
    die("Guest not found.");
}

// Fetch booking history (folios)
$foliosStmt = $db->prepare("
    SELECT gf.*,
           DATE_FORMAT(gf.folio_date, '%M %d, %Y') AS folio_date_fmt
    FROM guest_folios gf
    WHERE gf.guest_id = :id
    ORDER BY gf.folio_date DESC
");
$foliosStmt->execute([':id' => $guestId]);
$folios = $foliosStmt->fetchAll(PDO::FETCH_ASSOC);

// Tier badge colors
$tierColors = [
    'platinum' => 'bg-green-100 text-green-800 border border-green-200',
    'gold'     => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    'silver'   => 'bg-gray-100 text-gray-800 border border-gray-200',
    'member'   => 'bg-blue-100 text-blue-800 border border-blue-200',
];
?>
<div class="space-y-4">
    <!-- Guest Info -->
    <div class="rounded-lg border bg-card p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($guest['name']) ?></h3>
            <span class="px-2 py-1 rounded-md text-xs font-semibold <?php echo $tierColors[$guest['tier']] ?? 'bg-gray-200' ?>">
                <?php echo ucfirst($guest['tier']) ?>
            </span>
        </div>
        <div class="mt-3 space-y-2 text-sm text-muted-foreground">
            <div class="flex items-center gap-2"><i data-lucide="mail" class="h-4 w-4"></i> <?php echo htmlspecialchars($guest['email']) ?></div>
            <div class="flex items-center gap-2"><i data-lucide="phone" class="h-4 w-4"></i> <?php echo htmlspecialchars($guest['phone']) ?></div>
            <div class="flex items-center gap-2"><i data-lucide="calendar" class="h-4 w-4"></i> Last Visit: <?php echo $guest['last_visit'] ? date('M d, Y', strtotime($guest['last_visit'])) : "—" ?></div>
            <div class="flex items-center gap-2"><i data-lucide="star" class="h-4 w-4"></i> Total Stays: <?php echo $guest['stays'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Booking History -->
    <div class="rounded-lg border bg-card p-4">
        <h4 class="text-lg font-semibold mb-3">Booking History</h4>
        <?php if ($folios): ?>
            <ul class="divide-y text-sm">
                <?php foreach ($folios as $folio): ?>
                    <li class="py-2 flex justify-between">
                        <span>
                            Room <?php echo htmlspecialchars($folio['room_no'] ?? 'N/A') ?>
                            • <?php echo date('M d, Y H:i', strtotime($folio['check_in'])) ?> → <?php echo date('M d, Y H:i', strtotime($folio['check_out'])) ?>
                        </span>

                        <span class="font-medium">₱<?php echo number_format($folio['total_charges'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted-foreground">No bookings found.</p>
        <?php endif; ?>
    </div>
</div>

<script>window.lucide && window.lucide.createIcons();</script>
