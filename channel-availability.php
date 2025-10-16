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

// Get channel availability
function getChannelAvailability($channelId = null, $startDate = null, $endDate = null) {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $sql = "
            SELECT
                ca.*,
                c.display_name as channel_name,
                c.name as channel_code
            FROM channel_availability ca
            JOIN channels c ON ca.channel_id = c.id
        ";

        $params = [];
        $conditions = [];

        if ($channelId) {
            $conditions[] = "ca.channel_id = ?";
            $params[] = $channelId;
        }

        if ($startDate) {
            $conditions[] = "ca.available_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "ca.available_date <= ?";
            $params[] = $endDate;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY ca.available_date, c.display_name, ca.room_type";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching channel availability: ' . $e->getMessage());
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

// Get available rooms count for a room type
function getAvailableRoomsCount($roomType) {
    $pdo = getPdo();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_type = ? AND status = 'Vacant'");
        $stmt->execute([$roomType]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_availability' || $action === 'edit_availability') {
        $availabilityData = [
            'channel_id' => (int)$_POST['channel_id'],
            'room_type' => $_POST['room_type'],
            'available_date' => $_POST['available_date'],
            'total_rooms' => (int)$_POST['total_rooms'],
            'booked_rooms' => (int)($_POST['booked_rooms'] ?? 0),
            'blocked_rooms' => (int)($_POST['blocked_rooms'] ?? 0),
            'minimum_stay' => (int)($_POST['minimum_stay'] ?? 1),
            'maximum_stay' => !empty($_POST['maximum_stay']) ? (int)$_POST['maximum_stay'] : null,
            'rate' => (float)($_POST['rate'] ?? 0),
            'status' => $_POST['status'] ?? 'Open'
        ];

        // Calculate available rooms
        $availabilityData['available_rooms'] = max(0, $availabilityData['total_rooms'] - $availabilityData['booked_rooms'] - $availabilityData['blocked_rooms']);

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'add_availability') {
                    $stmt = $pdo->prepare("
                        INSERT INTO channel_availability (
                            channel_id, room_type, available_date, total_rooms, booked_rooms, blocked_rooms,
                            available_rooms, minimum_stay, maximum_stay, rate, status, last_updated
                        ) VALUES (
                            :channel_id, :room_type, :available_date, :total_rooms, :booked_rooms, :blocked_rooms,
                            :available_rooms, :minimum_stay, :maximum_stay, :rate, :status, NOW()
                        )
                        ON DUPLICATE KEY UPDATE
                            total_rooms = VALUES(total_rooms),
                            booked_rooms = VALUES(booked_rooms),
                            blocked_rooms = VALUES(blocked_rooms),
                            available_rooms = VALUES(available_rooms),
                            minimum_stay = VALUES(minimum_stay),
                            maximum_stay = VALUES(maximum_stay),
                            rate = VALUES(rate),
                            status = VALUES(status),
                            last_updated = NOW()
                    ");
                    $stmt->execute($availabilityData);
                    $message = 'Availability updated successfully!';
                    $messageType = 'success';
                } else {
                    $availabilityId = (int)$_POST['availability_id'];
                    $stmt = $pdo->prepare("
                        UPDATE channel_availability SET
                            channel_id = :channel_id, room_type = :room_type, available_date = :available_date,
                            total_rooms = :total_rooms, booked_rooms = :booked_rooms, blocked_rooms = :blocked_rooms,
                            available_rooms = :available_rooms, minimum_stay = :minimum_stay,
                            maximum_stay = :maximum_stay, rate = :rate, status = :status, last_updated = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute(array_merge($availabilityData, ['id' => $availabilityId]));
                    $message = 'Availability updated successfully!';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                $message = 'Error saving availability: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_availability') {
        $availabilityId = (int)$_POST['availability_id'];
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM channel_availability WHERE id = ?");
                $stmt->execute([$availabilityId]);
                $message = 'Availability deleted successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error deleting availability: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'bulk_update') {
        $channelId = (int)$_POST['channel_id'];
        $roomType = $_POST['room_type'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $totalRooms = (int)$_POST['total_rooms'];

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                // Generate dates between start and end
                $dates = [];
                $currentDate = new DateTime($startDate);
                $endDateTime = new DateTime($endDate);

                while ($currentDate <= $endDateTime) {
                    $dates[] = $currentDate->format('Y-m-d');
                    $currentDate->add(new DateInterval('P1D'));
                }

                $stmt = $pdo->prepare("
                    INSERT INTO channel_availability (
                        channel_id, room_type, available_date, total_rooms, booked_rooms, blocked_rooms,
                        available_rooms, status, last_updated
                    ) VALUES (?, ?, ?, ?, 0, 0, ?, 'Open', NOW())
                    ON DUPLICATE KEY UPDATE
                        total_rooms = VALUES(total_rooms),
                        available_rooms = VALUES(available_rooms),
                        last_updated = NOW()
                ");

                foreach ($dates as $date) {
                    $stmt->execute([$channelId, $roomType, $date, $totalRooms, $totalRooms]);
                }

                $message = 'Bulk availability updated successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error bulk updating availability: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$channels = getChannels();
$roomTypes = getRoomTypes();

// Default date range (next 30 days)
$defaultStartDate = date('Y-m-d');
$defaultEndDate = date('Y-m-d', strtotime('+30 days'));

$channelAvailability = getChannelAvailability(null, $defaultStartDate, $defaultEndDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Channel Availability Management - Inn Nexus</title>
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
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Channel Availability Management</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Manage room availability across different channels and dates</p>
                </div>
                <div class="flex gap-2">
                    <button
                        id="addAvailabilityBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Availability
                    </button>
                    <button
                        id="bulkUpdateBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        Bulk Update
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filters</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Room Type</label>
                        <select id="roomTypeFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Room Types</option>
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="date" id="startDateFilter" value="<?php echo $defaultStartDate; ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="date" id="endDateFilter" value="<?php echo $defaultEndDate; ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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

        <!-- Availability Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Availability Overview</h2>
            </div>

            <div class="p-6">
                <?php if (empty($channelAvailability)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="calendar-off" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No availability data</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Configure availability for your channels to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Room Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Available</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Booked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Blocked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($channelAvailability as $availability): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo date('M j, Y', strtotime($availability['available_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($availability['channel_name']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">(<?php echo htmlspecialchars($availability['channel_code']); ?>)</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($availability['room_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $availability['total_rooms']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getAvailabilityColor($availability['available_rooms']); ?>">
                                                <?php echo $availability['available_rooms']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $availability['booked_rooms']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $availability['blocked_rooms']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $availability['rate'] ? '₱' . number_format($availability['rate'], 2) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($availability['status']); ?>">
                                                <?php echo htmlspecialchars($availability['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button
                                                    class="edit-availability-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                    data-availability='<?php echo json_encode($availability); ?>'
                                                >
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button
                                                    class="delete-availability-btn text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                    data-availability-id="<?php echo $availability['id']; ?>"
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
    </main>

    <!-- Availability Modal -->
    <div id="availabilityModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="availabilityModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Add Availability</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" id="availabilityModalAction" value="add_availability">
                <input type="hidden" name="availability_id" id="availabilityModalId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel *</label>
                        <select name="channel_id" id="availabilityModalChannelId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Channel</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Room Type *</label>
                        <select name="room_type" id="availabilityModalRoomType" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Room Type</option>
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date *</label>
                        <input type="date" name="available_date" id="availabilityModalDate" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                        <select name="status" id="availabilityModalStatus"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                            <option value="On Request">On Request</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Rooms *</label>
                        <input type="number" min="0" name="total_rooms" id="availabilityModalTotalRooms" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Booked Rooms</label>
                        <input type="number" min="0" name="booked_rooms" id="availabilityModalBookedRooms"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Blocked Rooms</label>
                        <input type="number" min="0" name="blocked_rooms" id="availabilityModalBlockedRooms"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Stay</label>
                        <input type="number" min="1" name="minimum_stay" id="availabilityModalMinimumStay"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maximum Stay</label>
                        <input type="number" min="1" name="maximum_stay" id="availabilityModalMaximumStay"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rate (₱)</label>
                    <input type="number" step="0.01" min="0" name="rate" id="availabilityModalRate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <span id="availabilityModalSubmitText">Add Availability</span>
                    </button>
                    <button type="button" id="cancelAvailabilityModal" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div id="bulkUpdateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Bulk Update Availability</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" value="bulk_update">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel *</label>
                        <select name="channel_id" id="bulkChannelId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Channel</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Room Type *</label>
                        <select name="room_type" id="bulkRoomType" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Room Type</option>
                            <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date *</label>
                        <input type="date" name="start_date" id="bulkStartDate" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date *</label>
                        <input type="date" name="end_date" id="bulkEndDate" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Rooms *</label>
                    <input type="number" min="0" name="total_rooms" id="bulkTotalRooms" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This will set the total rooms for all dates in the selected range</p>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Update All Dates
                    </button>
                    <button type="button" id="cancelBulkUpdate" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
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
        const availabilityModal = document.getElementById('availabilityModal');
        const bulkUpdateModal = document.getElementById('bulkUpdateModal');
        const addAvailabilityBtn = document.getElementById('addAvailabilityBtn');
        const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
        const cancelAvailabilityModal = document.getElementById('cancelAvailabilityModal');
        const cancelBulkUpdate = document.getElementById('cancelBulkUpdate');

        function openAvailabilityModal() {
            availabilityModal.classList.remove('hidden');
            availabilityModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeAvailabilityModal() {
            availabilityModal.classList.add('hidden');
            availabilityModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            resetAvailabilityModal();
        }

        function openBulkUpdateModal() {
            bulkUpdateModal.classList.remove('hidden');
            bulkUpdateModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeBulkUpdateModal() {
            bulkUpdateModal.classList.add('hidden');
            bulkUpdateModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function resetAvailabilityModal() {
            document.getElementById('availabilityModalAction').value = 'add_availability';
            document.getElementById('availabilityModalId').value = '';
            document.getElementById('availabilityModalTitle').textContent = 'Add Availability';
            document.getElementById('availabilityModalSubmitText').textContent = 'Add Availability';

            // Reset form
            const form = availabilityModal.querySelector('form');
            form.reset();
        }

        // Add availability button
        if (addAvailabilityBtn) {
            addAvailabilityBtn.addEventListener('click', openAvailabilityModal);
        }

        // Bulk update button
        if (bulkUpdateBtn) {
            bulkUpdateBtn.addEventListener('click', openBulkUpdateModal);
        }

        // Cancel modals
        if (cancelAvailabilityModal) {
            cancelAvailabilityModal.addEventListener('click', closeAvailabilityModal);
        }

        if (cancelBulkUpdate) {
            cancelBulkUpdate.addEventListener('click', closeBulkUpdateModal);
        }

        // Close modals when clicking outside
        if (availabilityModal) {
            availabilityModal.addEventListener('click', function(e) {
                if (e.target === availabilityModal) {
                    closeAvailabilityModal();
                }
            });
        }

        if (bulkUpdateModal) {
            bulkUpdateModal.addEventListener('click', function(e) {
                if (e.target === bulkUpdateModal) {
                    closeBulkUpdateModal();
                }
            });
        }

        // Edit availability functionality
        document.querySelectorAll('.edit-availability-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const availability = JSON.parse(this.getAttribute('data-availability'));

                document.getElementById('availabilityModalAction').value = 'edit_availability';
                document.getElementById('availabilityModalId').value = availability.id;
                document.getElementById('availabilityModalTitle').textContent = 'Edit Availability';
                document.getElementById('availabilityModalSubmitText').textContent = 'Update Availability';

                // Populate form
                document.getElementById('availabilityModalChannelId').value = availability.channel_id;
                document.getElementById('availabilityModalRoomType').value = availability.room_type;
                document.getElementById('availabilityModalDate').value = availability.available_date;
                document.getElementById('availabilityModalStatus').value = availability.status;
                document.getElementById('availabilityModalTotalRooms').value = availability.total_rooms;
                document.getElementById('availabilityModalBookedRooms').value = availability.booked_rooms;
                document.getElementById('availabilityModalBlockedRooms').value = availability.blocked_rooms;
                document.getElementById('availabilityModalMinimumStay').value = availability.minimum_stay;
                document.getElementById('availabilityModalMaximumStay').value = availability.maximum_stay || '';
                document.getElementById('availabilityModalRate').value = availability.rate || '';

                openAvailabilityModal();
            });
        });

        // Delete availability functionality
        document.querySelectorAll('.delete-availability-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this availability record?')) {
                    const availabilityId = this.getAttribute('data-availability-id');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_availability">
                        <input type="hidden" name="availability_id" value="${availabilityId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Filter functionality
        const channelFilter = document.getElementById('channelFilter');
        const roomTypeFilter = document.getElementById('roomTypeFilter');
        const startDateFilter = document.getElementById('startDateFilter');
        const endDateFilter = document.getElementById('endDateFilter');
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');

        function applyFilters() {
            const channelValue = channelFilter.value;
            const roomTypeValue = roomTypeFilter.value;
            const startDateValue = startDateFilter.value;
            const endDateValue = endDateFilter.value;

            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let show = true;

                if (channelValue && !row.cells[1].textContent.includes(channelFilter.options[channelFilter.selectedIndex].text)) {
                    show = false;
                }

                if (roomTypeValue && !row.cells[2].textContent.includes(roomTypeValue)) {
                    show = false;
                }

                if (startDateValue) {
                    const rowDate = new Date(row.cells[0].textContent);
                    const filterDate = new Date(startDateValue);
                    if (rowDate < filterDate) {
                        show = false;
                    }
                }

                if (endDateValue) {
                    const rowDate = new Date(row.cells[0].textContent);
                    const filterDate = new Date(endDateValue);
                    if (rowDate > filterDate) {
                        show = false;
                    }
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
                roomTypeFilter.value = '';
                startDateFilter.value = '<?php echo $defaultStartDate; ?>';
                endDateFilter.value = '<?php echo $defaultEndDate; ?>';
                applyFilters();
            });
        }
    </script>
</body>
</html>

<?php
function getAvailabilityColor($availableRooms) {
    if ($availableRooms > 5) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } elseif ($availableRooms > 0) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    } else {
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'Open':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Closed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        case 'On Request':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
?>
