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
      $reservations = fetchAllReservations() ?: [];
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

      <?php
        $today = date('Y-m-d');
        $arrivingToday = 0;
        $departingToday = 0;
        $totalRates = 0;
        
        foreach ($reservations as $res) {
          if (isset($res['checkin']) && $res['checkin'] === $today) {
            $arrivingToday++;
          }
          if (isset($res['checkout']) && $res['checkout'] === $today) {
            $departingToday++;
          }
          if (isset($res['rate'])) {
            $totalRates += $res['rate'];
          }
        }
        
        $averageRate = count($reservations) > 0 ? $totalRates / count($reservations) : 0;
      ?>
      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Total Reservations</p>
          <p class="text-2xl font-bold"><?php echo count($reservations); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Arriving Today</p>
          <p class="text-2xl font-bold"><?php echo $arrivingToday; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Departing Today</p>
          <p class="text-2xl font-bold"><?php echo $departingToday; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Average Rate</p>
          <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso($averageRate, 2); ?></p>
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
    <div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden overflow-y-auto py-8">
      <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 my-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-bold">Create New Reservation</h2>
          <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="h-6 w-6"></i>
          </button>
        </div>
        
        <form id="reservationForm" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Left Column -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold border-b pb-2">Reservation Details</h3>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Check-in Date <span class="text-red-500">*</span></label>
                  <input type="date" id="checkInDate" required class="w-full rounded-md border px-3 py-2 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Check-in Time <span class="text-red-500">*</span></label>
                  <input type="time" id="checkInTime" required class="w-full rounded-md border px-3 py-2 text-sm" value="14:00">
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Check-out Date <span class="text-red-500">*</span></label>
                  <input type="date" id="checkOutDate" required class="w-full rounded-md border px-3 py-2 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Check-out Time <span class="text-red-500">*</span></label>
                  <input type="time" id="checkOutTime" required class="w-full rounded-md border px-3 py-2 text-sm" value="12:00">
                </div>
              </div>
              
              <div class="space-y-2">
                <div>
                  <label for="roomType" class="block text-sm font-medium text-gray-700">Room Type <span class="text-red-500">*</span></label>
                  <select id="roomType" name="room_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">-- Select Room Type --</option>
                    <?php
                    // Fetch available room types from the database
                    $pdo = getPdo();
                    $stmt = $pdo->query("
                        SELECT DISTINCT room_type, rate 
                        FROM rooms 
                        WHERE status = 'Vacant' 
                        GROUP BY room_type, rate
                        ORDER BY room_type
                    
                    ");
                    while ($roomType = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $rate = number_format($roomType['rate'], 2);
                        $maxGuests = [
                            'Single' => 1,
                            'Double' => 2,
                            'Twin' => 2,
                            'Deluxe' => 2,
                            'Suite' => 4,
                            'Family' => 4,
                            'Executive' => 2
                        ][$roomType['room_type']] ?? 2;
                        echo "<option value='{$roomType['room_type']}' data-rate='{$roomType['rate']}'>";
                        echo "{$roomType['room_type']} ({$maxGuests} " . ($maxGuests === 1 ? 'person' : 'people') . ") - ₱$rate";
                        echo "</option>";
                    }
                    ?>
                  </select>
                </div>
                <!-- Room number will be automatically assigned -->
                <input type="hidden" id="roomNumber" name="room_number" value="">
                <div id="amenitiesContainer" class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md hidden">
                  <p class="font-medium mb-1">Room Amenities:</p>
                  <ul id="amenitiesList" class="list-disc pl-5 space-y-1"></ul>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
              </div>
            </div>
            
            <!-- Right Column -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold border-b pb-2">Guest Information</h3>
              
              <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Search Guest <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input type="hidden" id="guestId" name="guestId" required>
                  <input type="text" id="guestSearch" 
                         class="w-full rounded-md border px-3 py-2 text-sm"
                         placeholder="Type to search guests..."
                         autocomplete="off">
                  <div id="guestSearchResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                    <!-- Search results will be populated here -->
                  </div>
                </div>
                <div id="selectedGuest" class="mt-2 p-2 bg-gray-50 rounded hidden">
                  <div class="flex justify-between items-center">
                    <span id="selectedGuestName"></span>
                    <button type="button" id="clearGuest" class="text-red-500 hover:text-red-700">
                      <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="pt-4 border-t mt-4">
                <div class="flex justify-between mb-2">
                  <span class="font-medium">Total Amount:</span>
                  <span id="totalAmount" class="font-bold text-lg">₱0.00</span>
                </div>
                
                <div class="mb-4">
                  <label class="block text-sm font-medium mb-2">Invoice Options</label>
                  <div class="space-y-2">
                    <label class="flex items-center">
                      <input type="checkbox" name="invoiceMethod" value="print" checked class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                      <span class="ml-2">Print Invoice</span>
                    </label>
                    <label class="flex items-center">
                      <input type="checkbox" name="invoiceMethod" value="email" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                      <span class="ml-2">Email Invoice</span>
                    </label>
                  </div>
                </div>
                
                <div class="mb-4">
                  <label class="block text-sm font-medium mb-2">Payment Source</label>
                  <div class="flex space-x-6">
                    <label class="inline-flex items-center">
                      <input type="radio" name="paymentSource" value="cash" checked class="form-radio">
                      <span class="ml-2">Cash</span>
                    </label>
                    <label class="inline-flex items-center opacity-50" title="Coming soon">
                      <input type="radio" name="paymentSource" value="online" disabled class="form-radio">
                      <span class="ml-2">Online</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end gap-3 pt-4 border-t mt-4">
            <button type="button" id="cancelModalBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              Save Reservation
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
      const roomTypeSelect = document.getElementById("roomType");
      const roomNumberSelect = document.getElementById("roomNumber");
      const checkInDate = document.getElementById("checkInDate");
      const checkOutDate = document.getElementById("checkOutDate");
      const checkInTime = document.getElementById("checkInTime");
      const checkOutTime = document.getElementById("checkOutTime");
      const totalAmount = document.getElementById("totalAmount");

      // Modal open/close functionality
      if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
          modal.classList.remove('hidden');
          document.body.style.overflow = 'hidden';
        });
      }

      // Close modal functions
      function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
      }

      if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
      if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);

      // Close when clicking outside modal
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeModal();
        }
      });

      // Guest search functionality
      const guestSearch = document.getElementById('guestSearch');
      const guestSearchResults = document.getElementById('guestSearchResults');
      const selectedGuest = document.getElementById('selectedGuest');
      const selectedGuestName = document.getElementById('selectedGuestName');
      const guestIdInput = document.getElementById('guestId');
      const clearGuestBtn = document.getElementById('clearGuest');
      let searchTimeout;

      // Search guests as user types
      guestSearch.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
          guestSearchResults.classList.add('hidden');
          return;
        }

        searchTimeout = setTimeout(async () => {
          try {
            const response = await fetch(`/hmscore1last1/inn-nexus-main/api/search-guests.php?q=${encodeURIComponent(query)}`);
            const guests = await response.json();
            
            if (guests.length > 0) {
              guestSearchResults.innerHTML = guests.map(guest => `
                <div class="p-2 hover:bg-gray-100 cursor-pointer" 
                     data-id="${guest.id}" 
                     data-name="${guest.first_name} ${guest.last_name}">
                  ${guest.first_name} ${guest.last_name} 
                  <span class="text-gray-500 text-sm">${guest.email}</span>
                </div>
              `).join('');
              guestSearchResults.classList.remove('hidden');
            } else {
              guestSearchResults.innerHTML = '<div class="p-2 text-gray-500">No guests found</div>';
              guestSearchResults.classList.remove('hidden');
            }
          } catch (error) {
            console.error('Error searching guests:', error);
            guestSearchResults.innerHTML = '<div class="p-2 text-red-500">Error searching guests</div>';
            guestSearchResults.classList.remove('hidden');
          }
        }, 300);
      });

      // Handle guest selection
      guestSearchResults.addEventListener('click', (e) => {
        const guestItem = e.target.closest('[data-id]');
        if (!guestItem) return;
        
        const guestId = guestItem.dataset.id;
        const guestName = guestItem.dataset.name;
        
        guestIdInput.value = guestId;
        selectedGuestName.textContent = guestName;
        selectedGuest.classList.remove('hidden');
        guestSearch.value = '';
        guestSearchResults.classList.add('hidden');
      });

      // Clear selected guest
      clearGuestBtn.addEventListener('click', () => {
        guestIdInput.value = '';
        selectedGuest.classList.add('hidden');
        guestSearch.value = '';
      });

      // Close search results when clicking outside
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
          guestSearchResults.classList.add('hidden');
        }
      });

      // Room type rates and max guests mapping - will be populated dynamically
      const roomTypeInfo = {};
      
      // Initialize room type info from the select options
      document.addEventListener('DOMContentLoaded', function() {
        const roomTypeSelect = document.getElementById('roomType');
        if (roomTypeSelect) {
          Array.from(roomTypeSelect.options).forEach(option => {
            if (option.value) {
              const rate = parseFloat(option.dataset.rate) || 0;
              const maxGuests = option.text.match(/\((\d+)/);
              roomTypeInfo[option.value] = {
                rate: rate,
                maxGuests: maxGuests ? parseInt(maxGuests[1]) : 2
              };
            }
          });
        }
      });

      // Room type to amenities mapping based on the database
      const roomTypeAmenities = {
        'Single': ['WiFi', 'TV', 'Mini Fridge'],
        'Double': ['WiFi', 'TV', 'Mini Fridge', 'Coffee Maker'],
        'Twin': ['WiFi', 'TV', 'Mini Fridge', '2 Single Beds'],
        'Triple': ['WiFi', 'TV', 'Mini Fridge', 'Extra Bed'],
        'Quad': ['WiFi', 'TV', 'Mini Fridge', '4 Single Beds'],
        'Family': ['WiFi', 'Smart TV', 'Mini Bar', '2 Bedrooms', 'Sofa Bed'],
        'Deluxe': ['WiFi', 'Smart TV', 'Mini Bar', 'Balcony', 'Coffee Maker'],
        'Junior Suite': ['WiFi', 'Smart TV', 'Mini Bar', 'Balcony', 'Jacuzzi', 'Living Area'],
        'Executive Suite': ['WiFi', 'Smart TV', 'Full Bar', 'Balcony', 'Jacuzzi', 'Separate Living Room', 'Work Desk'],
        'Family Suite': ['WiFi', 'Smart TV', 'Full Bar', '2 Bedrooms', '2 Bathrooms', 'Living Room', 'Kitchenette'],
        'Luxury Suite': ['WiFi', '65" Smart TV', 'Premium Bar', 'Panoramic Balcony', 'Jacuzzi', 'Steam Shower', 'Living Room', 'Dining Area'],
        'Presidential Suite': ['WiFi', 'Multiple Smart TVs', 'Premium Bar', 'Wrap-around Balcony', 'Jacuzzi', 'Steam Shower', '2 Bedrooms', 'Full Kitchen', 'Dining Room', 'Office']
      };

      // Update phone display when country code changes
      document.getElementById('countryCode').addEventListener('change', function() {
        document.getElementById('countryCodeDisplay').textContent = this.value;
      });

      // Phone number formatting
      document.getElementById('phone').addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
      });

      // Form validation
      function validateForm() {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const roomType = document.getElementById('roomType').value;
        const checkInDate = document.getElementById('checkInDate').value;
        const checkOutDate = document.getElementById('checkOutDate').value;
        
        // Check required fields
        if (!firstName || !lastName) {
          showToast('❌ Please enter both first and last name');
          return false;
        }
        
        // Name validation
        const nameRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/;
        if (!nameRegex.test(firstName) || !nameRegex.test(lastName)) {
          showToast('❌ Please enter valid names (letters and spaces only)');
          return false;
        }
        
        // Email validation
        if (!email) {
          showToast('❌ Email is required');
          return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          showToast('❌ Please enter a valid email address');
          return false;
        }
        
        // Phone validation (if provided)
        if (phone) {
          const phoneRegex = /^\d{10,15}$/;
          if (!phoneRegex.test(phone)) {
            showToast('❌ Please enter a valid phone number (10-15 digits)');
            return false;
          }
        }
        
        // Room type validation
        if (!roomType) {
          showToast('❌ Please select a room type');
          return false;
        }
        
        // Date validation
        if (!checkInDate || !checkOutDate) {
          showToast('❌ Please select both check-in and check-out dates');
          return false;
        }
        
        return true;
      }

      // Function to disable booked time slots
      async function updateTimePickerAvailability() {
        const roomType = document.getElementById('roomType').value;
        const checkInDate = document.getElementById('checkInDate').value;
        const checkOutDate = document.getElementById('checkOutDate').value;
        
        if (!roomType || !checkInDate) return;
        
        try {
          // Fetch booked time slots for the selected room type and date
          const response = await fetch(`/hmscore1last1/inn-nexus-main/api/get-booked-times.php?room_type=${encodeURIComponent(roomType)}&date=${checkInDate}`);
          const data = await response.json();
          
          if (data.status === 'success') {
            const timeInput = document.getElementById('checkInTime');
            const options = timeInput.querySelectorAll('option');
            
            // Reset all options
            options.forEach(option => {
              option.disabled = false;
              option.style.color = '';
            });
            
            // Disable booked time slots
            data.booked_slots.forEach(slot => {
              const startTime = new Date(`1970-01-01T${slot.start}Z`);
              const endTime = new Date(`1970-01-01T${slot.end}Z`);
              
              options.forEach(option => {
                if (option.value) {
                  const [hours, minutes] = option.value.split(':').map(Number);
                  const optionTime = new Date(1970, 0, 1, hours, minutes);
                  
                  if (optionTime >= startTime && optionTime < endTime) {
                    option.disabled = true;
                    option.style.color = '#999';
                  }
                }
              });
            });
          }
        } catch (error) {
          console.error('Error fetching booked times:', error);
        }
      }

      // Function to update room details when type changes
      async function updateRoomDetails(roomType) {
        if (!roomType) return;
        
        try {
          // Fetch room details for the selected type
          const response = await fetch(`/hmscore1last1/inn-nexus-main/api/get-available-rooms.php?type=${encodeURIComponent(roomType)}`);
          const rooms = await response.json();
          
          if (rooms.data && rooms.data.length > 0) {
            // Use the first available room
            const room = rooms.data[0];
            document.getElementById('roomNumber').value = room.room_number;
          } else {
            document.getElementById('roomNumber').value = '';
            showToast('❌ No rooms available for the selected type');
          }
        } catch (error) {
          console.error('Error fetching room details:', error);
          document.getElementById('roomNumber').value = '';
        }
      }
      
      // Room type change handler
      roomTypeSelect.addEventListener('change', function() {
        const roomType = this.value;
        const guestsInput = document.getElementById('guests');
        const amenitiesContainer = document.getElementById('amenitiesContainer');
        const amenitiesList = document.getElementById('amenitiesList');
        
        if (roomType && roomTypeInfo[roomType]) {
          const roomInfo = roomTypeInfo[roomType];
          
          // Update max guests
          if (guestsInput) {
            guestsInput.max = roomInfo.maxGuests;
            if (parseInt(guestsInput.value) > roomInfo.maxGuests) {
              guestsInput.value = roomInfo.maxGuests;
            }
          }
          
          // Update room rate display
          const roomRateElement = document.getElementById('roomRate');
          if (roomRateElement) {
            roomRateElement.textContent = `₱${roomInfo.rate.toFixed(2)}`;
          }
          
          // Update amenities display
          if (roomTypeAmenities[roomType]) {
            amenitiesList.innerHTML = '';
            roomTypeAmenities[roomType].forEach(amenity => {
              const li = document.createElement('li');
              li.textContent = amenity;
              amenitiesList.appendChild(li);
            });
            amenitiesContainer.classList.remove('hidden');
          } else {
            amenitiesContainer.classList.add('hidden');
          }
          
          // Update total amount
          updateTotalAmount();
        } else {
          amenitiesContainer.classList.add('hidden');
        }
      });

      // Generate time options for time inputs
      function generateTimeOptions() {
        const times = [];
        for (let hour = 0; hour < 24; hour++) {
          for (let minute = 0; minute < 60; minute += 30) { // 30-minute intervals
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            times.push(`<option value="${timeString}">${timeString}</option>`);
          }
        }
        return times.join('');
      }

      // Initialize time inputs with options
      document.addEventListener('DOMContentLoaded', () => {
        const timeInputs = document.querySelectorAll('input[type="time"]');
        timeInputs.forEach(input => {
          input.innerHTML = generateTimeOptions();
        });
      });

      // Open modal
      openModalBtn.addEventListener("click", () => {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        // Set default dates
        const today = new Date();
        checkInDate.min = today.toISOString().split('T')[0];
        // Set default check-out to tomorrow
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        checkOutDate.min = tomorrow.toISOString().split('T')[0];
        checkOutDate.value = tomorrow.toISOString().split('T')[0];
        
        // Update time picker availability when modal opens
        updateTimePickerAvailability();
      });
      
      // Update time picker when room type or date changes
      document.getElementById('roomType').addEventListener('change', updateTimePickerAvailability);
      document.getElementById('checkInDate').addEventListener('change', updateTimePickerAvailability);

      // Close modal functions
      function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        reservationForm.reset();
        totalAmount.textContent = '₱0.00';
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

      // Update check-out minimum date when check-in changes
      checkInDate.addEventListener('change', function() {
        if (this.value) {
          const checkIn = new Date(this.value);
          const minCheckOut = new Date(checkIn.getTime() + 24 * 60 * 60 * 1000); // Next day
          checkOutDate.min = minCheckOut.toISOString().split('T')[0];
          
          // If current check-out is before new min, update it
          if (new Date(checkOutDate.value) < minCheckOut) {
            checkOutDate.value = minCheckOut.toISOString().split('T')[0];
          }
          
          updateTotalAmount();
        }
      });

      // Update total when dates or room type changes
      [checkOutDate, checkInTime, checkOutTime, roomTypeSelect].forEach(element => {
        element.addEventListener('change', updateTotalAmount);
      });

      // Calculate and update total amount
      function updateTotalAmount() {
        const roomType = roomTypeSelect.value;
        if (!roomType) return;

        const rate = roomTypeInfo[roomType].rate;
        
        // Calculate hours between check-in and check-out
        if (checkInDate.value && checkOutDate.value && checkInTime.value && checkOutTime.value) {
          const checkIn = new Date(`${checkInDate.value}T${checkInTime.value}`);
          const checkOut = new Date(`${checkOutDate.value}T${checkOutTime.value}`);
          
          if (checkOut > checkIn) {
            const diffMs = checkOut - checkIn;
            const diffHours = Math.ceil(diffMs / (1000 * 60 * 60));
            const periods = Math.ceil(diffHours / 8);
            const total = periods * rate;
            
            totalAmount.textContent = `₱${total.toFixed(2)}`;
            return;
          }
        }
        
        // Default to 1 period if dates are invalid
        totalAmount.textContent = `₱${rate.toFixed(2)}`;
      }

      // Form submission
      document.getElementById('reservationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
          return false;
        }
        
        // Get form values
        const firstName = document.getElementById('firstName').value.trim();
        const middleInitial = document.getElementById('middleInitial').value.trim().toUpperCase();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const countryCode = document.getElementById('countryCode').value;
        const phone = countryCode + document.getElementById('phone').value.trim();
        const birthdate = document.getElementById('birthdate').value;
        const roomType = document.getElementById('roomType').value;
        const checkInDate = document.getElementById('checkInDate').value;
        const checkInTime = document.getElementById('checkInTime').value;
        const checkOutDate = document.getElementById('checkOutDate').value;
        const checkOutTime = document.getElementById('checkOutTime').value;
        const notes = document.getElementById('notes').value;
        const paymentMethod = document.querySelector('input[name="paymentSource"]:checked').value;
        
        // Get selected invoice methods (checkboxes)
        const invoiceMethods = [];
        document.querySelectorAll('input[name="invoiceMethod"]:checked').forEach(checkbox => {
          invoiceMethods.push(checkbox.value);
        });
        
        const totalAmount = parseFloat(document.getElementById('totalAmount').textContent.replace(/[^0-9.-]+/g, '')) || 0;
        
        if (!email) {
          showToast('❌ Email is required');
          return;
        }
        
        if (!roomType) {
          showToast('❌ Please select a room type');
          return;
        }
        
        if (!checkInDate || !checkOutDate) {
          showToast('❌ Please select both check-in and check-out dates');
          return;
        }
        
        // Format guest name with middle initial if provided
        const guestName = middleInitial 
          ? `${firstName} ${middleInitial}. ${lastName}`
          : `${firstName} ${lastName}`;
        
        // Room number will be automatically assigned by the server
        const guests = document.getElementById('guests')?.value || 1; // Default to 1 guest
        
        // Prepare form data for submission - matching API expected format
        const formData = {
          firstName: firstName,
          lastName: lastName,
          checkInDate: checkInDate,
          checkInTime: checkInTime,
          checkOutDate: checkOutDate,
          checkOutTime: checkOutTime,
          roomType: roomType,
          // Room number will be assigned by the server
          occupancy: guests,
          totalAmount: totalAmount,
          email: email,
          phone: phone,
          notes: notes || '',
          paymentMethod: paymentMethod,
          invoiceMethod: invoiceMethods.join(','),
          status: 'confirmed',
          amenities: roomTypeAmenities[roomType] ? roomTypeAmenities[roomType].join(',') : ''
        };

        try {
          // Calculate number of nights for the stay
          const checkIn = new Date(formData.checkin);
          const checkOut = new Date(formData.checkout);
          const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
          
          // Add nights to form data
          formData.nights = nights;
          
          // Make API request to save the reservation
          const response = await fetch('api/create-reservation.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
          });
          
          const result = await response.json();
          
          if (result.success) {
            showToast('✅ Reservation saved successfully!');
            closeModal();
            // Reload the page to show the new reservation
            setTimeout(() => window.location.reload(), 1000);
          } else {
            throw new Error(result.message || 'Failed to save reservation');
          }
        } catch (error) {
          console.error('Error saving reservation:', error);
          showToast(`❌ Error: ${error.message || 'Failed to save reservation'}`);
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
    </script>
  </body>
  </html>


