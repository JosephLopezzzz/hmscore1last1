<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

// Get all channels
function getChannels() {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT id, name, display_name FROM channels WHERE status = 'Active' ORDER BY display_name");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching channels: ' . $e->getMessage());
        return [];
    }
}

// Get sync logs with pagination
function getSyncLogs($channelId = null, $limit = 50, $offset = 0) {
    $pdo = getPdo();
    if (!$pdo) return ['logs' => [], 'total' => 0];

    try {
        // Count total logs
        $countSql = "SELECT COUNT(*) FROM channel_sync_logs";
        $countParams = [];

        if ($channelId) {
            $countSql .= " WHERE channel_id = ?";
            $countParams[] = $channelId;
        }

        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int)$stmt->fetchColumn();

        // Get logs with channel info
        $sql = "
            SELECT
                csl.*,
                c.display_name as channel_name,
                c.name as channel_code
            FROM channel_sync_logs csl
            JOIN channels c ON csl.channel_id = c.id
        ";

        $params = [];
        if ($channelId) {
            $sql .= " WHERE csl.channel_id = ?";
            $params[] = $channelId;
        }

        $sql .= " ORDER BY csl.started_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        return ['logs' => $logs, 'total' => $total];
    } catch (Throwable $e) {
        error_log('Error fetching sync logs: ' . $e->getMessage());
        return ['logs' => [], 'total' => 0];
    }
}

