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

// Get channel rates from API with database fallback
function fetchRatesFromAPI($channelId = null) {
    $apiUrl = 'http://localhost/hmscore1last1/api/channel-test-data.php?action=get_rates_data';

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        error_log('API request failed, falling back to database');
        return getChannelRatesFromDB($channelId);
    }

    $data = json_decode($response, true);

    if (!$data || !$data['success']) {
        error_log('API returned error: ' . ($data['error'] ?? 'Unknown error') . ', falling back to database');
        return getChannelRatesFromDB($channelId);
    }

    $rates = $data['data']['rates'] ?? [];

    // Transform API data to match expected format
    $transformedRates = [];
    foreach ($rates as $rate) {
        // Map channel codes to IDs
        $channelMapping = [
            'booking.com' => 1,
            'expedia' => 2,
            'agoda' => 3
        ];

        $channelIdFromAPI = $channelMapping[$rate['channel_code']] ?? null;

        // Filter by channel if specified
        if ($channelId && $channelIdFromAPI !== $channelId) {
            continue;
        }

        $transformedRates[] = [
            'id' => null, // API doesn't provide IDs
            'channel_id' => $channelIdFromAPI,
            'room_type' => $rate['room_type'],
            'rate_type' => ucfirst(strtolower(str_replace('_', ' ', $rate['rate_plan']))),
            'base_rate' => $rate['base_rate'],
            'extra_person_rate' => $rate['extra_adult_rate'] ?? 0,
            'child_rate' => $rate['child_rate'] ?? 0,
            'breakfast_included' => $rate['breakfast_included'] ? 1 : 0,
            'breakfast_rate' => $rate['breakfast_rate'] ?? 0,
            'valid_from' => $rate['valid_from'],
            'valid_to' => $rate['valid_to'],
            'minimum_stay' => $rate['minimum_stay'] ?? 1,
            'maximum_stay' => $rate['maximum_stay'] ?? null,
            'closed_to_arrival' => $rate['restrictions']['closed_to_arrival'] ?? false,
            'closed_to_departure' => $rate['restrictions']['closed_to_departure'] ?? false,
            'status' => 'Active',
            'channel_name' => ucfirst(str_replace('.', '.com', $rate['channel_code'])),
            'channel_code' => $rate['channel_code'],
            'created_at' => $rate['last_updated'],
            'updated_at' => $rate['last_updated']
        ];
    }

    return $transformedRates;
}

// Fallback function to get rates from database
function getChannelRatesFromDB($channelId = null) {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $sql = "
            SELECT
                cr.*,
                c.display_name as channel_name,
                c.name as channel_code
            FROM channel_rates cr
            JOIN channels c ON cr.channel_id = c.id
        ";

        $params = [];
        if ($channelId) {
            $sql .= " WHERE cr.channel_id = ?";
            $params[] = $channelId;
        }

        $sql .= " ORDER BY c.display_name, cr.room_type, cr.valid_from";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching channel rates from DB: ' . $e->getMessage());
        return [];
    }
}

