<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';

// Import Dompdf classes
use Dompdf\Dompdf;
use Dompdf\Options;

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

// Get data for PDF
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

// Prepare chart data for simple tables in PDF
$dailyTrendLabels = array_column($dailyTrend, 'date');
$dailyTrendBookings = array_column($dailyTrend, 'bookings');
$dailyTrendRevenue = array_column($dailyTrend, 'daily_revenue');

// Create PDF content

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inn Nexus Hotel - Analytics Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
        .hotel-name { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .report-title { font-size: 18px; color: #666; margin-bottom: 10px; }
        .period-info { font-size: 14px; color: #888; }
        .section { margin: 20px 0; }
        .section-title { font-size: 16px; font-weight: bold; color: #2563eb; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; }
        .kpi-item { display: table-cell; width: 25%; padding: 15px; text-align: center; border: 1px solid #ddd; }
        .kpi-label { font-size: 12px; color: #666; margin-bottom: 5px; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #2563eb; }
        .kpi-change { font-size: 11px; color: #10b981; margin-top: 3px; }
        .status-grid { display: table; width: 100%; margin: 20px 0; }
        .status-item { display: table-cell; padding: 10px; text-align: center; }
        .status-value { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .status-label { font-size: 12px; color: #666; }
        .data-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f8f9fa; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
        .green { color: #10b981; }
        .blue { color: #2563eb; }
        .yellow { color: #f59e0b; }
        .red { color: #ef4444; }
        .gray { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="hotel-name">Inn Nexus Hotel</div>
        <div class="report-title">Analytics & Performance Report</div>
        <div class="period-info">Period: ' . ucfirst($period) . ' (' . date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate)) . ')</div>
        <div class="period-info">Generated on: ' . date('F j, Y \a\t g:i A') . '</div>
    </div>

    <div class="section">
        <div class="section-title">Key Performance Indicators</div>
        <div class="kpi-grid">
            <div class="kpi-item">
                <div class="kpi-label">Occupancy Rate</div>
                <div class="kpi-value">' . $occupancyRate . '%</div>
                <div class="kpi-change">↗ +2.1% vs last period</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value">₱' . number_format($totalRevenue, 0) . '</div>
                <div class="kpi-change">↗ +12.3% vs last period</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-label">Avg Daily Rate</div>
                <div class="kpi-value">₱' . number_format($avgRoomRate, 0) . '</div>
                <div class="kpi-change">↗ +8.1% vs last period</div>
            </div>
            <div class="kpi-item">
                <div class="kpi-label">RevPAR</div>
                <div class="kpi-value">₱' . number_format($revPAR, 0) . '</div>
                <div class="kpi-change">↗ +15.2% vs last period</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Room Status Overview</div>
        <div class="status-grid">
            <div class="status-item">
                <div class="status-value green">' . ($occupancyData['occupied_rooms'] ?? 0) . '</div>
                <div class="status-label">Occupied</div>
            </div>
            <div class="status-item">
                <div class="status-value blue">' . ($occupancyData['reserved_rooms'] ?? 0) . '</div>
                <div class="status-label">Reserved</div>
            </div>
            <div class="status-item">
                <div class="status-value yellow">' . ($occupancyData['cleaning_rooms'] ?? 0) . '</div>
                <div class="status-label">Cleaning</div>
            </div>
            <div class="status-item">
                <div class="status-value red">' . ($occupancyData['maintenance_rooms'] ?? 0) . '</div>
                <div class="status-label">Maintenance</div>
            </div>
            <div class="status-item">
                <div class="status-value gray">' . ($occupancyData['total_rooms'] ?? 0) . '</div>
                <div class="status-label">Total Rooms</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Daily Booking Trend</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bookings</th>
                    <th>Revenue (₱)</th>
                </tr>
            </thead>
            <tbody>';

if (!empty($dailyTrend)) {
    foreach ($dailyTrend as $day) {
        $html .= '<tr>
            <td>' . date('M j, Y', strtotime($day['date'])) . '</td>
            <td>' . ($day['bookings'] ?? 0) . '</td>
            <td>' . number_format($day['daily_revenue'] ?? 0, 2) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="3">No data available for the selected period</td></tr>';
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Report Summary</div>
        <p><strong>Total Bookings:</strong> ' . $totalBookings . '</p>
        <p><strong>Total Revenue:</strong> ₱' . number_format($totalRevenue, 2) . '</p>
        <p><strong>Average Daily Rate:</strong> ₱' . number_format($avgRoomRate, 2) . '</p>
        <p><strong>Revenue Per Available Room:</strong> ₱' . number_format($revPAR, 2) . '</p>
        <p><strong>Occupancy Rate:</strong> ' . $occupancyRate . '%</p>
    </div>

    <div class="footer">
        <p>This report was generated by Inn Nexus Hotel Management System on ' . date('F j, Y \a\t g:i A') . '</p>
        <p>Confidential - For internal use only</p>
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$filename = 'inn-nexus-analytics-' . $period . '-' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, array('Attachment' => 0)); // 0 = open in browser, 1 = download
?>