// Get sync statistics
function getSyncStats() {
    $pdo = getPdo();
    if (!$pdo) return [
        'total_syncs' => 0,
        'successful_syncs' => 0,
        'failed_syncs' => 0,
        'avg_duration' => 0
    ];

    try {
        // Total syncs
        $totalSyncs = (int)$pdo->query("SELECT COUNT(*) FROM channel_sync_logs")->fetchColumn();

        // Successful syncs
        $successfulSyncs = (int)$pdo->query("SELECT COUNT(*) FROM channel_sync_logs WHERE status = 'Success'")->fetchColumn();

        // Failed syncs
        $failedSyncs = (int)$pdo->query("SELECT COUNT(*) FROM channel_sync_logs WHERE status = 'Failed'")->fetchColumn();

        // Average duration
        $avgDuration = (float)$pdo->query("SELECT AVG(duration_seconds) FROM channel_sync_logs WHERE duration_seconds IS NOT NULL")->fetchColumn();

        return [
            'total_syncs' => $totalSyncs,
            'successful_syncs' => $successfulSyncs,
            'failed_syncs' => $failedSyncs,
            'avg_duration' => $avgDuration ?: 0
        ];
    } catch (Throwable $e) {
        return [
            'total_syncs' => 0,
            'successful_syncs' => 0,
            'failed_syncs' => 0,
            'avg_duration' => 0
        ];
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'retry_sync') {
        $logId = (int)$_POST['log_id'];
        $pdo = getPdo();
        if ($pdo) {
            try {
                // Get the original sync log details
                $stmt = $pdo->prepare("
                    SELECT csl.*, c.display_name as channel_name
                    FROM channel_sync_logs csl
                    JOIN channels c ON csl.channel_id = c.id
                    WHERE csl.id = ?
                ");
                $stmt->execute([$logId]);
                $originalLog = $stmt->fetch();

                if ($originalLog) {
                    // Create a new sync log for retry
                    $stmt = $pdo->prepare("
                        INSERT INTO channel_sync_logs (
                            channel_id, sync_type, sync_direction, status, started_at
                        ) VALUES (?, ?, ?, 'Running', NOW())
                    ");
                    $stmt->execute([
                        $originalLog['channel_id'],
                        $originalLog['sync_type'],
                        $originalLog['sync_direction']
                    ]);

                    $message = 'Sync retry initiated successfully!';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                $message = 'Error retrying sync: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'clear_logs') {
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->query("DELETE FROM channel_sync_logs WHERE started_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $message = 'Old sync logs cleared successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error clearing logs: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$channels = getChannels();
$syncStats = getSyncStats();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 25;
$offset = ($page - 1) * $limit;

$syncLogsData = getSyncLogs(null, $limit, $offset);
$syncLogs = $syncLogsData['logs'];
$totalLogs = $syncLogsData['total'];
$totalPages = ceil($totalLogs / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Logs - Inn Nexus</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .light-mode-header { @apply border-gray-200; }
        :root { --primary-color: #3b82f6; --accent-color: #10b981; }
        .dark { --primary-color: #60a5fa; --accent-color: #34d399; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sync Logs</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Monitor channel synchronization history and status</p>
                </div>
                <div class="flex gap-2">
                    <button
                        id="clearLogsBtn"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Clear Old Logs
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sync Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="activity" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Syncs</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($syncStats['total_syncs']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Successful</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($syncStats['successful_syncs']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($syncStats['failed_syncs']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="clock" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Duration</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo number_format($syncStats['avg_duration'], 1); ?>s</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel</label>
                        <select id="channelFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Channels</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Statuses</option>
                            <option value="Success">Success</option>
                            <option value="Failed">Failed</option>
                            <option value="Partial">Partial</option>
                            <option value="Running">Running</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sync Type</label>
                        <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Types</option>
                            <option value="Rates">Rates</option>
                            <option value="Availability">Availability</option>
                            <option value="Reservations">Reservations</option>
                            <option value="Inventory">Inventory</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button id="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Apply Filters
                    </button>
                    <button id="clearFilters" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg transition-colors">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Sync Logs Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Sync Activity</h2>
                    <?php if ($totalLogs > 0): ?>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Showing <?php echo min(($offset + 1), $totalLogs); ?>-<?php echo min(($offset + $limit), $totalLogs); ?> of <?php echo number_format($totalLogs); ?> logs
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-6">
                <?php if (empty($syncLogs)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No sync logs found</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Sync logs will appear here after channel synchronization operations.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Started</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Direction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Records</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($syncLogs as $log): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo date('M j, Y H:i', strtotime($log['started_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($log['channel_name']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">(<?php echo htmlspecialchars($log['channel_code']); ?>)</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($log['sync_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getDirectionColor($log['sync_direction']); ?>">
                                                <?php echo htmlspecialchars($log['sync_direction']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <div class="text-sm">
                                                <span class="text-green-600 dark:text-green-400"><?php echo number_format($log['records_successful'] ?? 0); ?>✓</span>
                                                <?php if (($log['records_failed'] ?? 0) > 0): ?>
                                                    <span class="text-red-600 dark:text-red-400 ml-1"><?php echo number_format($log['records_failed']); ?>✗</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                of <?php echo number_format(($log['records_successful'] ?? 0) + ($log['records_failed'] ?? 0)); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $log['duration_seconds'] ? number_format($log['duration_seconds'], 1) . 's' : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($log['status']); ?>">
                                                <?php echo htmlspecialchars($log['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <?php if ($log['status'] === 'Failed' && $log['errors']): ?>
                                                    <button
                                                        class="view-errors-btn text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                                                        data-errors="<?php echo htmlspecialchars($log['errors']); ?>"
                                                        title="View Errors"
                                                    >
                                                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($log['status'] === 'Failed'): ?>
                                                    <button
                                                        class="retry-sync-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                        data-log-id="<?php echo $log['id']; ?>"
                                                        title="Retry Sync"
                                                    >
                                                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>"
                                       class="px-3 py-2 border rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Error Details Modal -->
    <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[80vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Sync Errors</h2>
            </div>

            <div class="p-6">
                <pre id="errorContent" class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap overflow-x-auto"></pre>
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <button id="closeErrorModal" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Modal functionality
        const errorModal = document.getElementById('errorModal');
        const closeErrorModal = document.getElementById('closeErrorModal');
        const clearLogsBtn = document.getElementById('clearLogsBtn');

        function openErrorModal(errors) {
            document.getElementById('errorContent').textContent = errors;
            errorModal.classList.remove('hidden');
            errorModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeErrorModalHandler() {
            errorModal.classList.add('hidden');
            errorModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        // Close error modal
        if (closeErrorModal) {
            closeErrorModal.addEventListener('click', closeErrorModalHandler);
        }

        // Close modal when clicking outside
        if (errorModal) {
            errorModal.addEventListener('click', function(e) {
                if (e.target === errorModal) {
                    closeErrorModalHandler();
                }
            });
        }

        // View errors functionality
        document.querySelectorAll('.view-errors-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const errors = this.getAttribute('data-errors');
                openErrorModal(errors);
            });
        });

        // Retry sync functionality
        document.querySelectorAll('.retry-sync-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to retry this sync?')) {
                    const logId = this.getAttribute('data-log-id');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="retry_sync">
                        <input type="hidden" name="log_id" value="${logId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Clear logs functionality
        if (clearLogsBtn) {
            clearLogsBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear sync logs older than 30 days? This action cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="clear_logs">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Filter functionality
        const channelFilter = document.getElementById('channelFilter');
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');

        function applyFilters() {
            const channelValue = channelFilter.value;
            const statusValue = statusFilter.value;
            const typeValue = typeFilter.value;

            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let show = true;

                if (channelValue && !row.cells[1].textContent.includes(channelFilter.options[channelFilter.selectedIndex].text)) {
                    show = false;
                }

                if (statusValue && !row.cells[7].textContent.includes(statusValue)) {
                    show = false;
                }

                if (typeValue && !row.cells[2].textContent.includes(typeValue)) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', applyFilters);
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                channelFilter.value = '';
                statusFilter.value = '';
                typeFilter.value = '';
                applyFilters();
            });
        }
    </script>
</body>
</html>

<?php
function getDirectionColor($direction) {
    switch ($direction) {
        case 'Push':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'Pull':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Both':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'Success':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'Partial':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'Running':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
?>
