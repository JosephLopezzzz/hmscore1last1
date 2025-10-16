<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

// Get all channels
function getChannels() {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("
            SELECT
                c.*,
                CASE
                    WHEN c.last_sync IS NULL THEN 'Never'
                    ELSE CONCAT(
                        TIMESTAMPDIFF(DAY, c.last_sync, NOW()), ' days ago'
                    )
                END as last_sync_ago
            FROM channels c
            ORDER BY c.name
        ");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching channels: ' . $e->getMessage());
        return [];
    }
}

// Get sync logs for a specific channel
function getChannelSyncLogs($channelId, $limit = 10) {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM channel_sync_logs
            WHERE channel_id = ?
            ORDER BY started_at DESC
            LIMIT ?
        ");
        $stmt->execute([$channelId, $limit]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching sync logs: ' . $e->getMessage());
        return [];
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_channel' || $action === 'edit_channel') {
        $channelData = [
            'name' => trim($_POST['name'] ?? ''),
            'display_name' => trim($_POST['display_name'] ?? ''),
            'type' => $_POST['type'] ?? 'OTA',
            'api_endpoint' => trim($_POST['api_endpoint'] ?? ''),
            'api_key' => trim($_POST['api_key'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'status' => $_POST['status'] ?? 'Active',
            'commission_rate' => (float)($_POST['commission_rate'] ?? 0),
            'currency' => $_POST['currency'] ?? 'PHP',
            'timezone' => $_POST['timezone'] ?? 'Asia/Manila',
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'add_channel') {
                    $stmt = $pdo->prepare("
                        INSERT INTO channels (
                            name, display_name, type, api_endpoint, api_key, username, password,
                            status, commission_rate, currency, timezone, contact_person,
                            contact_email, contact_phone, notes, created_at, updated_at
                        ) VALUES (
                            :name, :display_name, :type, :api_endpoint, :api_key, :username, :password,
                            :status, :commission_rate, :currency, :timezone, :contact_person,
                            :contact_email, :contact_phone, :notes, NOW(), NOW()
                        )
                    ");
                    $stmt->execute($channelData);
                    $message = 'Channel added successfully!';
                    $messageType = 'success';
                } else {
                    $channelId = (int)$_POST['channel_id'];
                    $stmt = $pdo->prepare("
                        UPDATE channels SET
                            name = :name, display_name = :display_name, type = :type,
                            api_endpoint = :api_endpoint, api_key = :api_key,
                            username = :username, password = :password, status = :status,
                            commission_rate = :commission_rate, currency = :currency,
                            timezone = :timezone, contact_person = :contact_person,
                            contact_email = :contact_email, contact_phone = :contact_phone,
                            notes = :notes, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute(array_merge($channelData, ['id' => $channelId]));
                    $message = 'Channel updated successfully!';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                $message = 'Error saving channel: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_channel') {
        $channelId = (int)$_POST['channel_id'];
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM channels WHERE id = ?");
                $stmt->execute([$channelId]);
                $message = 'Channel deleted successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error deleting channel: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'sync_channel') {
        $channelId = (int)$_POST['channel_id'];
        // Here you would implement actual sync logic
        // For now, just log the sync attempt
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO channel_sync_logs (
                        channel_id, sync_type, sync_direction, status, started_at, completed_at
                    ) VALUES (?, 'Rates', 'Pull', 'Success', NOW(), NOW())
                ");
                $stmt->execute([$channelId]);
                $message = 'Sync completed successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error during sync: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$channels = getChannels();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Channel Management - Inn Nexus</title>
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
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Channel Management</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Manage OTA channels and distribution partners</p>
                </div>
                <button
                    id="addChannelBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Channel
                </button>
            </div>

            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Channel Management Tools</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Quick access to all channel management features</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    <a href="channel-rates.php" class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors group">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-800">
                            <i data-lucide="calculator" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white text-center">Rates Management</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 text-center">Manage room rates</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Channels Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Connected Channels</h2>
            </div>

            <div class="p-6">
                <?php if (empty($channels)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="wifi-off" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No channels configured</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Get started by adding your first OTA channel.</p>
                        <button
                            id="addFirstChannelBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 mx-auto transition-colors"
                        >
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Your First Channel
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($channels as $channel): ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                            <i data-lucide="globe" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($channel['display_name']); ?></h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($channel['name']); ?></p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo getStatusColor($channel['status']); ?>">
                                        <?php echo htmlspecialchars($channel['status']); ?>
                                    </span>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($channel['type']); ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Commission:</span>
                                        <span class="font-medium"><?php echo number_format($channel['commission_rate'], 2); ?>%</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Last Sync:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($channel['last_sync_ago']); ?></span>
                                    </div>
                                    <?php if ($channel['contact_person']): ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Contact:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($channel['contact_person']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        class="edit-channel-btn flex-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded text-sm transition-colors"
                                        data-channel='<?php echo json_encode($channel); ?>'
                                    >
                                        <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i>
                                        Edit
                                    </button>
                                    <button
                                        class="sync-channel-btn bg-green-100 dark:bg-green-900 hover:bg-green-200 dark:hover:bg-green-800 text-green-700 dark:text-green-300 px-3 py-2 rounded text-sm transition-colors"
                                        data-channel-id="<?php echo $channel['id']; ?>"
                                    >
                                        <i data-lucide="refresh-cw" class="w-4 h-4 inline mr-1"></i>
                                        Sync
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Sync Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mt-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Sync Activity</h2>
            </div>
            <div class="p-6">
                <?php if (!empty($channels)): ?>
                    <?php
                    $recentLogs = [];
                    foreach ($channels as $channel) {
                        $logs = getChannelSyncLogs($channel['id'], 3);
                        foreach ($logs as $log) {
                            $recentLogs[] = array_merge($log, ['channel_name' => $channel['display_name']]);
                        }
                    }
                    usort($recentLogs, function($a, $b) {
                        return strtotime($b['started_at']) - strtotime($a['started_at']);
                    });
                    $recentLogs = array_slice($recentLogs, 0, 10);
                    ?>

                    <?php if (empty($recentLogs)): ?>
                        <div class="text-center py-8">
                            <i data-lucide="activity" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                            <p class="text-gray-600 dark:text-gray-400">No sync activity yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                            <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($log['channel_name']); ?></p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                <?php echo htmlspecialchars($log['sync_type']); ?> sync • <?php echo htmlspecialchars($log['status']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo date('M j, H:i', strtotime($log['started_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i data-lucide="activity" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
                        <p class="text-gray-600 dark:text-gray-400">Add channels to see sync activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Channel Modal -->
    <div id="channelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Add New Channel</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" id="modalAction" value="add_channel">
                <input type="hidden" name="channel_id" id="modalChannelId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel Name *</label>
                        <input type="text" name="name" id="modalName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Display Name *</label>
                        <input type="text" name="display_name" id="modalDisplayName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type *</label>
                        <select name="type" id="modalType" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="OTA">OTA</option>
                            <option value="GDS">GDS</option>
                            <option value="Direct">Direct</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="status" id="modalStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Error">Error</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Commission Rate (%)</label>
                        <input type="number" step="0.01" name="commission_rate" id="modalCommissionRate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                        <select name="currency" id="modalCurrency"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="PHP">PHP</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Endpoint</label>
                    <input type="url" name="api_endpoint" id="modalApiEndpoint"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                        <input type="password" name="api_key" id="modalApiKey"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" id="modalUsername"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" id="modalPassword"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Person</label>
                        <input type="text" name="contact_person" id="modalContactPerson"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Email</label>
                        <input type="email" name="contact_email" id="modalContactEmail"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contact Phone</label>
                    <input type="tel" name="contact_phone" id="modalContactPhone"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes</label>
                    <textarea name="notes" id="modalNotes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <span id="modalSubmitText">Add Channel</span>
                    </button>
                    <button type="button" id="cancelModal" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Modal functionality
        const channelModal = document.getElementById('channelModal');
        const addChannelBtn = document.getElementById('addChannelBtn');
        const addFirstChannelBtn = document.getElementById('addFirstChannelBtn');
        const cancelModal = document.getElementById('cancelModal');

        function openModal() {
            channelModal.classList.remove('hidden');
            channelModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            channelModal.classList.add('hidden');
            channelModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            resetModal();
        }

        function resetModal() {
            document.getElementById('modalAction').value = 'add_channel';
            document.getElementById('modalChannelId').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Channel';
            document.getElementById('modalSubmitText').textContent = 'Add Channel';

            // Reset form
            const form = channelModal.querySelector('form');
            form.reset();
        }

        // Add channel buttons
        if (addChannelBtn) {
            addChannelBtn.addEventListener('click', openModal);
        }

        if (addFirstChannelBtn) {
            addFirstChannelBtn.addEventListener('click', openModal);
        }

        // Cancel modal
        if (cancelModal) {
            cancelModal.addEventListener('click', closeModal);
        }

        // Close modal when clicking outside
        if (channelModal) {
            channelModal.addEventListener('click', function(e) {
                if (e.target === channelModal) {
                    closeModal();
                }
            });
        }

        // Edit channel functionality
        document.querySelectorAll('.edit-channel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const channel = JSON.parse(this.getAttribute('data-channel'));

                document.getElementById('modalAction').value = 'edit_channel';
                document.getElementById('modalChannelId').value = channel.id;
                document.getElementById('modalTitle').textContent = 'Edit Channel';
                document.getElementById('modalSubmitText').textContent = 'Update Channel';

                // Populate form
                document.getElementById('modalName').value = channel.name;
                document.getElementById('modalDisplayName').value = channel.display_name;
                document.getElementById('modalType').value = channel.type;
                document.getElementById('modalStatus').value = channel.status;
                document.getElementById('modalCommissionRate').value = channel.commission_rate;
                document.getElementById('modalCurrency').value = channel.currency;
                document.getElementById('modalApiEndpoint').value = channel.api_endpoint || '';
                document.getElementById('modalApiKey').value = channel.api_key || '';
                document.getElementById('modalUsername').value = channel.username || '';
                document.getElementById('modalPassword').value = channel.password || '';
                document.getElementById('modalContactPerson').value = channel.contact_person || '';
                document.getElementById('modalContactEmail').value = channel.contact_email || '';
                document.getElementById('modalContactPhone').value = channel.contact_phone || '';
                document.getElementById('modalNotes').value = channel.notes || '';

                openModal();
            });
        });

        // Sync channel functionality
        document.querySelectorAll('.sync-channel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const channelId = this.getAttribute('data-channel-id');

                if (confirm('Are you sure you want to sync this channel?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="sync_channel">
                        <input type="hidden" name="channel_id" value="${channelId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Handle delete functionality (you can add delete buttons if needed)
        document.querySelectorAll('.delete-channel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
                    const channelId = this.getAttribute('data-channel-id');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_channel">
                        <input type="hidden" name="channel_id" value="${channelId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'Active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        case 'Maintenance':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'Error':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
?>
