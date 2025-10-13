<?php
require_once __DIR__ . '/../includes/db.php';
$dbStats = fetchDashboardStats();
$stats = [];

if ($dbStats) {
  $stats = [
    [ 'label' => 'Occupancy Rate', 'value' => ($dbStats['occupancy'] ?? 0) . '%', 'change' => '+5%', 'icon' => 'building-2' ],
    [ 'label' => 'Guests In-House', 'value' => (string)($dbStats['inHouse'] ?? 0), 'change' => '+12', 'icon' => 'users' ],
    [ 'label' => "Today's Revenue", 'value' => formatCurrencyPhpPeso((float)($dbStats['todayRevenue'] ?? 0), 2), 'change' => '+18%', 'icon' => 'banknote' ],
    [ 'label' => 'Avg. Daily Rate', 'value' => formatCurrencyPhpPeso((float)($dbStats['avgRate'] ?? 0), 2), 'change' => '+8%', 'icon' => 'trending-up' ],
  ];
} else {
  $stats = [
    [ 'label' => 'Occupancy Rate', 'value' => '87%', 'change' => '+5%', 'icon' => 'building-2' ],
    [ 'label' => 'Guests In-House', 'value' => '142', 'change' => '+12', 'icon' => 'users' ],
    [ 'label' => "Today's Revenue", 'value' => '₱24,580', 'change' => '+18%', 'icon' => 'banknote' ],
    [ 'label' => 'Avg. Daily Rate', 'value' => '₱185', 'change' => '+8%', 'icon' => 'trending-up' ],
  ];
}
?>
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
  <?php foreach ($stats as $stat): ?>
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-lg bg-accent/10">
            <i data-lucide="<?php echo $stat['icon']; ?>" class="h-5 w-5 text-accent"></i>
          </div>
          <div>
            <p class="text-sm text-muted-foreground"><?php echo $stat['label']; ?></p>
            <p class="text-2xl font-bold text-foreground"><?php echo $stat['value']; ?></p>
          </div>
        </div>
        <span class="text-sm font-medium text-success"><?php echo $stat['change']; ?></span>
      </div>
    </div>
  <?php endforeach; ?>
  
</div>


