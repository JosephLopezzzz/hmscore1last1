<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db.php';
requireAuth(['admin', 'receptionist']);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false, 'error' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_channels':
            $stmt = $pdo->query("
                SELECT c.*, COUNT(crm.id) as room_mappings,
                       (SELECT COUNT(*) FROM channel_sync_logs csl WHERE csl.channel_id = c.id AND csl.status = 'Success' AND csl.started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as recent_syncs
                FROM channels c
                LEFT JOIN channel_room_mappings crm ON c.id = crm.channel_id
                GROUP BY c.id
                ORDER BY c.display_name
            ");
            $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'channels' => $channels];
            break;

        case 'get_channel':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid channel ID');
            }

            $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = ?");
            $stmt->execute([$id]);
            $channel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$channel) {
                throw new Exception('Channel not found');
            }

            $response = ['success' => true, 'channel' => $channel];
            break;

        case 'add_channel':
            $name = trim($_POST['name'] ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $type = $_POST['type'] ?? 'OTA';
            $api_endpoint = trim($_POST['api_endpoint'] ?? '');
            $api_key = trim($_POST['api_key'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $commission_rate = floatval($_POST['commission_rate'] ?? 0);
            $contact_person = trim($_POST['contact_person'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            if (empty($name) || empty($display_name)) {
                throw new Exception('Channel name and display name are required');
            }

            $stmt = $pdo->prepare("
                INSERT INTO channels (name, display_name, type, api_endpoint, api_key, username, password, commission_rate, contact_person, contact_email, contact_phone, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $display_name, $type, $api_endpoint, $api_key, $username, $password, $commission_rate, $contact_person, $contact_email, $contact_phone, $notes]);

            $response = ['success' => true, 'message' => 'Channel added successfully'];
            break;

        case 'update_channel':
            $id = intval($_POST['channel_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid channel ID');
            }

            $name = trim($_POST['name'] ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $type = $_POST['type'] ?? 'OTA';
            $api_endpoint = trim($_POST['api_endpoint'] ?? '');
            $api_key = trim($_POST['api_key'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $commission_rate = floatval($_POST['commission_rate'] ?? 0);
            $contact_person = trim($_POST['contact_person'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            if (empty($name) || empty($display_name)) {
                throw new Exception('Channel name and display name are required');
            }

            $stmt = $pdo->prepare("
                UPDATE channels SET 
                    name = ?, display_name = ?, type = ?, api_endpoint = ?, api_key = ?, 
                    username = ?, password = ?, commission_rate = ?, contact_person = ?, 
                    contact_email = ?, contact_phone = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $display_name, $type, $api_endpoint, $api_key, $username, $password, $commission_rate, $contact_person, $contact_email, $contact_phone, $notes, $id]);

            $response = ['success' => true, 'message' => 'Channel updated successfully'];
            break;

        case 'delete_channel':
            $id = intval($_POST['channel_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid channel ID');
            }

            $stmt = $pdo->prepare("DELETE FROM channels WHERE id = ?");
            $stmt->execute([$id]);

            $response = ['success' => true, 'message' => 'Channel deleted successfully'];
            break;

        case 'sync_channel':
            $id = intval($_POST['channel_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid channel ID');
            }

            // Update sync status to in progress
            $stmt = $pdo->prepare("UPDATE channels SET sync_status = 'In Progress', last_sync = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            // Log sync attempt
            $stmt = $pdo->prepare("
                INSERT INTO channel_sync_logs (channel_id, sync_type, status, started_at)
                VALUES (?, 'Full', 'In Progress', NOW())
            ");
            $stmt->execute([$id]);

            $response = ['success' => true, 'message' => 'Channel sync started'];
            break;

        default:
            $response = ['success' => false, 'error' => 'Invalid action'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
?>
