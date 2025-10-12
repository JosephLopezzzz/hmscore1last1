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
              
              <div>
                <label class="block text-sm font-medium mb-1">Room Type <span class="text-red-500">*</span></label>
                <select id="roomType" required class="w-full rounded-md border px-3 py-2 text-sm">
                  <option value="">Select room type...</option>
                  <option value="general">General (Floors 1-3) - ₱300/8hrs</option>
                  <option value="deluxe">Deluxe (Floor 4) - ₱400/8hrs</option>
                  <option value="executive">Executive (Floor 5) - ₱500/8hrs</option>
                  <option value="luxury">Luxury (Floor 5) - ₱600/8hrs</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Floor <span class="text-red-500">*</span></label>
                <select id="floorSelect" required class="w-full rounded-md border px-3 py-2 text-sm" disabled>
                  <option value="">Select room type first</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Room Number <span class="text-red-500">*</span></label>
                <select id="roomNumber" required class="w-full rounded-md border px-3 py-2 text-sm" disabled>
                  <option value="">Select floor first</option>
                </select>
                <p id="roomStatus" class="text-xs text-muted-foreground mt-1"></p>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Occupancy (1-3) <span class="text-red-500">*</span></label>
                <input type="number" id="occupancy" min="1" max="3" value="1" required 
                       class="w-full rounded-md border px-3 py-2 text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Special Requests</label>
                <textarea id="specialRequests" rows="2" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
              </div>
            </div>
            
            <!-- Right Column -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold border-b pb-2">Guest Information</h3>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">First Name <span class="text-red-500">*</span></label>
                  <input type="text" id="firstName" required class="w-full rounded-md border px-3 py-2 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Last Name <span class="text-red-500">*</span></label>
                  <input type="text" id="lastName" required class="w-full rounded-md border px-3 py-2 text-sm">
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" id="email" class="w-full rounded-md border px-3 py-2 text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="tel" id="phone" class="w-full rounded-md border px-3 py-2 text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium mb-1">Birthdate</label>
                <input type="date" id="birthdate" class="w-full rounded-md border px-3 py-2 text-sm">
              </div>
              
              <div class="pt-4 border-t mt-4">
                <div class="flex justify-between mb-2">
                  <span class="font-medium">Total Amount:</span>
                  <span id="totalAmount" class="font-bold text-lg">₱0.00</span>
                </div>
                
                <div class="mb-4">
                  <label class="block text-sm font-medium mb-2">Invoice Method</label>
                  <div class="flex space-x-6">
                    <label class="inline-flex items-center">
                      <input type="radio" name="invoiceMethod" value="email" class="form-radio">
                      <span class="ml-2">Email</span>
                    </label>
                    <label class="inline-flex items-center">
                      <input type="radio" name="invoiceMethod" value="print" checked class="form-radio">
                      <span class="ml-2">Print</span>
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

      // Room type configuration
      const roomConfig = {
        'general': {
          floors: [1, 2, 3],
          rate: 300,
          roomsPerFloor: 5,
          startNumber: 100
        },
        'deluxe': {
          floors: [4],
          rate: 400,
          roomsPerFloor: 5,
          startNumber: 400
        },
        'executive': {
          floors: [5],
          rate: 500,
          roomsPerFloor: 3,
          startNumber: 500
        },
        'luxury': {
          floors: [5],
          rate: 600,
          roomsPerFloor: 2,
          startNumber: 580
        }
      };

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
      });

      // Close modal functions
      function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        reservationForm.reset();
        roomNumberSelect.innerHTML = '<option value="">Select room type first</option>';
        roomNumberSelect.disabled = true;
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

      // Update floor options when room type changes
      roomTypeSelect.addEventListener('change', function() {
        const roomType = this.value;
        const floorSelect = document.getElementById('floorSelect');
        const roomNumberSelect = document.getElementById('roomNumber');
        
        // Reset and disable dependent fields
        floorSelect.innerHTML = '<option value="">Select floor...</option>';
        floorSelect.disabled = !roomType;
        
        roomNumberSelect.innerHTML = '<option value="">Select floor first</option>';
        roomNumberSelect.disabled = true;
        
        if (!roomType) return;
        
        // Populate floor options based on room type
        roomConfig[roomType].floors.forEach(floor => {
          const option = document.createElement('option');
          option.value = floor;
          option.textContent = `Floor ${floor}`;
          floorSelect.appendChild(option);
        });
        
        // Update total amount when room type changes
        updateTotalAmount();
      });
      
      // Load all rooms when floor is selected, showing status for each
      document.getElementById('floorSelect').addEventListener('change', async function() {
        const floor = this.value;
        const roomType = roomTypeSelect.value;
        const roomNumberSelect = document.getElementById('roomNumber');
        const roomStatus = document.getElementById('roomStatus');
        
        roomNumberSelect.innerHTML = '<option value="">Loading rooms...</option>';
        roomNumberSelect.disabled = true;
        roomStatus.textContent = '';
        
        if (!floor || !roomType) {
          roomNumberSelect.innerHTML = '<option value="">Select floor first</option>';
          return;
        }
        
        try {
          // Fetch all rooms for the floor, including occupied and maintenance
          const response = await fetch(`api/get-available-rooms.php?type=${roomType}&floor=${floor}&checkIn=${checkInDate.value}&checkOut=${checkOutDate.value}&showAll=1`);
          const rooms = await response.json();
          
          roomNumberSelect.innerHTML = '';
          
          if (rooms.length === 0) {
            roomNumberSelect.innerHTML = '<option value="">No rooms on this floor</option>';
            roomStatus.textContent = 'No rooms found on this floor.';
            return;
          }
          
          // Group rooms by status
          const availableRooms = [];
          const occupiedRooms = [];
          const maintenanceRooms = [];
          
          rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room.number;
            option.textContent = `Room ${room.number}`;
            
            // Set status and disable if not available
            if (room.status === 'available') {
              availableRooms.push(option);
            } else if (room.status === 'occupied') {
              option.disabled = true;
              option.textContent += ' (Occupied)';
              occupiedRooms.push(option);
            } else if (room.status === 'maintenance') {
              option.disabled = true;
              option.textContent += ' (Maintenance)';
              maintenanceRooms.push(option);
            }
          });
          
          // Add available rooms first
          if (availableRooms.length > 0) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = 'Available Rooms';
            availableRooms.forEach(option => optgroup.appendChild(option));
            roomNumberSelect.appendChild(optgroup);
          }
          
          // Add occupied rooms with disabled optgroup
          if (occupiedRooms.length > 0) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = 'Currently Occupied';
            optgroup.disabled = true;
            occupiedRooms.forEach(option => optgroup.appendChild(option));
            roomNumberSelect.appendChild(optgroup);
          }
          
          // Add maintenance rooms with disabled optgroup
          if (maintenanceRooms.length > 0) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = 'Under Maintenance';
            optgroup.disabled = true;
            maintenanceRooms.forEach(option => optgroup.appendChild(option));
            roomNumberSelect.appendChild(optgroup);
          }
          
          // Enable the select if there are available rooms
          roomNumberSelect.disabled = availableRooms.length === 0;
          
          // Update status message
          if (availableRooms.length === 0) {
            roomStatus.textContent = 'No available rooms on this floor for the selected dates.';
            roomStatus.className = 'text-xs text-destructive mt-1';
          } else {
            roomStatus.textContent = `${availableRooms.length} room(s) available`;
            roomStatus.className = 'text-xs text-success mt-1';
          }
          
        } catch (error) {
          console.error('Error loading rooms:', error);
          roomNumberSelect.innerHTML = '<option value="">Error loading rooms</option>';
          roomStatus.textContent = 'Error loading room availability. Please try again.';
          roomStatus.className = 'text-xs text-destructive mt-1';
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

        const rate = roomConfig[roomType]?.rate || 0;
        
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

      // Handle form submission
      reservationForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        // Get form data
        const formData = {
          firstName: document.getElementById('firstName').value.trim(),
          lastName: document.getElementById('lastName').value.trim(),
          email: document.getElementById('email').value.trim(),
          phone: document.getElementById('phone').value.trim(),
          birthdate: document.getElementById('birthdate').value,
          checkInDate: checkInDate.value,
          checkInTime: checkInTime.value,
          checkOutDate: checkOutDate.value,
          checkOutTime: checkOutTime.value,
          roomType: roomTypeSelect.value,
          roomNumber: roomNumberSelect.value,
          occupancy: document.getElementById('occupancy').value,
          specialRequests: document.getElementById('specialRequests').value,
          invoiceMethod: document.querySelector('input[name="invoiceMethod"]:checked').value,
          paymentSource: document.querySelector('input[name="paymentSource"]:checked').value,
          totalAmount: totalAmount.textContent.replace('₱', '')
        };

        // Basic validation
        if (!formData.roomNumber) {
          alert('Please select a room number');
          return;
        }

        // In a real app, this would make an AJAX request to save the reservation
        try {
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