// Get room types from existing rooms table
function getRoomTypes() {
    $pdo = getPdo();
    if (!$pdo) return ['Single', 'Double', 'Deluxe', 'Suite'];

    try {
        $stmt = $pdo->query("SELECT DISTINCT room_type FROM rooms WHERE room_type IS NOT NULL ORDER BY room_type");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return !empty($types) ? $types : ['Single', 'Double', 'Deluxe', 'Suite'];
    } catch (Throwable $e) {
        return ['Single', 'Double', 'Deluxe', 'Suite'];
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_rate' || $action === 'edit_rate') {
        $rateData = [
            'channel_id' => (int)$_POST['channel_id'],
            'room_type' => $_POST['room_type'],
            'rate_type' => $_POST['rate_type'] ?? 'Standard',
            'base_rate' => (float)$_POST['base_rate'],
            'extra_person_rate' => (float)($_POST['extra_person_rate'] ?? 0),
            'child_rate' => (float)($_POST['child_rate'] ?? 0),
            'breakfast_included' => isset($_POST['breakfast_included']) ? 1 : 0,
            'breakfast_rate' => (float)($_POST['breakfast_rate'] ?? 0),
            'valid_from' => $_POST['valid_from'],
            'valid_to' => $_POST['valid_to'],
            'minimum_stay' => (int)($_POST['minimum_stay'] ?? 1),
            'maximum_stay' => !empty($_POST['maximum_stay']) ? (int)$_POST['maximum_stay'] : null,
            'closed_to_arrival' => isset($_POST['closed_to_arrival']) ? 1 : 0,
            'closed_to_departure' => isset($_POST['closed_to_departure']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'Active'
        ];

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'add_rate') {
                    $stmt = $pdo->prepare("
                        INSERT INTO channel_rates (
                            channel_id, room_type, rate_type, base_rate, extra_person_rate, child_rate,
                            breakfast_included, breakfast_rate, valid_from, valid_to, minimum_stay,
                            maximum_stay, closed_to_arrival, closed_to_departure, status, created_at, updated_at
                        ) VALUES (
                            :channel_id, :room_type, :rate_type, :base_rate, :extra_person_rate, :child_rate,
                            :breakfast_included, :breakfast_rate, :valid_from, :valid_to, :minimum_stay,
                            :maximum_stay, :closed_to_arrival, :closed_to_departure, :status, NOW(), NOW()
                        )
                    ");
                    $stmt->execute($rateData);
                    $message = 'Rate added successfully!';
                    $messageType = 'success';
                } else {
                    $rateId = (int)$_POST['rate_id'];
                    $stmt = $pdo->prepare("
                        UPDATE channel_rates SET
                            channel_id = :channel_id, room_type = :room_type, rate_type = :rate_type,
                            base_rate = :base_rate, extra_person_rate = :extra_person_rate, child_rate = :child_rate,
                            breakfast_included = :breakfast_included, breakfast_rate = :breakfast_rate,
                            valid_from = :valid_from, valid_to = :valid_to, minimum_stay = :minimum_stay,
                            maximum_stay = :maximum_stay, closed_to_arrival = :closed_to_arrival,
                            closed_to_departure = :closed_to_departure, status = :status, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute(array_merge($rateData, ['id' => $rateId]));
                    $message = 'Rate updated successfully!';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                $message = 'Error saving rate: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_rate') {
        $rateId = (int)$_POST['rate_id'];
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM channel_rates WHERE id = ?");
                $stmt->execute([$rateId]);
                $message = 'Rate deleted successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error deleting rate: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$channels = getChannels();
$channelRates = fetchRatesFromAPI();
$roomTypes = getRoomTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Channel Rates Management - Inn Nexus</title>
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
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Channel Rates Management</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Manage rates for different channels and room types</p>
                </div>
                <div class="flex gap-2">
                    <button
                        id="syncFromAPI"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Sync from API
                    </button>
                    <button
                        id="addRateBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Rate
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rates Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Channel Rates</h2>
                    <div class="flex gap-2">
                        <select id="channelFilter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Channels</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if (empty($channelRates)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="calculator" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No rates configured</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Get started by adding rates for your channels.</p>
                        <button
                            id="addFirstRateBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 mx-auto transition-colors"
                        >
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Add Your First Rate
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Room Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Base Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valid Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($channelRates as $rate): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($rate['channel_name']); ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 ml-2">(<?php echo htmlspecialchars($rate['channel_code']); ?>)</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($rate['room_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getRateTypeColor($rate['rate_type']); ?>">
                                                <?php echo htmlspecialchars($rate['rate_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            ₱<?php echo number_format($rate['base_rate'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('M j, Y', strtotime($rate['valid_from'])); ?> - <?php echo date('M j, Y', strtotime($rate['valid_to'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($rate['status']); ?>">
                                                <?php echo htmlspecialchars($rate['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button
                                                    class="edit-rate-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                    data-rate='<?php echo json_encode($rate); ?>'
                                                >
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button
                                                    class="delete-rate-btn text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                    data-rate-id="<?php echo $rate['id']; ?>"
                                                >
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rate Statistics -->
        <?php if (!empty($channelRates)): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <?php
                $totalRates = count($channelRates);
                $activeRates = count(array_filter($channelRates, fn($r) => $r['status'] === 'Active'));
                $avgBaseRate = !empty($channelRates) ? array_sum(array_column($channelRates, 'base_rate')) / count($channelRates) : 0;
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="tag" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Rates</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $totalRates; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Rates</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $activeRates; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="trending-up" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Base Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">₱<?php echo number_format($avgBaseRate, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Rate Modal -->
    <div id="rateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="rateModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Add New Rate</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" id="rateModalAction" value="add_rate">
                <input type="hidden" name="rate_id" id="rateModalRateId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel *</label>
                        <select name="channel_id" id="rateModalChannelId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Channel</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Room Type *</label>
                        <select name="room_type" id="rateModalRoomType" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Room Type</option>
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rate Type</label>
                        <select name="rate_type" id="rateModalRateType"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Standard">Standard</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Promotional">Promotional</option>
                            <option value="Last Minute">Last Minute</option>
                            <option value="Weekend">Weekend</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="status" id="rateModalStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Base Rate (₱) *</label>
                        <input type="number" step="0.01" name="base_rate" id="rateModalBaseRate" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Extra Person Rate (₱)</label>
                        <input type="number" step="0.01" name="extra_person_rate" id="rateModalExtraPersonRate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Child Rate (₱)</label>
                        <input type="number" step="0.01" name="child_rate" id="rateModalChildRate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Valid From *</label>
                        <input type="date" name="valid_from" id="rateModalValidFrom" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Valid To *</label>
                        <input type="date" name="valid_to" id="rateModalValidTo" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Stay</label>
                        <input type="number" min="1" name="minimum_stay" id="rateModalMinimumStay"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maximum Stay</label>
                        <input type="number" min="1" name="maximum_stay" id="rateModalMaximumStay"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="breakfast_included" id="rateModalBreakfastIncluded" value="1"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="rateModalBreakfastIncluded" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Breakfast Included
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Breakfast Rate (₱)</label>
                        <input type="number" step="0.01" name="breakfast_rate" id="rateModalBreakfastRate"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="closed_to_arrival" id="rateModalClosedToArrival" value="1"
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="rateModalClosedToArrival" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Closed to Arrival
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="closed_to_departure" id="rateModalClosedToDeparture" value="1"
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="rateModalClosedToDeparture" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Closed to Departure
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <span id="rateModalSubmitText">Add Rate</span>
                    </button>
                    <button type="button" id="cancelRateModal" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
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
        const rateModal = document.getElementById('rateModal');
        const addRateBtn = document.getElementById('addRateBtn');
        const addFirstRateBtn = document.getElementById('addFirstRateBtn');
        const cancelRateModal = document.getElementById('cancelRateModal');
        const channelFilter = document.getElementById('channelFilter');

        function openRateModal() {
            rateModal.classList.remove('hidden');
            rateModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeRateModal() {
            rateModal.classList.add('hidden');
            rateModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            resetRateModal();
        }

        function resetRateModal() {
            document.getElementById('rateModalAction').value = 'add_rate';
            document.getElementById('rateModalRateId').value = '';
            document.getElementById('rateModalTitle').textContent = 'Add New Rate';
            document.getElementById('rateModalSubmitText').textContent = 'Add Rate';

            // Reset form
            const form = rateModal.querySelector('form');
            form.reset();
        }

        // Add rate buttons
        if (addRateBtn) {
            addRateBtn.addEventListener('click', openRateModal);
        }

        if (addFirstRateBtn) {
            addFirstRateBtn.addEventListener('click', openRateModal);
        }

        // Cancel modal
        if (cancelRateModal) {
            cancelRateModal.addEventListener('click', closeRateModal);
        }

        // Close modal when clicking outside
        if (rateModal) {
            rateModal.addEventListener('click', function(e) {
                if (e.target === rateModal) {
                    closeRateModal();
                }
            });
        }

        // Filter functionality
        if (channelFilter) {
            channelFilter.addEventListener('change', function() {
                const selectedChannel = this.value;
                const rows = document.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const channelCell = row.cells[0];
                    if (!selectedChannel || channelCell.textContent.includes(channelFilter.options[channelFilter.selectedIndex].text)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Edit rate functionality
        document.querySelectorAll('.edit-rate-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const rate = JSON.parse(this.getAttribute('data-rate'));

                document.getElementById('rateModalAction').value = 'edit_rate';
                document.getElementById('rateModalRateId').value = rate.id;
                document.getElementById('rateModalTitle').textContent = 'Edit Rate';
                document.getElementById('rateModalSubmitText').textContent = 'Update Rate';

                // Populate form
                document.getElementById('rateModalChannelId').value = rate.channel_id;
                document.getElementById('rateModalRoomType').value = rate.room_type;
                document.getElementById('rateModalRateType').value = rate.rate_type;
                document.getElementById('rateModalStatus').value = rate.status;
                document.getElementById('rateModalBaseRate').value = rate.base_rate;
                document.getElementById('rateModalExtraPersonRate').value = rate.extra_person_rate;
                document.getElementById('rateModalChildRate').value = rate.child_rate;
                document.getElementById('rateModalBreakfastIncluded').checked = rate.breakfast_included;
                document.getElementById('rateModalBreakfastRate').value = rate.breakfast_rate;
                document.getElementById('rateModalValidFrom').value = rate.valid_from;
                document.getElementById('rateModalValidTo').value = rate.valid_to;
                document.getElementById('rateModalMinimumStay').value = rate.minimum_stay;
                document.getElementById('rateModalMaximumStay').value = rate.maximum_stay || '';
                document.getElementById('rateModalClosedToArrival').checked = rate.closed_to_arrival;
                document.getElementById('rateModalClosedToDeparture').checked = rate.closed_to_departure;

                openRateModal();
            });
        });

        // Sync from API functionality
        const syncFromAPI = document.getElementById('syncFromAPI');
        if (syncFromAPI) {
            syncFromAPI.addEventListener('click', async function() {
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Syncing...';
                this.disabled = true;

                try {
                    // Call the API
                    const response = await fetch('http://localhost/hmscore1last1/api/channel-test-data.php?action=get_rates_data');
                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        showMessage('Rates synced successfully from API! ' + data.data.rates.length + ' rates loaded.', 'success');

                        // Reload the page to show new data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage('API Error: ' + (data.error || 'Unknown error'), 'error');
                    }
                } catch (error) {
                    showMessage('Network error: Could not connect to API. ' + error.message, 'error');
                } finally {
                    // Reset button
                    this.innerHTML = originalText;
                    this.disabled = false;
                    lucide.createIcons();
                }
            });
        }

        // Message display function
        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}`;
            messageDiv.textContent = message;

            const container = document.querySelector('.mb-8');
            container.appendChild(messageDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>

<?php
function getRateTypeColor($rateType) {
    switch ($rateType) {
        case 'Standard':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'Corporate':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'Promotional':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Last Minute':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        case 'Weekend':
            return 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'Active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        case 'Expired':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
?>
