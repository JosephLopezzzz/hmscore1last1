<!doctype html>
<html lang="en" class="">
  <head>
    <!-- Theme initialization (must be first to prevent flash) -->
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analytics & Reports - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/helpers.php'; ?>
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php
      $stats = fetchDashboardStats();
      $adr = isset($stats['avgRate']) ? (float)$stats['avgRate'] : 186.50;
      $occ = isset($stats['occupancy']) ? (float)$stats['occupancy'] : 87.2;
      $totalRevenue = isset($stats['todayRevenue']) ? (float)$stats['todayRevenue'] : 125840;

      $metrics = [
        [ 'label' => 'RevPAR', 'value' => formatCurrencyPhpPeso(($adr * ($occ/100)), 2), 'change' => '+12.3%', 'trend' => 'up' ],
        [ 'label' => 'ADR', 'value' => formatCurrencyPhpPeso($adr, 2), 'change' => '+8.1%', 'trend' => 'up' ],
        [ 'label' => 'Occupancy', 'value' => number_format($occ, 1) . '%', 'change' => '+5.4%', 'trend' => 'up' ],
        [ 'label' => 'Total Revenue', 'value' => formatCurrencyPhpPeso($totalRevenue, 2), 'change' => '+15.2%', 'trend' => 'up' ],
      ];
      $revenue = [
        [ 'name' => 'Rooms', 'amount' => (int)round($totalRevenue * 0.68), 'percentage' => 68 ],
        [ 'name' => 'F&B', 'amount' => (int)round($totalRevenue * 0.22), 'percentage' => 22 ],
        [ 'name' => 'Spa', 'amount' => (int)round($totalRevenue * 0.07), 'percentage' => 7 ],
        [ 'name' => 'Other', 'amount' => (int)round($totalRevenue * 0.03), 'percentage' => 3 ],
      ];
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Analytics & Reports</h1>
          <p class="text-muted-foreground">Performance insights and KPIs</p>
        </div>
        <div class="flex gap-2">
          <button class="gap-2 inline-flex items-center rounded-md border px-3 py-2 text-sm">
            <i data-lucide="calendar" class="h-4 w-4"></i>
            Date Range
          </button>
          <button class="gap-2 inline-flex items-center rounded-md border px-3 py-2 text-sm">
            <i data-lucide="download" class="h-4 w-4"></i>
            Export
          </button>
        </div>
      </div>

      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <?php foreach ($metrics as $metric): ?>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center justify-between mb-2">
              <p class="text-sm text-muted-foreground"><?php echo $metric['label']; ?></p>
              <?php if ($metric['trend'] === 'up'): ?>
                <i data-lucide="trending-up" class="h-4 w-4 text-success"></i>
              <?php else: ?>
                <i data-lucide="trending-down" class="h-4 w-4 text-destructive"></i>
              <?php endif; ?>
            </div>
            <p class="text-2xl font-bold mb-1"><?php echo $metric['value']; ?></p>
            <p class="text-sm text-success"><?php echo $metric['change']; ?> vs last month</p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <h3 class="text-lg font-semibold mb-6">Revenue by Department</h3>
          <div class="space-y-4">
            <?php foreach ($revenue as $dept): ?>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-sm font-medium"><?php echo $dept['name']; ?></span>
                  <span class="text-sm text-muted-foreground"><?php echo formatCurrencyPhpPeso($dept['amount'], 2); ?></span>
                </div>
                <div class="w-full bg-muted rounded-full h-2">
                  <div class="bg-accent h-2 rounded-full" style="width: <?php echo $dept['percentage']; ?>%"></div>
                </div>
                <p class="text-xs text-muted-foreground mt-1"><?php echo $dept['percentage']; ?>% of total</p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <h3 class="text-lg font-semibold mb-6">Occupancy Trend</h3>
          <div class="space-y-3">
            <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"] as $day): ?>
              <?php $occ = 70 + mt_rand(0,200)/10; ?>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-sm"><?php echo $day; ?></span>
                  <span class="text-sm font-medium"><?php echo number_format($occ,1); ?>%</span>
                </div>
                <div class="w-full bg-muted rounded-full h-2">
                  <div class="bg-primary h-2 rounded-full" style="width: <?php echo $occ; ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 mt-6">
        <h3 class="text-lg font-semibold mb-4">Quick Reports</h3>
        <div class="grid gap-3 md:grid-cols-3">
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Daily Flash Report</button>
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Night Audit Summary</button>
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Forecast Report</button>
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Guest History Report</button>
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Revenue Analysis</button>
          <button class="justify-start inline-flex items-center rounded-md border px-3 py-2 text-sm">Housekeeping Report</button>
        </div>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
  </body>
  </html>


