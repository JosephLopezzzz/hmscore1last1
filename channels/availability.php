<?php
  // PHP session and auth MUST be at the very top before any HTML
  require_once __DIR__ . '/includes/db.php';
  requireAuth(['admin','receptionist']);

  // Handle form submissions
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'update_availability':
          $channel_id = intval($_POST['channel_id']);
          $room_type = $_POST['room_type'];
          $available_date = $_POST['available_date'];
          $total_rooms = intval($_POST['total_rooms']);
          $booked_rooms = intval($_POST['booked_rooms']);
          $blocked_rooms = intval($_POST['blocked_rooms']);
          $minimum_stay = intval($_POST['minimum_stay']);
          $maximum_stay = !empty($_POST['maximum_stay']) ? intval($_POST['maximum_stay']) : null;
          $status = $_POST['status'];

          try {
            $stmt = $pdo->prepare("
              INSERT INTO channel_availability (channel_id, room_type, available_date, total_rooms, booked_rooms, blocked_rooms, minimum_stay, maximum_stay, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
              total_rooms = VALUES(total_rooms),
              booked_rooms = VALUES(booked_rooms),
              blocked_rooms = VALUES(blocked_rooms),
              minimum_stay = VALUES(minimum_stay),
              maximum_stay = VALUES(maximum_stay),
              status = VALUES(status),
              last_updated = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$channel_id, $room_type, $available_date, $total_rooms, $booked_rooms, $blocked_rooms, $minimum_stay, $maximum_stay, $status]);

            $success = "Availability updated successfully!";
          } catch (PDOException $e) {
            $error = "Failed to update availability. Please try again.";
          }
          break;

        case 'bulk_update_availability':
          $channel_id = intval($_POST['channel_id']);
          $room_type = $_POST['room_type'];
          $start_date = $_POST['start_date'];
          $end_date = $_POST['end_date'];
          $total_rooms = intval($_POST['total_rooms']);
          $minimum_stay = intval($_POST['minimum_stay']);
          $maximum_stay = !empty($_POST['maximum_stay']) ? intval($_POST['maximum_stay']) : null;
          $status = $_POST['status'];

          try {
            // Generate date range
            $dates = [];
            $current = new DateTime($start_date);
            $end = new DateTime($end_date);

            while ($current <= $end) {
              $dates[] = $current->format('Y-m-d');
              $current->modify('+1 day');
            }

            $stmt = $pdo->prepare("
              INSERT INTO channel_availability (channel_id, room_type, available_date, total_rooms, booked_rooms, blocked_rooms, minimum_stay, maximum_stay, status)
              VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?)
              ON DUPLICATE KEY UPDATE
              total_rooms = VALUES(total_rooms),
              minimum_stay = VALUES(minimum_stay),
              maximum_stay = VALUES(maximum_stay),
              status = VALUES(status),
              last_updated = CURRENT_TIMESTAMP
            ");

            foreach ($dates as $date) {
              $stmt->execute([$channel_id, $room_type, $date, $total_rooms, $minimum_stay, $maximum_stay, $status]);
            }

            $success = "Bulk availability updated for " . count($dates) . " dates!";
          } catch (PDOException $e) {
            $error = "Failed to update bulk availability. Please try again.";
          }
          break;
      }
    }
  }

  // Get channels for dropdown
  try {
    $stmt = $pdo->query("SELECT id, name, display_name FROM channels WHERE status = 'Active' ORDER BY display_name");
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $channels = [];
  }

  // Get room types
  try {
    $stmt = $pdo->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type");
    $roomTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
  } catch (PDOException $e) {
    $roomTypes = ['Single', 'Double', 'Deluxe', 'Suite'];
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
    <title>Availability Management - Channel Management - Inn Nexus</title>
    <meta name="title" content="Availability Management - Channel Management - Inn Nexus" />
    <meta name="description" content="Manage room availability across all distribution channels with real-time inventory control." />
    <meta name="keywords" content="availability management, room inventory, channel availability, hotel distribution, blackout dates" />
    <meta name="author" content="Inn Nexus Team" />
    <meta name="robots" content="noindex, nofollow" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../public/favicon.svg" />
    <link rel="icon" type="image/png" href="../public/favicon.ico" />
    <link rel="apple-touch-icon" href="../public/favicon.svg" />

    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6" />

    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/tokens.css" />

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
      .availability-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
      }
      .availability-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        background-color: white;
      }
      .dark .availability-card {
        background-color: #374151;
        border-color: #4b5563;
      }
      .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
      }
      .status-open { background-color: #dcfce7; color: #166534; }
      .status-closed { background-color: #fee2e2; color: #991b1b; }
      .status-request { background-color: #fef3c7; color: #92400e; }
    </style>
  </head>
  <body class="min-h-screen bg-background">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">Availability Management</h1>
            <p class="text-muted-foreground">Manage room availability across all distribution channels</p>
          </div>
          <div class="flex gap-3">
            <a href="../channel-management.php" class="btn-secondary">
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
        <!-- Single Date Update -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Update Single Date</h2>

          <form method="POST" action="">
            <input type="hidden" name="action" value="update_availability">

            <div class="form-group">
              <label for="single_channel_id" class="form-label">Channel *</label>
              <select id="single_channel_id" name="channel_id" class="form-input" required>
                <option value="">Select a channel...</option>
                <?php foreach ($channels as $channel): ?>
                  <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="single_room_type" class="form-label">Room Type *</label>
                <select id="single_room_type" name="room_type" class="form-input" required>
                  <option value="">Select type...</option>
                  <?php foreach ($roomTypes as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="single_available_date" class="form-label">Date *</label>
                <input type="date" id="single_available_date" name="available_date" class="form-input" required>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div class="form-group">
                <label for="total_rooms" class="form-label">Total Rooms</label>
                <input type="number" id="total_rooms" name="total_rooms" class="form-input" min="0">
              </div>

              <div class="form-group">
                <label for="booked_rooms" class="form-label">Booked</label>
                <input type="number" id="booked_rooms" name="booked_rooms" class="form-input" min="0" readonly>
              </div>

              <div class="form-group">
                <label for="blocked_rooms" class="form-label">Blocked</label>
                <input type="number" id="blocked_rooms" name="blocked_rooms" class="form-input" min="0">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="minimum_stay" class="form-label">Minimum Stay</label>
                <input type="number" id="minimum_stay" name="minimum_stay" class="form-input" min="1" value="1">
              </div>

              <div class="form-group">
                <label for="maximum_stay" class="form-label">Maximum Stay</label>
                <input type="number" id="maximum_stay" name="maximum_stay" class="form-input" min="1">
              </div>
            </div>

            <div class="form-group">
              <label for="status" class="form-label">Status</label>
              <select id="status" name="status" class="form-input">
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="On Request">On Request</option>
              </select>
            </div>

            <div class="flex gap-3">
              <button type="submit" class="btn-primary">
                <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                Update Availability
              </button>
            </div>
          </form>
        </div>

        <!-- Bulk Update -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Bulk Update</h2>

          <form method="POST" action="">
            <input type="hidden" name="action" value="bulk_update_availability">

            <div class="form-group">
              <label for="bulk_channel_id" class="form-label">Channel *</label>
              <select id="bulk_channel_id" name="channel_id" class="form-input" required>
                <option value="">Select a channel...</option>
                <?php foreach ($channels as $channel): ?>
                  <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="bulk_room_type" class="form-label">Room Type *</label>
              <select id="bulk_room_type" name="room_type" class="form-input" required>
                <option value="">Select type...</option>
                <?php foreach ($roomTypes as $type): ?>
                  <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="start_date" class="form-label">Start Date *</label>
                <input type="date" id="start_date" name="start_date" class="form-input" required>
              </div>

              <div class="form-group">
                <label for="end_date" class="form-label">End Date *</label>
                <input type="date" id="end_date" name="end_date" class="form-input" required>
              </div>
            </div>

            <div class="form-group">
              <label for="bulk_total_rooms" class="form-label">Total Rooms</label>
              <input type="number" id="bulk_total_rooms" name="total_rooms" class="form-input" min="0">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="bulk_minimum_stay" class="form-label">Minimum Stay</label>
                <input type="number" id="bulk_minimum_stay" name="minimum_stay" class="form-input" min="1" value="1">
              </div>

              <div class="form-group">
                <label for="bulk_maximum_stay" class="form-label">Maximum Stay</label>
                <input type="number" id="bulk_maximum_stay" name="maximum_stay" class="form-input" min="1">
              </div>
            </div>

            <div class="form-group">
              <label for="bulk_status" class="form-label">Status</label>
              <select id="bulk_status" name="status" class="form-input">
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="On Request">On Request</option>
              </select>
            </div>

            <div class="flex gap-3">
              <button type="submit" class="btn-primary">
                <i data-lucide="calendar" class="w-4 h-4 mr-2 inline"></i>
                Bulk Update
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Availability Calendar -->
      <div class="bg-card rounded-lg border p-6 shadow-sm mt-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">Availability Calendar</h2>
          <div class="flex gap-3">
            <select id="calendarChannel" class="form-input">
              <option value="">Select channel...</option>
              <?php foreach ($channels as $channel): ?>
                <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
              <?php endforeach; ?>
            </select>
            <select id="calendarRoomType" class="form-input">
              <option value="">Select room type...</option>
              <?php foreach ($roomTypes as $type): ?>
                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="availability-grid" id="availabilityGrid">
          <!-- Calendar will be loaded here -->
        </div>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>

    <script>
      // Load availability data for selected channel and room type
      async function loadAvailability(channelId, roomType) {
        if (!channelId || !roomType) {
          document.getElementById('availabilityGrid').innerHTML = '<p class="text-muted-foreground col-span-full text-center">Please select a channel and room type to view availability.</p>';
          return;
        }

        try {
          const response = await fetch(`../api/channel-actions.php?action=get_availability&channel_id=${channelId}&room_type=${roomType}`);
          const data = await response.json();

          if (data.success) {
            renderAvailabilityCalendar(data.availability);
          } else {
            document.getElementById('availabilityGrid').innerHTML = '<p class="text-muted-foreground col-span-full text-center">No availability data found.</p>';
          }
        } catch (error) {
          console.error('Error loading availability:', error);
          document.getElementById('availabilityGrid').innerHTML = '<p class="text-muted-foreground col-span-full text-center">Error loading availability data.</p>';
        }
      }

      // Render availability calendar
      function renderAvailabilityCalendar(availability) {
        const grid = document.getElementById('availabilityGrid');

        if (!availability || availability.length === 0) {
          grid.innerHTML = '<p class="text-muted-foreground col-span-full text-center">No availability data found.</p>';
          return;
        }

        // Group by month for better display
        const groupedByMonth = availability.reduce((acc, item) => {
          const date = new Date(item.available_date);
          const monthKey = `${date.getFullYear()}-${date.getMonth()}`;

          if (!acc[monthKey]) {
            acc[monthKey] = {
              month: date.toLocaleString('default', { month: 'long', year: 'numeric' }),
              items: []
            };
          }

          acc[monthKey].items.push(item);
          return acc;
        }, {});

        let html = '';

        Object.values(groupedByMonth).forEach(monthData => {
          html += `<h3 class="text-lg font-semibold mb-3 col-span-full">${monthData.month}</h3>`;

          monthData.items.forEach(item => {
            const date = new Date(item.available_date);
            const available = item.available_rooms || (item.total_rooms - item.booked_rooms - item.blocked_rooms);

            html += `
              <div class="availability-card">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-medium">${date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}</h4>
                  <span class="status-badge status-${item.status.toLowerCase().replace(' ', '-')}">${item.status}</span>
                </div>
                <div class="space-y-1 text-sm">
                  <p><strong>Total:</strong> ${item.total_rooms}</p>
                  <p><strong>Available:</strong> ${available}</p>
                  <p><strong>Booked:</strong> ${item.booked_rooms}</p>
                  <p><strong>Blocked:</strong> ${item.blocked_rooms}</p>
                  ${item.minimum_stay > 1 ? `<p><strong>Min Stay:</strong> ${item.minimum_stay}</p>` : ''}
                  ${item.maximum_stay ? `<p><strong>Max Stay:</strong> ${item.maximum_stay}</p>` : ''}
                </div>
              </div>
            `;
          });
        });

        grid.innerHTML = html;
      }

      // Setup event listeners
      document.getElementById('calendarChannel').addEventListener('change', (e) => {
        const roomType = document.getElementById('calendarRoomType').value;
        loadAvailability(e.target.value, roomType);
      });

      document.getElementById('calendarRoomType').addEventListener('change', (e) => {
        const channelId = document.getElementById('calendarChannel').value;
        loadAvailability(channelId, e.target.value);
      });

      // Auto-populate booked rooms when total rooms changes
      document.getElementById('total_rooms').addEventListener('input', function() {
        const bookedInput = document.getElementById('booked_rooms');
        // This would typically fetch current bookings from the database
        // For now, we'll leave it empty for manual entry
      });

      // Set default date to today
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('single_available_date').value = today;
      document.getElementById('start_date').value = today;
      document.getElementById('end_date').value = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    </script>
  </body>
</html>
