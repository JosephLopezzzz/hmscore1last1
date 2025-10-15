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
    <div class="stat-card bg-card border border-border rounded-lg p-6 hover:shadow-md transition-all duration-200 hover:transform hover:-translate-y-1">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="p-3 rounded-lg bg-primary/10">
            <i data-lucide="<?php echo $stat['icon']; ?>" class="h-6 w-6 text-primary"></i>
          </div>
          <div>
            <p class="text-sm text-muted-foreground font-medium"><?php echo $stat['label']; ?></p>
            <p class="text-2xl font-bold text-card-foreground"><?php echo $stat['value']; ?></p>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-success bg-success/10 px-2 py-1 rounded-md"><?php echo $stat['change']; ?></span>
        <div class="flex items-center gap-1 text-success">
          <i data-lucide="trending-up" class="h-4 w-4"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  
</div>



