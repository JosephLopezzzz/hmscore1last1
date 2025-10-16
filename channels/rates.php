<?php
  // PHP session and auth MUST be at the very top before any HTML
  require_once __DIR__ . '/includes/db.php';
  requireAuth(['admin','receptionist']);

  // Handle form submissions
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
      switch ($_POST['action']) {
        case 'add_rate':
          $channel_id = intval($_POST['channel_id']);
          $room_type = $_POST['room_type'];
          $rate_type = $_POST['rate_type'];
          $base_rate = floatval($_POST['base_rate']);
          $extra_person_rate = floatval($_POST['extra_person_rate']);
          $child_rate = floatval($_POST['child_rate']);
          $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;
          $breakfast_rate = floatval($_POST['breakfast_rate']);
          $valid_from = $_POST['valid_from'];
          $valid_to = $_POST['valid_to'];
          $minimum_stay = intval($_POST['minimum_stay']);
          $maximum_stay = !empty($_POST['maximum_stay']) ? intval($_POST['maximum_stay']) : null;
          $closed_to_arrival = isset($_POST['closed_to_arrival']) ? 1 : 0;
          $closed_to_departure = isset($_POST['closed_to_departure']) ? 1 : 0;

          if (empty($valid_from) || empty($valid_to) || $base_rate <= 0) {
            $error = "Valid dates and base rate are required.";
          } else {
            try {
              $stmt = $pdo->prepare("
                INSERT INTO channel_rates (channel_id, room_type, rate_type, base_rate, extra_person_rate, child_rate, breakfast_included, breakfast_rate, valid_from, valid_to, minimum_stay, maximum_stay, closed_to_arrival, closed_to_departure)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
              ");
              $stmt->execute([$channel_id, $room_type, $rate_type, $base_rate, $extra_person_rate, $child_rate, $breakfast_included, $breakfast_rate, $valid_from, $valid_to, $minimum_stay, $maximum_stay, $closed_to_arrival, $closed_to_departure]);

              $success = "Rate added successfully!";
            } catch (PDOException $e) {
              $error = "Failed to add rate. Please try again.";
            }
          }
          break;

        case 'update_rate':
          $id = intval($_POST['rate_id']);
          $channel_id = intval($_POST['channel_id']);
          $room_type = $_POST['room_type'];
          $rate_type = $_POST['rate_type'];
          $base_rate = floatval($_POST['base_rate']);
          $extra_person_rate = floatval($_POST['extra_person_rate']);
          $child_rate = floatval($_POST['child_rate']);
          $breakfast_included = isset($_POST['breakfast_included']) ? 1 : 0;
          $breakfast_rate = floatval($_POST['breakfast_rate']);
          $valid_from = $_POST['valid_from'];
          $valid_to = $_POST['valid_to'];
          $minimum_stay = intval($_POST['minimum_stay']);
          $maximum_stay = !empty($_POST['maximum_stay']) ? intval($_POST['maximum_stay']) : null;
          $closed_to_arrival = isset($_POST['closed_to_arrival']) ? 1 : 0;
          $closed_to_departure = isset($_POST['closed_to_departure']) ? 1 : 0;

          if (empty($valid_from) || empty($valid_to) || $base_rate <= 0) {
            $error = "Valid dates and base rate are required.";
          } else {
            try {
              $stmt = $pdo->prepare("
                UPDATE channel_rates
                SET channel_id = ?, room_type = ?, rate_type = ?, base_rate = ?, extra_person_rate = ?, child_rate = ?, breakfast_included = ?, breakfast_rate = ?, valid_from = ?, valid_to = ?, minimum_stay = ?, maximum_stay = ?, closed_to_arrival = ?, closed_to_departure = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
              ");
              $stmt->execute([$channel_id, $room_type, $rate_type, $base_rate, $extra_person_rate, $child_rate, $breakfast_included, $breakfast_rate, $valid_from, $valid_to, $minimum_stay, $maximum_stay, $closed_to_arrival, $closed_to_departure, $id]);

              $success = "Rate updated successfully!";
            } catch (PDOException $e) {
              $error = "Failed to update rate. Please try again.";
            }
          }
          break;

        case 'delete_rate':
          $id = intval($_POST['rate_id']);
          try {
            $stmt = $pdo->prepare("DELETE FROM channel_rates WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Rate deleted successfully!";
          } catch (PDOException $e) {
            $error = "Failed to delete rate. It may be in use.";
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
    <title>Rate Management - Channel Management - Inn Nexus</title>
    <meta name="title" content="Rate Management - Channel Management - Inn Nexus" />
    <meta name="description" content="Manage rates and pricing for different channels and room types with flexible date ranges and conditions." />
    <meta name="keywords" content="rate management, pricing, channel rates, room rates, hotel pricing, dynamic pricing" />
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
            <h1 class="text-3xl font-bold mb-2">Rate Management</h1>
            <p class="text-muted-foreground">Manage rates and pricing for different channels and room types</p>
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
        <!-- Rate Form -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Add/Edit Rate</h2>

          <form method="POST" action="">
            <input type="hidden" name="action" value="add_rate" id="formAction">
            <input type="hidden" name="rate_id" value="" id="rateId">

            <div class="form-group">
              <label for="channel_id" class="form-label">Channel *</label>
              <select id="channel_id" name="channel_id" class="form-input" required>
                <option value="">Select a channel...</option>
                <?php foreach ($channels as $channel): ?>
                  <option value="<?php echo $channel['id']; ?>"><?php echo htmlspecialchars($channel['display_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="room_type" class="form-label">Room Type *</label>
                <select id="room_type" name="room_type" class="form-input" required>
                  <option value="">Select type...</option>
                  <option value="Single">Single</option>
                  <option value="Double">Double</option>
                  <option value="Deluxe">Deluxe</option>
                  <option value="Suite">Suite</option>
                </select>
              </div>

              <div class="form-group">
                <label for="rate_type" class="form-label">Rate Type</label>
                <select id="rate_type" name="rate_type" class="form-input">
                  <option value="Standard">Standard</option>
                  <option value="Corporate">Corporate</option>
                  <option value="Promotional">Promotional</option>
                  <option value="Last Minute">Last Minute</option>
                  <option value="Weekend">Weekend</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="base_rate" class="form-label">Base Rate (₱) *</label>
                <input type="number" id="base_rate" name="base_rate" class="form-input" step="0.01" min="0" required>
              </div>

              <div class="form-group">
                <label for="extra_person_rate" class="form-label">Extra Person Rate (₱)</label>
                <input type="number" id="extra_person_rate" name="extra_person_rate" class="form-input" step="0.01" min="0">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="child_rate" class="form-label">Child Rate (₱)</label>
                <input type="number" id="child_rate" name="child_rate" class="form-input" step="0.01" min="0">
              </div>

              <div class="form-group">
                <label for="breakfast_rate" class="form-label">Breakfast Rate (₱)</label>
                <input type="number" id="breakfast_rate" name="breakfast_rate" class="form-input" step="0.01" min="0">
              </div>
            </div>

            <div class="form-group">
              <label class="flex items-center">
                <input type="checkbox" id="breakfast_included" name="breakfast_included" class="mr-2">
                <span class="text-sm">Breakfast Included</span>
              </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label for="valid_from" class="form-label">Valid From *</label>
                <input type="date" id="valid_from" name="valid_from" class="form-input" required>
              </div>

              <div class="form-group">
                <label for="valid_to" class="form-label">Valid To *</label>
                <input type="date" id="valid_to" name="valid_to" class="form-input" required>
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

            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" id="closed_to_arrival" name="closed_to_arrival" class="mr-2">
                <span class="text-sm">Closed to Arrival</span>
              </label>

              <label class="flex items-center">
                <input type="checkbox" id="closed_to_departure" name="closed_to_departure" class="mr-2">
                <span class="text-sm">Closed to Departure</span>
              </label>
            </div>

            <div class="flex gap-3 mt-6">
              <button type="submit" class="btn-primary">
                <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                Add Rate
              </button>
              <button type="reset" class="btn-secondary">
                <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2 inline"></i>
                Reset
              </button>
            </div>
          </form>
        </div>

        <!-- Existing Rates -->
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-4">Existing Rates</h2>

          <?php
          try {
            $stmt = $pdo->query("
              SELECT cr.*, c.display_name as channel_name
              FROM channel_rates cr
              JOIN channels c ON cr.channel_id = c.id
              ORDER BY cr.valid_from DESC, c.display_name, cr.room_type
            ");
            $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (PDOException $e) {
            $rates = [];
          }
          ?>

          <?php if (empty($rates)): ?>
            <p class="text-muted-foreground">No rates configured yet.</p>
          <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto">
              <?php foreach ($rates as $rate): ?>
                <div class="border rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold"><?php echo htmlspecialchars($rate['channel_name']); ?> - <?php echo htmlspecialchars($rate['room_type']); ?></h3>
                    <div class="flex gap-2">
                      <button onclick="editRate(<?php echo htmlspecialchars(json_encode($rate)); ?>)" class="text-blue-600 hover:text-blue-800" title="Edit">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                      </button>
                      <button onclick="deleteRate(<?php echo $rate['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                      </button>
                    </div>
                  </div>

                  <div class="space-y-1 text-sm text-muted-foreground">
                    <p><strong>Rate Type:</strong> <?php echo htmlspecialchars($rate['rate_type']); ?></p>
                    <p><strong>Base Rate:</strong> ₱<?php echo number_format($rate['base_rate'], 2); ?></p>
                    <p><strong>Valid:</strong> <?php echo date('M j, Y', strtotime($rate['valid_from'])); ?> - <?php echo date('M j, Y', strtotime($rate['valid_to'])); ?></p>
                    <p><strong>Min Stay:</strong> <?php echo $rate['minimum_stay']; ?> <?php echo $rate['maximum_stay'] ? '- ' . $rate['maximum_stay'] : ''; ?></p>
                    <?php if ($rate['breakfast_included']): ?>
                      <p><strong>Breakfast:</strong> Included <?php echo $rate['breakfast_rate'] > 0 ? '(₱' . number_format($rate['breakfast_rate'], 2) . ')' : ''; ?></p>
                    <?php endif; ?>
                    <?php if ($rate['closed_to_arrival'] || $rate['closed_to_departure']): ?>
                      <p><strong>Restrictions:</strong>
                        <?php echo $rate['closed_to_arrival'] ? 'Closed to Arrival ' : ''; ?>
                        <?php echo $rate['closed_to_departure'] ? 'Closed to Departure' : ''; ?>
                      </p>
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
        <p class="mb-6">Are you sure you want to delete this rate? This action cannot be undone.</p>
        <div class="flex gap-3">
          <button id="confirmDelete" class="btn-danger flex-1">Delete</button>
          <button id="cancelDelete" class="btn-secondary flex-1">Cancel</button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>

    <script>
      let rateToDelete = null;

      function editRate(rate) {
        document.getElementById('formAction').value = 'update_rate';
        document.getElementById('rateId').value = rate.id;
        document.getElementById('channel_id').value = rate.channel_id;
        document.getElementById('room_type').value = rate.room_type;
        document.getElementById('rate_type').value = rate.rate_type;
        document.getElementById('base_rate').value = rate.base_rate;
        document.getElementById('extra_person_rate').value = rate.extra_person_rate;
        document.getElementById('child_rate').value = rate.child_rate;
        document.getElementById('breakfast_included').checked = rate.breakfast_included == 1;
        document.getElementById('breakfast_rate').value = rate.breakfast_rate;
        document.getElementById('valid_from').value = rate.valid_from;
        document.getElementById('valid_to').value = rate.valid_to;
        document.getElementById('minimum_stay').value = rate.minimum_stay;
        document.getElementById('maximum_stay').value = rate.maximum_stay || '';
        document.getElementById('closed_to_arrival').checked = rate.closed_to_arrival == 1;
        document.getElementById('closed_to_departure').checked = rate.closed_to_departure == 1;

        // Scroll to form
        document.querySelector('.bg-card').scrollIntoView({ behavior: 'smooth' });
      }

      function deleteRate(rateId) {
        rateToDelete = rateId;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      }

      function confirmDeleteRate() {
        if (!rateToDelete) return;

        const formData = new FormData();
        formData.append('action', 'delete_rate');
        formData.append('rate_id', rateToDelete);

        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(() => {
          window.location.reload();
        })
        .catch(error => {
          console.error('Error deleting rate:', error);
          alert('Failed to delete rate. Please try again.');
        });
      }

      // Setup modal event listeners
      document.getElementById('confirmDelete').addEventListener('click', confirmDeleteRate);
      document.getElementById('cancelDelete').addEventListener('click', () => {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        rateToDelete = null;
      });

      // Close modal on outside click
      document.getElementById('deleteModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('deleteModal')) {
          const modal = document.getElementById('deleteModal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          rateToDelete = null;
        }
      });

      // Close modal on Escape
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
          const modal = document.getElementById('deleteModal');
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          rateToDelete = null;
        }
      });

      // Reset form when adding new rate
      document.querySelector('button[type="reset"]').addEventListener('click', () => {
        document.getElementById('formAction').value = 'add_rate';
        document.getElementById('rateId').value = '';
      });
    </script>
  </body>
</html>
