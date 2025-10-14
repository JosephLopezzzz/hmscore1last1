<?php
require_once __DIR__ . '/../includes/db.php';
$dbStats = fetchDashboardStats();
$stats = [
  [
    'label' => 'Occupancy Rate',
    'value' => (isset($dbStats['occupancy']) ? (int)$dbStats['occupancy'] : 0) . '%',
    'change' => ((int)($dbStats['changes']['occupancy'] ?? 0) >= 0 ? '+' : '') . (int)($dbStats['changes']['occupancy'] ?? 0) . '%',
    'icon' => 'building-2'
  ],
  [
    'label' => 'Guests In-House',
    'value' => (string)($dbStats['inHouse'] ?? 0),
    'change' => ((int)($dbStats['changes']['inHouse'] ?? 0) >= 0 ? '+' : '') . (int)($dbStats['changes']['inHouse'] ?? 0),
    'icon' => 'users'
  ],
  [
    'label' => "Today's Revenue",
    'value' => formatCurrencyPhpPeso((float)($dbStats['todayRevenue'] ?? 0), 2),
    'change' => ((int)($dbStats['changes']['todayRevenue'] ?? 0) >= 0 ? '+' : '') . (int)($dbStats['changes']['todayRevenue'] ?? 0) . '%',
    'icon' => 'banknote'
  ],
  [
    'label' => 'Avg. Daily Rate',
    'value' => formatCurrencyPhpPeso((float)($dbStats['avgRate'] ?? 0), 2),
    'change' => ((int)($dbStats['changes']['avgRate'] ?? 0) >= 0 ? '+' : '') . (int)($dbStats['changes']['avgRate'] ?? 0) . '%',
    'icon' => 'trending-up'
  ],
];
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


