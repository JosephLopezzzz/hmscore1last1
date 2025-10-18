<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth(['admin','receptionist']);

$pdo = getPdo(); if (!$pdo) { echo 'No database.'; exit; }
$guestId = (int)($_GET['id'] ?? 0); if ($guestId <= 0) { echo 'Invalid guest'; exit; }

// Fetch guest
$g = $pdo->prepare('SELECT id, first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes FROM guests WHERE id = :id');
$g->execute([':id' => $guestId]);
$guest = $g->fetch(); if (!$guest) { echo 'Guest not found'; exit; }

// Compute metrics
$staysStmt = $pdo->prepare("SELECT COUNT(DISTINCT r.id) FROM reservations r WHERE r.guest_id = :id AND r.status = 'Checked In'");
$staysStmt->execute([':id' => $guestId]);
$timesCheckedIn = (int)$staysStmt->fetchColumn();

$paidStmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN bt.transaction_type IN ('Room Charge','Service') THEN bt.amount WHEN bt.transaction_type = 'Refund' THEN -bt.amount ELSE 0 END),0)
  FROM billing_transactions bt
  JOIN reservations r ON bt.reservation_id = r.id
  WHERE r.guest_id = :id AND bt.status = 'Paid'");
$paidStmt->execute([':id' => $guestId]);
$totalPaid = (float)$paidStmt->fetchColumn();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $upd = $pdo->prepare('UPDATE guests SET first_name=:first_name,last_name=:last_name,email=:email,phone=:phone,address=:address,city=:city,country=:country,id_type=:id_type,id_number=:id_number,date_of_birth=:dob,nationality=:nationality,notes=:notes WHERE id=:id');
  $upd->execute([
    ':first_name' => trim($_POST['first_name'] ?? ''),
    ':last_name' => trim($_POST['last_name'] ?? ''),
    ':email' => trim($_POST['email'] ?? ''),
    ':phone' => trim($_POST['phone'] ?? ''),
    ':address' => trim($_POST['address'] ?? ''),
    ':city' => trim($_POST['city'] ?? ''),
    ':country' => trim($_POST['country'] ?? ''),
    ':id_type' => trim($_POST['id_type'] ?? 'National ID'),
    ':id_number' => trim($_POST['id_number'] ?? ''),
    ':dob' => $_POST['date_of_birth'] ?? null,
    ':nationality' => trim($_POST['nationality'] ?? ''),
    ':notes' => trim($_POST['notes'] ?? ''),
    ':id' => $guestId,
  ]);
  header('Location: guest_profile.php?id=' . $guestId);
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Guest Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>
  <body class="min-h-screen bg-background">
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="container mx-auto px-4 py-6">
      <h1 class="text-2xl font-bold mb-4">Guest Profile</h1>
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Editable personal info -->
        <form method="post" class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 lg:col-span-2 space-y-3">
          <h2 class="text-lg font-semibold mb-2">Personal Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-muted-foreground mb-1">First Name</label>
              <input name="first_name" value="<?php echo htmlspecialchars($guest['first_name'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Last Name</label>
              <input name="last_name" value="<?php echo htmlspecialchars($guest['last_name'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Email</label>
              <input name="email" type="email" value="<?php echo htmlspecialchars($guest['email'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Phone</label>
              <input name="phone" value="<?php echo htmlspecialchars($guest['phone'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Address</label>
              <input name="address" value="<?php echo htmlspecialchars($guest['address'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">City</label>
              <input name="city" value="<?php echo htmlspecialchars($guest['city'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Country</label>
              <input name="country" value="<?php echo htmlspecialchars($guest['country'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">ID Type</label>
              <input name="id_type" value="<?php echo htmlspecialchars($guest['id_type'] ?? 'National ID'); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">ID Number</label>
              <input name="id_number" value="<?php echo htmlspecialchars($guest['id_number'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Date of Birth</label>
              <input name="date_of_birth" type="date" value="<?php echo htmlspecialchars(substr((string)($guest['date_of_birth'] ?? ''),0,10)); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
        </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Nationality</label>
              <input name="nationality" value="<?php echo htmlspecialchars($guest['nationality'] ?? ''); ?>" class="w-full px-3 py-2 rounded-md border bg-background">
        </div>
    </div>
          <div>
            <label class="block text-xs text-muted-foreground mb-1">Notes</label>
            <textarea name="notes" class="w-full px-3 py-2 rounded-md border bg-background" rows="3"><?php echo htmlspecialchars($guest['notes'] ?? ''); ?></textarea>
          </div>
          <div class="flex justify-end">
            <button class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground">Save Changes</button>
          </div>
        </form>

        <!-- Metrics -->
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <h2 class="text-lg font-semibold mb-2">Guest Metrics</h2>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between"><span>Times Checked In</span><span class="font-semibold"><?php echo $timesCheckedIn; ?></span></div>
            <div class="flex items-center justify-between"><span>Total Paid</span><span class="font-semibold"><?php echo formatCurrencyPhpPeso($totalPaid, 2); ?></span></div>
          </div>
    </div>
</div>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
<script>window.lucide && window.lucide.createIcons();</script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
  </body>
</html>
