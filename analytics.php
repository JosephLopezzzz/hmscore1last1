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
      // Get time range filter (default to current month if not specified)
      $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
      $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
      $period = isset($_GET['period']) ? $_GET['period'] : 'month';
      
      // Calculate date range based on period
      if ($period === 'day') {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
      } elseif ($period === 'week') {
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
      } elseif ($period === 'year') {
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
      }
      
      // Function to get occupancy data
      function getOccupancyData($startDate, $endDate) {
        $pdo = getPdo();
        if (!$pdo) return ['total_rooms' => 0, 'occupied_rooms' => 0, 'reserved_rooms' => 0, 'cleaning_rooms' => 0, 'maintenance_rooms' => 0];

        try {
          $stmt = $pdo->prepare("
            SELECT
              COUNT(*) as total_rooms,
              SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied_rooms,
              SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved_rooms,
              SUM(CASE WHEN status = 'Cleaning' THEN 1 ELSE 0 END) as cleaning_rooms,
              SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_rooms
            FROM rooms
          ");
          $stmt->execute();
          return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
          return ['total_rooms' => 0, 'occupied_rooms' => 0, 'reserved_rooms' => 0, 'cleaning_rooms' => 0, 'maintenance_rooms' => 0];
        }
      }
      
      // Function to get revenue data
      function getRevenueData($startDate, $endDate) {
        $pdo = getPdo();
        if (!$pdo) return ['total_revenue' => 0, 'avg_room_rate' => 0, 'total_bookings' => 0, 'total_payments' => 0];

        try {
          $stmt = $pdo->prepare("
            SELECT
              SUM(CASE WHEN transaction_type IN ('Room Charge', 'Service') THEN amount ELSE 0 END) as total_revenue,
              AVG(CASE WHEN transaction_type = 'Room Charge' THEN amount END) as avg_room_rate,
              COUNT(DISTINCT reservation_id) as total_bookings,
              SUM(CASE WHEN transaction_type = 'Payment' THEN payment_amount ELSE 0 END) as total_payments
            FROM billing_transactions
            WHERE DATE(transaction_date) BETWEEN ? AND ?
              AND status != 'Cancelled'
          ");
          $stmt->execute([$startDate, $endDate]);
          return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
          return ['total_revenue' => 0, 'avg_room_rate' => 0, 'total_bookings' => 0, 'total_payments' => 0];
        }
      }
      
      // Function to get daily occupancy trend
      function getDailyOccupancyTrend($startDate, $endDate) {
        $pdo = getPdo();
        if (!$pdo) return [];

        try {
          $stmt = $pdo->prepare("
            SELECT
              DATE(transaction_date) as date,
              COUNT(DISTINCT reservation_id) as bookings,
              SUM(CASE WHEN transaction_type = 'Room Charge' THEN amount ELSE 0 END) as daily_revenue
            FROM billing_transactions
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            GROUP BY DATE(transaction_date)
            ORDER BY date
          ");
          $stmt->execute([$startDate, $endDate]);
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
          return [];
        }
      }
      
      // Get data for dashboard
      $occupancyData = getOccupancyData($startDate, $endDate);
      $revenueData = getRevenueData($startDate, $endDate);
      $dailyTrend = getDailyOccupancyTrend($startDate, $endDate);
      
      // Calculate KPIs
      $totalRooms = $occupancyData['total_rooms'] ?? 0;
      $occupiedRooms = $occupancyData['occupied_rooms'] ?? 0;
      $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
      $totalRevenue = $revenueData['total_revenue'] ?? 0;
      $avgRoomRate = $revenueData['avg_room_rate'] ?? 0;
      $totalBookings = $revenueData['total_bookings'] ?? 0;
      $revPAR = $totalRooms > 0 && $totalBookings > 0 ? round($totalRevenue / $totalRooms, 2) : 0;
      
      // Prepare chart data
      $dailyTrendLabels = array_column($dailyTrend, 'date');
      $dailyTrendBookings = array_column($dailyTrend, 'bookings');
      $dailyTrendRevenue = array_column($dailyTrend, 'daily_revenue');
      
      $metrics = [
        [ 'label' => 'Occupancy Rate', 'value' => $occupancyRate . '%', 'change' => '+2.1%', 'trend' => 'up' ],
        [ 'label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 0), 'change' => '+12.3%', 'trend' => 'up' ],
        [ 'label' => 'Avg Daily Rate', 'value' => '₱' . number_format($avgRoomRate, 0), 'change' => '+8.1%', 'trend' => 'up' ],
        [ 'label' => 'RevPAR', 'value' => '₱' . number_format($revPAR, 0), 'change' => '+15.2%', 'trend' => 'up' ],
      ];
    ?>
    <main class="container mx-auto px-4 py-4 max-w-7xl">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold">Analytics & Reports</h1>
          <p class="text-muted-foreground text-sm">Performance insights and KPIs</p>
        </div>
        <div class="flex gap-2">
          <select id="periodFilter" class="gap-2 inline-flex items-center rounded-md border px-3 py-2 text-sm">
            <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Today</option>
            <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
            <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
          </select>
          <button onclick="exportData()" class="gap-2 inline-flex items-center rounded-md border px-3 py-2 text-sm">
            <i data-lucide="download" class="h-4 w-4"></i>
            Export
          </button>
        </div>
      </div>

      <div class="grid gap-4 mb-4 grid-cols-2 lg:grid-cols-4">
        <?php foreach ($metrics as $metric): ?>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
              <p class="text-sm text-muted-foreground"><?php echo $metric['label']; ?></p>
              <?php if ($metric['trend'] === 'up'): ?>
                <i data-lucide="trending-up" class="h-4 w-4 text-success"></i>
              <?php else: ?>
                <i data-lucide="trending-down" class="h-4 w-4 text-destructive"></i>
              <?php endif; ?>
            </div>
            <p class="text-xl sm:text-2xl font-bold mb-1"><?php echo $metric['value']; ?></p>
            <p class="text-sm text-success"><?php echo $metric['change']; ?> vs last period</p>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="grid gap-6">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <h3 class="text-lg font-semibold mb-4">Daily Booking Trend</h3>
          <div class="h-96">
            <canvas id="bookingTrendChart"></canvas>
          </div>
        </div>
      </div>

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4 mt-4">
        <h3 class="text-lg font-semibold mb-3">Room Status Overview</h3>
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
          <div class="text-center p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
            <p class="text-xl font-bold text-green-600"><?php echo $occupancyData['occupied_rooms'] ?? 0; ?></p>
            <p class="text-xs text-green-600">Occupied</p>
          </div>
          <div class="text-center p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
            <p class="text-xl font-bold text-blue-600"><?php echo $occupancyData['reserved_rooms'] ?? 0; ?></p>
            <p class="text-xs text-blue-600">Reserved</p>
          </div>
          <div class="text-center p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
            <p class="text-xl font-bold text-yellow-600"><?php echo $occupancyData['cleaning_rooms'] ?? 0; ?></p>
            <p class="text-xs text-yellow-600">Cleaning</p>
          </div>
          <div class="text-center p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
            <p class="text-xl font-bold text-red-600"><?php echo $occupancyData['maintenance_rooms'] ?? 0; ?></p>
            <p class="text-xs text-red-600">Maintenance</p>
          </div>
          <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-900/20">
            <p class="text-xl font-bold text-gray-600"><?php echo $occupancyData['total_rooms'] ?? 0; ?></p>
            <p class="text-xs text-gray-600">Total Rooms</p>
          </div>
        </div>
      </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      // Initialize charts when page loads
      document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
      });
      
      function initializeCharts() {
        // Booking Trend Chart
        const bookingTrendCtx = document.getElementById('bookingTrendChart').getContext('2d');
        new Chart(bookingTrendCtx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($dailyTrendLabels); ?>,
            datasets: [{
              label: 'Bookings',
              data: <?php echo json_encode($dailyTrendBookings); ?>,
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.1
            }, {
              label: 'Revenue (₱)',
              data: <?php echo json_encode($dailyTrendRevenue); ?>,
              borderColor: 'rgb(16, 185, 129)',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.1,
              yAxisID: 'y1'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false }
              }
            }
          }
        });
      }
      
      // Handle period filter change
      document.getElementById('periodFilter').addEventListener('change', function() {
        const period = this.value;
        window.location.href = `?period=${period}`;
      });
      
      function exportData() {
        // Simple export functionality - could be enhanced with actual data export
        alert('Export functionality would generate reports based on current filters.');
      }
    </script>

    <!-- Lucide Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      // Initialize Lucide icons when DOM is loaded
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }
      });
    </script>
  </body>
  </html>


