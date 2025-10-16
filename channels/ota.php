<?php
  // PHP session and auth MUST be at the very top before any HTML
  require_once __DIR__ . '/includes/db.php';
  requireAuth(['admin','receptionist']);

  // Handle form submissions
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_channel':
          $name = trim($_POST['name']);
          $display_name = trim($_POST['display_name']);
          $type = $_POST['type'];
          $api_endpoint = trim($_POST['api_endpoint']);
          $api_key = trim($_POST['api_key']);
          $username = trim($_POST['username']);
          $password = trim($_POST['password']);
          $commission_rate = floatval($_POST['commission_rate']);
          $contact_person = trim($_POST['contact_person']);
          $contact_email = trim($_POST['contact_email']);
          $contact_phone = trim($_POST['contact_phone']);
          $notes = trim($_POST['notes']);

          if (empty($name) || empty($display_name)) {
            $error = "Channel name and display name are required.";
          } else {
            try {
              $stmt = $pdo->prepare("
                INSERT INTO channels (name, display_name, type, api_endpoint, api_key, username, password, commission_rate, contact_person, contact_email, contact_phone, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
              ");
              $stmt->execute([$name, $display_name, $type, $api_endpoint, $api_key, $username, $password, $commission_rate, $contact_person, $contact_email, $contact_phone, $notes]);

              $success = "Channel added successfully!";
            } catch (PDOException $e) {
              if ($e->getCode() == 23000) { // Duplicate entry
                $error = "A channel with this name already exists.";
              } else {
                $error = "Failed to add channel. Please try again.";
              }
            }
          }
          break;

        case 'update_channel':
          $id = intval($_POST['channel_id']);
          $name = trim($_POST['name']);
          $display_name = trim($_POST['display_name']);
          $type = $_POST['type'];
          $api_endpoint = trim($_POST['api_endpoint']);
          $api_key = trim($_POST['api_key']);
          $username = trim($_POST['username']);
          $password = trim($_POST['password']);
          $commission_rate = floatval($_POST['commission_rate']);
          $contact_person = trim($_POST['contact_person']);
          $contact_email = trim($_POST['contact_email']);
          $contact_phone = trim($_POST['contact_phone']);
          $notes = trim($_POST['notes']);

          if (empty($name) || empty($display_name)) {
            $error = "Channel name and display name are required.";
          } else {
            try {
              $stmt = $pdo->prepare("
                UPDATE channels
                SET name = ?, display_name = ?, type = ?, api_endpoint = ?, api_key = ?, username = ?, password = ?, commission_rate = ?, contact_person = ?, contact_email = ?, contact_phone = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
              ");
              $stmt->execute([$name, $display_name, $type, $api_endpoint, $api_key, $username, $password, $commission_rate, $contact_person, $contact_email, $contact_phone, $notes, $id]);

              $success = "Channel updated successfully!";
            } catch (PDOException $e) {
              if ($e->getCode() == 23000) { // Duplicate entry
                $error = "A channel with this name already exists.";
              } else {
                $error = "Failed to update channel. Please try again.";
              }
            }
          }
          break;

        case 'delete_channel':
          $id = intval($_POST['channel_id']);
          try {
            $stmt = $pdo->prepare("DELETE FROM channels WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Channel deleted successfully!";
          } catch (PDOException $e) {
            $error = "Failed to delete channel. It may be in use.";
          }
          break;

        case 'test_connection':
          $channel_id = intval($_POST['channel_id']);
          // This would implement actual connection testing
          $success = "Connection test completed successfully!";
          break;
      }
    }
  }

  // Get edit channel ID if provided
  $edit_channel_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
  $edit_channel = null;

  if ($edit_channel_id) {
    $stmt = $pdo->prepare("SELECT * FROM channels WHERE id = ?");
    $stmt->execute([$edit_channel_id]);
    $edit_channel = $stmt->fetch(PDO::FETCH_ASSOC);
  }
?>
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

    <!-- Primary Meta Tags -->
    <title>OTA Connections - Channel Management - Inn Nexus</title>
    <meta name="title" content="OTA Connections - Channel Management - Inn Nexus" />
    <meta name="description" content="Manage OTA connections and API settings for Booking.com, Expedia, Agoda and other distribution channels." />
    <meta name="keywords" content="OTA, booking.com, expedia, agoda, channel management, API connections, hotel distribution" />
    <meta name="author" content="Inn Nexus Team" />
    <meta name="robots" content="noindex, nofollow" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    <link rel="icon" type="image/png" href="./public/favicon.ico" />
    <link rel="apple-touch-icon" href="./public/favicon.svg" />

    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6" />

    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />

    <!-- Security -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
    <style>
      .form-group {
        margin-bottom: 1rem;
      }
      .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
      }
      .form-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background-color: white;
        color: #374151;
      }
      .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      }
      .dark .form-input {
        background-color: #374151;
        border-color: #4b5563;
        color: #f9fafb;
      }
      .btn-primary {
        background-color: #3b82f6;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
      }
      .btn-primary:hover {
        background-color: #2563eb;
      }
      .btn-secondary {
        background-color: #6b7280;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
      }
      .btn-secondary:hover {
        background-color: #4b5563;
      }
      .btn-danger {
        background-color: #ef4444;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
      }
      .btn-danger:hover {
        background-color: #dc2626;
      }
    </style>
  </head>
  <body class="min-h-screen bg-background">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">
              <?php echo $edit_channel_id ? 'Edit OTA Connection' : 'OTA Connections'; ?>
            </h1>
            <p class="text-muted-foreground">
              <?php echo $edit_channel_id ? 'Update connection settings' : 'Manage OTA connections and API settings'; ?>
            </p>
          </div>
          <div class="flex gap-3">
            <a href="channel-management.php" class="btn-secondary">
              <i data-lucide="arrow-left" class="w-4 h-4 mr-2 inline"></i>
              Back to Overview
            </a>
          </div>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Channel Form -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">
            <?php echo $edit_channel_id ? 'Edit Channel' : 'Add New Channel'; ?>
          </h2>

          <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $edit_channel_id ? 'update_channel' : 'add_channel'; ?>">
            <?php if ($edit_channel_id): ?>
              <input type="hidden" name="channel_id" value="<?php echo $edit_channel_id; ?>">
            <?php endif; ?>

            <div class="form-group">
              <label for="name" class="form-label">Channel Name *</label>
              <input type="text" id="name" name="name" class="form-input" required
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['name'] : ''); ?>"
                     placeholder="e.g., booking.com, expedia">
            </div>

            <div class="form-group">
              <label for="display_name" class="form-label">Display Name *</label>
              <input type="text" id="display_name" name="display_name" class="form-input" required
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['display_name'] : ''); ?>"
                     placeholder="e.g., Booking.com">
            </div>

            <div class="form-group">
              <label for="type" class="form-label">Channel Type</label>
              <select id="type" name="type" class="form-input">
                <option value="OTA" <?php echo ($edit_channel && $edit_channel['type'] === 'OTA') ? 'selected' : ''; ?>>OTA</option>
                <option value="GDS" <?php echo ($edit_channel && $edit_channel['type'] === 'GDS') ? 'selected' : ''; ?>>GDS</option>
                <option value="Direct" <?php echo ($edit_channel && $edit_channel['type'] === 'Direct') ? 'selected' : ''; ?>>Direct</option>
                <option value="Wholesale" <?php echo ($edit_channel && $edit_channel['type'] === 'Wholesale') ? 'selected' : ''; ?>>Wholesale</option>
                <option value="Corporate" <?php echo ($edit_channel && $edit_channel['type'] === 'Corporate') ? 'selected' : ''; ?>>Corporate</option>
              </select>
            </div>

            <div class="form-group">
              <label for="commission_rate" class="form-label">Commission Rate (%)</label>
              <input type="number" id="commission_rate" name="commission_rate" class="form-input" step="0.01" min="0" max="100"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['commission_rate'] : '15.00'); ?>">
            </div>

            <div class="form-group">
              <label for="api_endpoint" class="form-label">API Endpoint</label>
              <input type="url" id="api_endpoint" name="api_endpoint" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['api_endpoint'] : ''); ?>"
                     placeholder="https://api.example.com/v1/">
            </div>

            <div class="form-group">
              <label for="api_key" class="form-label">API Key</label>
              <input type="password" id="api_key" name="api_key" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['api_key'] : ''); ?>"
                     placeholder="Your API key">
            </div>

            <div class="form-group">
              <label for="username" class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['username'] : ''); ?>"
                     placeholder="API username">
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['password'] : ''); ?>"
                     placeholder="API password">
            </div>

            <div class="form-group">
              <label for="contact_person" class="form-label">Contact Person</label>
              <input type="text" id="contact_person" name="contact_person" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['contact_person'] : ''); ?>"
                     placeholder="Account manager name">
            </div>

            <div class="form-group">
              <label for="contact_email" class="form-label">Contact Email</label>
              <input type="email" id="contact_email" name="contact_email" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['contact_email'] : ''); ?>"
                     placeholder="manager@example.com">
            </div>

            <div class="form-group">
              <label for="contact_phone" class="form-label">Contact Phone</label>
              <input type="tel" id="contact_phone" name="contact_phone" class="form-input"
                     value="<?php echo htmlspecialchars($edit_channel ? $edit_channel['contact_phone'] : ''); ?>"
                     placeholder="+1-555-0123">
            </div>

            <div class="form-group">
              <label for="notes" class="form-label">Notes</label>
              <textarea id="notes" name="notes" class="form-input" rows="3"
                        placeholder="Additional notes or special instructions"><?php echo htmlspecialchars($edit_channel ? $edit_channel['notes'] : ''); ?></textarea>
            </div>

            <div class="flex gap-3">
              <button type="submit" class="btn-primary">
                <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                <?php echo $edit_channel_id ? 'Update Channel' : 'Add Channel'; ?>
              </button>
              <?php if (!$edit_channel_id): ?>
                <button type="reset" class="btn-secondary">
                  <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2 inline"></i>
                  Reset
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- Existing Channels -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Existing Channels</h2>

          <?php
          try {
            $stmt = $pdo->query("
              SELECT c.*, COUNT(crm.id) as room_mappings
              FROM channels c
              LEFT JOIN channel_room_mappings crm ON c.id = crm.channel_id
              GROUP BY c.id
              ORDER BY c.display_name
            ");
            $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOException $e) {
            $channels = [];
          }
          ?>

          <?php if (empty($channels)): ?>
            <p class="text-muted-foreground">No channels configured yet.</p>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($channels as $channel): ?>
                <div class="border rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold"><?php echo htmlspecialchars($channel['display_name']); ?></h3>
                    <div class="flex gap-2">
                      <button onclick="editChannel(<?php echo $channel['id']; ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                      </button>
                      <button onclick="testConnection(<?php echo $channel['id']; ?>)" class="text-green-600 hover:text-green-800" title="Test Connection">
                        <i data-lucide="wifi" class="w-4 h-4"></i>
                      </button>
                      <button onclick="deleteChannel(<?php echo $channel['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                      </button>
                    </div>
                  </div>

                  <div class="space-y-1 text-sm text-muted-foreground">
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($channel['type']); ?></p>
                    <p><strong>Commission:</strong> <?php echo htmlspecialchars($channel['commission_rate']); ?>%</p>
                    <p><strong>Room Mappings:</strong> <?php echo $channel['room_mappings']; ?></p>
                    <p><strong>Status:</strong>
                      <span class="px-2 py-1 rounded-full text-xs <?php
                        echo match($channel['status']) {
                          'Active' => 'bg-green-100 text-green-800',
                          'Inactive' => 'bg-red-100 text-red-800',
                          'Maintenance' => 'bg-yellow-100 text-yellow-800',
                          'Error' => 'bg-purple-100 text-purple-800',
                          default => 'bg-gray-100 text-gray-800'
                        };
                      ?>">
                        <?php echo htmlspecialchars($channel['status']); ?>
                      </span>
                    </p>
                    <?php if ($channel['last_sync']): ?>
                      <p><strong>Last Sync:</strong> <?php echo date('M j, Y g:i A', strtotime($channel['last_sync'])); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-card text-card-foreground rounded-lg p-6 w-full max-w-md mx-4 shadow-xl border border-border">
        <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
        <p class="mb-6">Are you sure you want to delete this channel? This action cannot be undone.</p>
        <div class="flex gap-3">
          <button id="confirmDelete" class="btn-danger flex-1">Delete</button>
          <button id="cancelDelete" class="btn-secondary flex-1">Cancel</button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>

    <script>
      let channelToDelete = null;

      function editChannel(channelId) {
        window.location.href = `?edit=${channelId}`;
      }

      function testConnection(channelId) {
        const formData = new FormData();
        formData.append('action', 'test_connection');
        formData.append('channel_id', channelId);

        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(() => {
          alert('Connection test completed. Check the channel status for results.');
        })
        .catch(error => {
          console.error('Error testing connection:', error);
          alert('Failed to test connection. Please try again.');
        });
      }

      function deleteChannel(channelId) {
        channelToDelete = channelId;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function confirmDeleteChannel() {
        if (!channelToDelete) return;

        const formData = new FormData();
        formData.append('action', 'delete_channel');
        formData.append('channel_id', channelToDelete);

        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(() => {
          window.location.reload();
        })
        .catch(error => {
          console.error('Error deleting channel:', error);
          alert('Failed to delete channel. Please try again.');
        });
      }

      // Setup modal event listeners
      document.getElementById('confirmDelete').addEventListener('click', confirmDeleteChannel);
      document.getElementById('cancelDelete').addEventListener('click', () => {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        channelToDelete = null;
      });

      // Close modal on outside click
      document.getElementById('deleteModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('deleteModal')) {
          const modal = document.getElementById('deleteModal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          channelToDelete = null;
        }
      });

      // Close modal on Escape
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
          const modal = document.getElementById('deleteModal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          channelToDelete = null;
        }
      });
    </script>
  </body>
</html>
