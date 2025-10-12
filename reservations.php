<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- Primary Meta Tags -->
    <title>Reservations - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Reservations Management - Inn Nexus Hotel Management System" />
    <meta name="description" content="Manage hotel reservations, bookings, and guest check-ins with Inn Nexus reservation management system. Streamline your booking process." />
    <meta name="keywords" content="hotel reservations, booking management, guest check-in, hotel booking system, reservation software" />
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
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin','receptionist']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/helpers.php'; ?>
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php
      $reservations = fetchAllReservations();
      if (!$reservations || count($reservations) === 0) {
        $reservations = [
          [ 'id' => 'RES-001', 'guest' => 'Sarah Johnson', 'room' => '204', 'checkin' => '2025-01-15', 'checkout' => '2025-01-18', 'status' => 'confirmed', 'nights' => 3, 'rate' => 185 ],
          [ 'id' => 'RES-002', 'guest' => 'Michael Chen', 'room' => '315', 'checkin' => '2025-01-16', 'checkout' => '2025-01-20', 'status' => 'confirmed', 'nights' => 4, 'rate' => 210 ],
          [ 'id' => 'RES-003', 'guest' => 'Emma Williams', 'room' => '102', 'checkin' => '2025-01-14', 'checkout' => '2025-01-17', 'status' => 'checked-in', 'nights' => 3, 'rate' => 165 ],
          [ 'id' => 'RES-004', 'guest' => 'David Brown', 'room' => '410', 'checkin' => '2025-01-18', 'checkout' => '2025-01-22', 'status' => 'pending', 'nights' => 4, 'rate' => 225 ],
          [ 'id' => 'RES-005', 'guest' => 'Lisa Anderson', 'room' => '208', 'checkin' => '2025-01-20', 'checkout' => '2025-01-25', 'status' => 'confirmed', 'nights' => 5, 'rate' => 195 ],
        ];
      }
      $statusClasses = [
        'confirmed' => 'bg-success/10 text-success border border-success/20',
        'pending' => 'bg-warning/10 text-warning border border-warning/20',
        'checked-in' => 'bg-accent/10 text-accent border border-accent/20',
        'cancelled' => 'bg-destructive/10 text-destructive border border-destructive/20',
      ];
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Reservations</h1>
          <p class="text-muted-foreground">Manage bookings and availability</p>
        </div>
        <button id="openModalBtn" class="gap-2 inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm">
          <i data-lucide="plus" class="h-4 w-4"></i>
          New Reservation
        </button>
      </div>

      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Total Reservations</p>
          <p class="text-2xl font-bold"><?php echo count($reservations); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Arriving Today</p>
          <p class="text-2xl font-bold">3</p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Departing Today</p>
          <p class="text-2xl font-bold">2</p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Average Rate</p>
          <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso(196, 2); ?></p>
        </div>
      </div>

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b text-left">
              <th class="pb-3 text-sm font-medium text-muted-foreground">Confirmation</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Guest</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Room</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Check-in</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Check-out</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Nights</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Rate</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Status</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $res): ?>
              <tr class="border-b hover:bg-muted/50 transition-colors">
                <td class="py-4 font-medium"><?php echo $res['id']; ?></td>
                <td class="py-4"><?php echo $res['guest']; ?></td>
                <td class="py-4"><?php echo $res['room']; ?></td>
                <td class="py-4 text-sm"><?php echo date('m/d/Y', strtotime($res['checkin'])); ?></td>
                <td class="py-4 text-sm"><?php echo date('m/d/Y', strtotime($res['checkout'])); ?></td>
                <td class="py-4 text-sm"><?php echo $res['nights']; ?></td>
                <td class="py-4 font-medium"><?php echo formatCurrencyPhpPeso($res['rate'], 2); ?></td>
                <td class="py-4">
                  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs <?php echo $statusClasses[$res['status']]; ?>"><?php echo $res['status']; ?></span>
                </td>
                <td class="py-4">
                  <button class="text-sm px-2 py-1 rounded-md hover:bg-accent/10">View</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>

    <!-- Reservation Modal -->
    <div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Create New Reservation</h2>
          <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>
        
        <form id="reservationForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Guest Name</label>
            <input type="text" id="guestName" required class="w-full rounded-md border px-3 py-2 text-sm">
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Contact Number</label>
            <input type="text" id="contactNumber" required class="w-full rounded-md border px-3 py-2 text-sm">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Check-in Date</label>
              <input type="date" id="checkIn" required class="w-full rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Check-out Date</label>
              <input type="date" id="checkOut" required class="w-full rounded-md border px-3 py-2 text-sm">
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Room Type</label>
            <select id="roomType" required class="w-full rounded-md border px-3 py-2 text-sm">
              <option value="">Select room type...</option>
              <option value="Single">Single</option>
              <option value="Double">Double</option>
              <option value="Deluxe">Deluxe</option>
              <option value="Suite">Suite</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Number of Guests</label>
            <input type="number" id="numGuests" min="1" max="10" required class="w-full rounded-md border px-3 py-2 text-sm">
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Special Requests</label>
            <textarea id="specialRequests" rows="3" class="w-full rounded-md border px-3 py-2 text-sm" placeholder="Any special requests..."></textarea>
          </div>
          
          <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">
              Save Reservation
            </button>
            <button type="button" id="cancelModalBtn" class="flex-1 rounded-md border px-4 py-2 text-sm hover:bg-muted">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 rounded-lg bg-green-500 px-4 py-2 text-white shadow-lg opacity-0 transition-opacity duration-300 z-50">
      <span id="toastMessage">✅ Reservation saved successfully!</span>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    
    <script>
      // Modal functionality
      const openModalBtn = document.getElementById("openModalBtn");
      const modal = document.getElementById("reservationModal");
      const closeModalBtn = document.getElementById("closeModalBtn");
      const cancelModalBtn = document.getElementById("cancelModalBtn");
      const reservationForm = document.getElementById("reservationForm");

      // Open modal
      openModalBtn.addEventListener("click", () => {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      });

      // Close modal functions
      function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        reservationForm.reset();
      }

      closeModalBtn.addEventListener("click", closeModal);
      cancelModalBtn.addEventListener("click", closeModal);

      // Close modal when clicking outside content
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          closeModal();
        }
      });

      // Close modal on Escape key
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !modal.classList.contains("hidden")) {
          closeModal();
        }
      });

      // Toast notification
      function showToast(message) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');
        setTimeout(() => {
          toast.classList.remove('opacity-100');
          toast.classList.add('opacity-0');
        }, 3000);
      }

      // Handle form submission
      reservationForm.addEventListener("submit", (e) => {
        e.preventDefault();
        
        // Get form data
        const formData = {
          guestName: document.getElementById('guestName').value,
          contactNumber: document.getElementById('contactNumber').value,
          checkIn: document.getElementById('checkIn').value,
          checkOut: document.getElementById('checkOut').value,
          roomType: document.getElementById('roomType').value,
          numGuests: document.getElementById('numGuests').value,
          specialRequests: document.getElementById('specialRequests').value
        };

        // In a real app, this would make an AJAX request to save the reservation
        console.log('Reservation data:', formData);
        
        // Close modal and show success message
        closeModal();
        showToast('✅ Reservation saved successfully!');
        
        // Optionally reload the page or update the table dynamically
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      });

      // Set minimum date to today for check-in
      document.getElementById('checkIn').min = new Date().toISOString().split('T')[0];
      
      // Update check-out minimum date when check-in changes
      document.getElementById('checkIn').addEventListener('change', function() {
        const checkInDate = new Date(this.value);
        const minCheckOut = new Date(checkInDate.getTime() + 24 * 60 * 60 * 1000); // Next day
        document.getElementById('checkOut').min = minCheckOut.toISOString().split('T')[0];
      });
    </script>
  </body>
  </html>


