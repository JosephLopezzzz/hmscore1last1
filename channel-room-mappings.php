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

// Get all hotel rooms
function getHotelRooms() {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("
            SELECT id, room_number, room_type, status
            FROM rooms
            ORDER BY room_number
        ");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching hotel rooms: ' . $e->getMessage());
        return [];
    }
}

// Get room mappings
function getRoomMappings($channelId = null) {
    $pdo = getPdo();
    if (!$pdo) return [];

    try {
        $sql = "
            SELECT
                crm.*,
                c.display_name as channel_name,
                c.name as channel_code,
                r.room_number,
                r.room_type
            FROM channel_room_mappings crm
            JOIN channels c ON crm.channel_id = c.id
            JOIN rooms r ON crm.room_id = r.id
        ";

        $params = [];
        if ($channelId) {
            $sql .= " WHERE crm.channel_id = ?";
            $params[] = $channelId;
        }

        $sql .= " ORDER BY c.display_name, r.room_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error fetching room mappings: ' . $e->getMessage());
        return [];
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_mapping' || $action === 'edit_mapping') {
        $mappingData = [
            'channel_id' => (int)$_POST['channel_id'],
            'room_id' => (int)$_POST['room_id'],
            'channel_room_id' => trim($_POST['channel_room_id'] ?? ''),
            'channel_room_name' => trim($_POST['channel_room_name'] ?? ''),
            'status' => $_POST['status'] ?? 'Active'
        ];

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'add_mapping') {
                    $stmt = $pdo->prepare("
                        INSERT INTO channel_room_mappings (
                            channel_id, room_id, channel_room_id, channel_room_name, status, created_at, updated_at
                        ) VALUES (
                            :channel_id, :room_id, :channel_room_id, :channel_room_name, :status, NOW(), NOW()
                        )
                    ");
                    $stmt->execute($mappingData);
                    $message = 'Room mapping added successfully!';
                    $messageType = 'success';
                } else {
                    $mappingId = (int)$_POST['mapping_id'];
                    $stmt = $pdo->prepare("
                        UPDATE channel_room_mappings SET
                            channel_id = :channel_id, room_id = :room_id,
                            channel_room_id = :channel_room_id, channel_room_name = :channel_room_name,
                            status = :status, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute(array_merge($mappingData, ['id' => $mappingId]));
                    $message = 'Room mapping updated successfully!';
                    $messageType = 'success';
                }
            } catch (Throwable $e) {
                $message = 'Error saving room mapping: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_mapping') {
        $mappingId = (int)$_POST['mapping_id'];
        $pdo = getPdo();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("DELETE FROM channel_room_mappings WHERE id = ?");
                $stmt->execute([$mappingId]);
                $message = 'Room mapping deleted successfully!';
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error deleting room mapping: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($action === 'bulk_map') {
        $channelId = (int)$_POST['channel_id'];
        $mappings = json_decode($_POST['mappings'] ?? '[]', true);

        $pdo = getPdo();
        if (!$pdo) {
            $message = 'Database connection failed';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO channel_room_mappings (
                        channel_id, room_id, channel_room_id, channel_room_name, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'Active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        channel_room_id = VALUES(channel_room_id),
                        channel_room_name = VALUES(channel_room_name),
                        status = VALUES(status),
                        updated_at = NOW()
                ");

                $successCount = 0;
                foreach ($mappings as $mapping) {
                    if (!empty($mapping['room_id']) && !empty($mapping['channel_room_id'])) {
                        $stmt->execute([
                            $channelId,
                            (int)$mapping['room_id'],
                            trim($mapping['channel_room_id']),
                            trim($mapping['channel_room_name'] ?? '')
                        ]);
                        $successCount++;
                    }
                }

                $message = "Successfully mapped $successCount rooms!";
                $messageType = 'success';
            } catch (Throwable $e) {
                $message = 'Error bulk mapping rooms: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$channels = getChannels();
$hotelRooms = getHotelRooms();
$roomMappings = getRoomMappings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Mappings - Inn Nexus</title>
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
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Room Mappings</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Map your hotel rooms to channel-specific room identifiers</p>
                </div>
                <div class="flex gap-2">
                    <button
                        id="addMappingBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add Mapping
                    </button>
                    <button
                        id="bulkMapBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                    >
                        <i data-lucide="link" class="w-4 h-4"></i>
                        Bulk Map
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mappings Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Room Mappings</h2>
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
                <?php if (empty($roomMappings)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="link" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No room mappings configured</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Map your hotel rooms to channel identifiers to sync availability and rates.</p>
                        <button
                            id="addFirstMappingBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 mx-auto transition-colors"
                        >
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Create Your First Mapping
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hotel Room</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel Room ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Channel Room Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Sync</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($roomMappings as $mapping): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($mapping['channel_name']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">(<?php echo htmlspecialchars($mapping['channel_code']); ?>)</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($mapping['room_number']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($mapping['room_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($mapping['channel_room_id'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($mapping['channel_room_name'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($mapping['status']); ?>">
                                                <?php echo htmlspecialchars($mapping['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo $mapping['last_sync'] ? date('M j, H:i', strtotime($mapping['last_sync'])) : 'Never'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-2">
                                                <button
                                                    class="edit-mapping-btn text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                    data-mapping='<?php echo json_encode($mapping); ?>'
                                                >
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button
                                                    class="delete-mapping-btn text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                    data-mapping-id="<?php echo $mapping['id']; ?>"
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

        <!-- Mapping Statistics -->
        <?php if (!empty($roomMappings)): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                <?php
                $totalMappings = count($roomMappings);
                $activeMappings = count(array_filter($roomMappings, fn($m) => $m['status'] === 'Active'));
                $mappedRooms = count(array_unique(array_column($roomMappings, 'room_id')));
                $mappedChannels = count(array_unique(array_column($roomMappings, 'channel_id')));
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="link" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Mappings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $totalMappings; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Mappings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $activeMappings; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="home" class="w-4 h-4 text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mapped Rooms</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $mappedRooms; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                            <i data-lucide="globe" class="w-4 h-4 text-orange-600 dark:text-orange-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Mapped Channels</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?php echo $mappedChannels; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Unmapped Rooms Alert -->
        <?php
        $mappedRoomIds = array_column($roomMappings, 'room_id');
        $unmappedRooms = array_filter($hotelRooms, fn($room) => !in_array($room['id'], $mappedRoomIds));
        ?>
        <?php if (!empty($unmappedRooms)): ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mt-8">
                <div class="flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Unmapped Rooms</h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            <?php echo count($unmappedRooms); ?> rooms are not mapped to any channels. These rooms won't be available for booking through OTAs.
                            <button id="showUnmappedBtn" class="ml-2 text-yellow-800 dark:text-yellow-200 underline hover:text-yellow-900 dark:hover:text-yellow-100">View rooms</button>
                        </p>
                    </div>
                </div>

                <div id="unmappedRooms" class="hidden mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 dark:border-yellow-700">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Unmapped Rooms:</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                        <?php foreach ($unmappedRooms as $room): ?>
                            <div class="text-sm text-gray-600 dark:text-gray-400 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['room_type']); ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Mapping Modal -->
    <div id="mappingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="mappingModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Add Room Mapping</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" id="mappingModalAction" value="add_mapping">
                <input type="hidden" name="mapping_id" id="mappingModalId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel *</label>
                        <select name="channel_id" id="mappingModalChannelId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Channel</option>
                            <?php foreach ($channels as $channel): ?>
                                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hotel Room *</label>
                        <select name="room_id" id="mappingModalRoomId" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Hotel Room</option>
                            <?php foreach ($hotelRooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['room_type']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel Room ID</label>
                        <input type="text" name="channel_room_id" id="mappingModalChannelRoomId" placeholder="e.g., DLX-101, 12345"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Unique identifier used by the channel</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel Room Name</label>
                        <input type="text" name="channel_room_name" id="mappingModalChannelRoomName" placeholder="e.g., Deluxe Room 101"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Display name used by the channel</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status" id="mappingModalStatus"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Mapped">Mapped</option>
                        <option value="Unmapped">Unmapped</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        <span id="mappingModalSubmitText">Add Mapping</span>
                    </button>
                    <button type="button" id="cancelMappingModal" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Mapping Modal -->
    <div id="bulkMappingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Bulk Room Mapping</h2>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" value="bulk_map">
                <input type="hidden" name="mappings" id="bulkMappingsInput">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel *</label>
                    <select id="bulkChannelId" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Channel</option>
                        <?php foreach ($channels as $channel): ?>
                            <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Room Mappings</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Map multiple rooms at once. Leave Channel Room ID blank to skip a room.</p>
                    </div>

                    <div class="p-4 max-h-96 overflow-y-auto">
                        <div id="bulkMappingsContainer" class="space-y-3">
                            <?php foreach ($hotelRooms as $room): ?>
                                <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="w-32 flex-shrink-0">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($room['room_number']); ?></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">(<?php echo htmlspecialchars($room['room_type']); ?>)</span>
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" class="channel-room-id-input w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                               placeholder="Channel Room ID" data-room-id="<?php echo $room['id']; ?>">
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" class="channel-room-name-input w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                               placeholder="Channel Room Name (optional)" data-room-id="<?php echo $room['id']; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Map Selected Rooms
                    </button>
                    <button type="button" id="cancelBulkMapping" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors">
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
        const mappingModal = document.getElementById('mappingModal');
        const bulkMappingModal = document.getElementById('bulkMappingModal');
        const addMappingBtn = document.getElementById('addMappingBtn');
        const bulkMapBtn = document.getElementById('bulkMapBtn');
        const addFirstMappingBtn = document.getElementById('addFirstMappingBtn');
        const cancelMappingModal = document.getElementById('cancelMappingModal');
        const cancelBulkMapping = document.getElementById('cancelBulkMapping');
        const showUnmappedBtn = document.getElementById('showUnmappedBtn');
        const unmappedRooms = document.getElementById('unmappedRooms');

        function openMappingModal() {
            mappingModal.classList.remove('hidden');
            mappingModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeMappingModal() {
            mappingModal.classList.add('hidden');
            mappingModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            resetMappingModal();
        }

        function openBulkMappingModal() {
            bulkMappingModal.classList.remove('hidden');
            bulkMappingModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeBulkMappingModal() {
            bulkMappingModal.classList.add('hidden');
            bulkMappingModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function resetMappingModal() {
            document.getElementById('mappingModalAction').value = 'add_mapping';
            document.getElementById('mappingModalId').value = '';
            document.getElementById('mappingModalTitle').textContent = 'Add Room Mapping';
            document.getElementById('mappingModalSubmitText').textContent = 'Add Mapping';

            // Reset form
            const form = mappingModal.querySelector('form');
            form.reset();
        }

        // Add mapping buttons
        if (addMappingBtn) {
            addMappingBtn.addEventListener('click', openMappingModal);
        }

        if (bulkMapBtn) {
            bulkMapBtn.addEventListener('click', openBulkMappingModal);
        }

        if (addFirstMappingBtn) {
            addFirstMappingBtn.addEventListener('click', openMappingModal);
        }

        // Cancel modals
        if (cancelMappingModal) {
            cancelMappingModal.addEventListener('click', closeMappingModal);
        }

        if (cancelBulkMapping) {
            cancelBulkMapping.addEventListener('click', closeBulkMappingModal);
        }

        // Close modals when clicking outside
        if (mappingModal) {
            mappingModal.addEventListener('click', function(e) {
                if (e.target === mappingModal) {
                    closeMappingModal();
                }
            });
        }

        if (bulkMappingModal) {
            bulkMappingModal.addEventListener('click', function(e) {
                if (e.target === bulkMappingModal) {
                    closeBulkMappingModal();
                }
            });
        }

        // Show unmapped rooms
        if (showUnmappedBtn && unmappedRooms) {
            showUnmappedBtn.addEventListener('click', function() {
                unmappedRooms.classList.toggle('hidden');
            });
        }

        // Filter functionality
        const channelFilter = document.getElementById('channelFilter');
        if (channelFilter) {
            channelFilter.addEventListener('change', function() {
                const selectedChannel = this.value;
                const rows = document.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    if (!selectedChannel || row.cells[0].textContent.includes(channelFilter.options[channelFilter.selectedIndex].text)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Edit mapping functionality
        document.querySelectorAll('.edit-mapping-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const mapping = JSON.parse(this.getAttribute('data-mapping'));

                document.getElementById('mappingModalAction').value = 'edit_mapping';
                document.getElementById('mappingModalId').value = mapping.id;
                document.getElementById('mappingModalTitle').textContent = 'Edit Room Mapping';
                document.getElementById('mappingModalSubmitText').textContent = 'Update Mapping';

                // Populate form
                document.getElementById('mappingModalChannelId').value = mapping.channel_id;
                document.getElementById('mappingModalRoomId').value = mapping.room_id;
                document.getElementById('mappingModalChannelRoomId').value = mapping.channel_room_id || '';
                document.getElementById('mappingModalChannelRoomName').value = mapping.channel_room_name || '';
                document.getElementById('mappingModalStatus').value = mapping.status;

                openMappingModal();
            });
        });

        // Delete mapping functionality
        document.querySelectorAll('.delete-mapping-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this room mapping?')) {
                    const mappingId = this.getAttribute('data-mapping-id');
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_mapping">
                        <input type="hidden" name="mapping_id" value="${mappingId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Bulk mapping form submission
        const bulkMappingForm = bulkMappingModal?.querySelector('form');
        if (bulkMappingForm) {
            bulkMappingForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const channelId = document.getElementById('bulkChannelId').value;
                if (!channelId) {
                    alert('Please select a channel');
                    return;
                }

                const mappings = [];
                document.querySelectorAll('.channel-room-id-input').forEach(input => {
                    const roomId = input.getAttribute('data-room-id');
                    const channelRoomId = input.value.trim();
                    if (channelRoomId) {
                        const nameInput = document.querySelector(`.channel-room-name-input[data-room-id="${roomId}"]`);
                        mappings.push({
                            room_id: roomId,
                            channel_room_id: channelRoomId,
                            channel_room_name: nameInput ? nameInput.value.trim() : ''
                        });
                    }
                });

                if (mappings.length === 0) {
                    alert('Please enter at least one Channel Room ID');
                    return;
                }

                document.getElementById('bulkMappingsInput').value = JSON.stringify(mappings);
                this.submit();
            });
        }
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
        case 'Mapped':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'Unmapped':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}
?>
