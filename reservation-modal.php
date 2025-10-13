<!-- Reservation Modal -->
<div id="reservationModal" class="fixed inset-0 bg-black/50 z-50 hidden">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b">
        <h2 class="text-xl font-semibold">New Reservation</h2>
        <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">
          <i data-lucide="x" class="h-6 w-6"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <form id="reservationForm" class="p-6">
        <!-- Guest Selection Section -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Guest Information</h3>
            <div class="flex gap-2">
              <button type="button" id="existingGuestBtn" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                Select Existing Guest
              </button>
              <button type="button" id="newGuestBtn" class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                Create New Guest
              </button>
            </div>
          </div>

          <!-- Existing Guest Selection -->
          <div id="existingGuestSection" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search and Select Guest</label>
            <div class="relative">
              <input type="text" id="guestSearch" placeholder="Search guests by name, email, or phone..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <div id="guestSearchResults" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden">
                <div id="guestsLoading" class="p-3 text-sm text-gray-500">Loading guests...</div>
                <div id="noGuestsFound" class="p-3 text-sm text-gray-500 hidden">No guests found</div>
              </div>
            </div>
            <div id="selectedGuestInfo" class="mt-2 p-3 bg-gray-50 rounded hidden">
              <p class="text-sm text-gray-600">Selected: <span id="selectedGuestName" class="font-medium"></span></p>
              <input type="hidden" id="guest_id" name="guest_id">
            </div>
            <div id="guestsFetchStatus" class="mt-2 text-xs text-gray-500">Guests: <span id="guestsStatus">Not loaded</span></div>
          </div>

          <!-- New Guest Form -->
          <div id="newGuestSection" class="mb-4 hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                <input type="text" id="first_name" name="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                <input type="text" id="last_name" name="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="tel" id="phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" id="address" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input type="text" id="city" name="city" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <input type="text" id="country" name="country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Type</label>
                <select id="id_type" name="id_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                  <option value="National ID">National ID</option>
                  <option value="Passport">Passport</option>
                  <option value="Driver License">Driver License</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                <input type="text" id="id_number" name="id_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                <input type="text" id="nationality" name="nationality" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
            </div>
          </div>
        </div>

        <!-- Room Selection Section -->
        <div class="mb-6">
          <h3 class="text-lg font-medium mb-4">Room Selection</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Select Room</label>
              <select id="room_id" name="room_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Loading rooms...</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Room Preview</label>
              <div id="selectedRoomInfo" class="p-3 bg-gray-50 rounded min-h-[44px] flex items-center">
                <span class="text-sm text-gray-500">Select a room to see details</span>
              </div>
            </div>
          </div>
          <div id="roomsFetchStatus" class="mt-2 text-xs text-gray-500">Rooms: <span id="roomsStatus">Not loaded</span></div>
        </div>

        <!-- Date and Time Section -->
        <div class="mb-6">
          <h3 class="text-lg font-medium mb-4">Reservation Dates</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Check-in Date & Time *</label>
              <input type="datetime-local" id="check_in_date" name="check_in_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Check-out Date & Time *</label>
              <input type="datetime-local" id="check_out_date" name="check_out_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
          </div>
        </div>

        <!-- Payment Section -->
        <div class="mb-6">
          <h3 class="text-lg font-medium mb-4">Payment Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">Invoice Method</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="radio" name="invoice_method" value="print" class="mr-2" checked>
                  <span class="text-sm">Print</span>
                </label>
                <label class="flex items-center">
                  <input type="radio" name="invoice_method" value="email" class="mr-2">
                  <span class="text-sm">Email</span>
                </label>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">Payment Source</label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input type="radio" name="payment_source" value="cash" class="mr-2" checked>
                  <span class="text-sm">Cash</span>
                </label>
                <label class="flex items-center">
                  <input type="radio" name="payment_source" value="online" class="mr-2" disabled>
                  <span class="text-sm text-gray-400">Online (Coming Soon)</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Section -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Reservation Status</label>
          <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="Pending">Pending</option>
            <option value="Checked In">Checked In</option>
            <option value="Checked Out">Checked Out</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Submit Section -->
        <div class="flex justify-end gap-3 pt-6 border-t">
          <button type="button" id="cancelBtn" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded">
            Cancel
          </button>
          <button type="submit" id="submitBtn" class="px-4 py-2 text-sm bg-blue-600 text-white hover:bg-blue-700 rounded disabled:opacity-50 disabled:cursor-not-allowed">
            Create Reservation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal elements
  const modal = document.getElementById('reservationModal');
  const openModalBtn = document.getElementById('openModalBtn');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const reservationForm = document.getElementById('reservationForm');

  // Guest section elements
  const existingGuestBtn = document.getElementById('existingGuestBtn');
  const newGuestBtn = document.getElementById('newGuestBtn');
  const existingGuestSection = document.getElementById('existingGuestSection');
  const newGuestSection = document.getElementById('newGuestSection');
  const guestSearch = document.getElementById('guestSearch');
  const guestSearchResults = document.getElementById('guestSearchResults');
  const guestsLoading = document.getElementById('guestsLoading');
  const noGuestsFound = document.getElementById('noGuestsFound');
  const selectedGuestInfo = document.getElementById('selectedGuestInfo');
  const selectedGuestName = document.getElementById('selectedGuestName');
  const guestIdInput = document.getElementById('guest_id');
  const guestsStatus = document.getElementById('guestsStatus');

  // Room section elements
  const roomSelect = document.getElementById('room_id');
  const selectedRoomInfo = document.getElementById('selectedRoomInfo');
  const roomsStatus = document.getElementById('roomsStatus');

  // Form data
  let guests = [];
  let rooms = [];

  // Initialize modal
  function initModal() {
    loadGuests();
    loadRooms();

    // Set default check-in time to now, check-out to tomorrow
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    document.getElementById('check_in_date').value = formatDateTimeLocal(now);
    document.getElementById('check_out_date').value = formatDateTimeLocal(tomorrow);
  }

  // Modal event listeners
  if (openModalBtn) {
    openModalBtn.addEventListener('click', function() {
      modal.classList.remove('hidden');
      initModal();
    });
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeModal);
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

  // Close modal when clicking outside
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });

  function closeModal() {
    modal.classList.add('hidden');
    reservationForm.reset();
    // Reset to existing guest view
    showExistingGuestSection();
  }

  // Guest section toggle
  if (existingGuestBtn) {
    existingGuestBtn.addEventListener('click', showExistingGuestSection);
  }

  if (newGuestBtn) {
    newGuestBtn.addEventListener('click', showNewGuestSection);
  }

  function showExistingGuestSection() {
    existingGuestSection.classList.remove('hidden');
    newGuestSection.classList.add('hidden');
    existingGuestBtn.classList.add('bg-blue-600');
    existingGuestBtn.classList.remove('bg-gray-600');
    newGuestBtn.classList.add('bg-green-600');
    newGuestBtn.classList.remove('bg-gray-600');
  }

  function showNewGuestSection() {
    existingGuestSection.classList.add('hidden');
    newGuestSection.classList.remove('hidden');
    newGuestBtn.classList.add('bg-green-600');
    newGuestBtn.classList.remove('bg-gray-600');
    existingGuestBtn.classList.add('bg-blue-600');
    existingGuestBtn.classList.remove('bg-gray-600');
  }

  // Load guests from API
  async function loadGuests() {
    try {
      guestsStatus.textContent = 'Loading...';
      const response = await fetch('http://localhost/hmscore1last1/api/guests');
      const data = await response.json();

      if (data.data && Array.isArray(data.data)) {
        guests = data.data;
        guestsStatus.textContent = `Loaded (${guests.length} guests)`;
      } else {
        guests = [];
        guestsStatus.textContent = 'Error loading guests';
      }
    } catch (error) {
      console.error('Error loading guests:', error);
      guests = [];
      guestsStatus.textContent = 'Error loading guests';
    }
  }

  // Guest search functionality
  if (guestSearch) {
    guestSearch.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();

      if (query.length === 0) {
        guestSearchResults.classList.add('hidden');
        return;
      }

      const filteredGuests = guests.filter(guest =>
        (guest.name && guest.name.toLowerCase().includes(query)) ||
        (guest.email && guest.email.toLowerCase().includes(query)) ||
        (guest.phone && guest.phone.toLowerCase().includes(query))
      );

      showGuestSearchResults(filteredGuests, query);
    });
  }

  function showGuestSearchResults(results, query) {
    guestSearchResults.classList.remove('hidden');
    guestsLoading.classList.add('hidden');
    noGuestsFound.classList.add('hidden');

    if (results.length === 0) {
      noGuestsFound.classList.remove('hidden');
      return;
    }

    // Clear previous results
    guestSearchResults.innerHTML = '';
    guestsLoading.classList.add('hidden');
    noGuestsFound.classList.add('hidden');

    results.forEach(guest => {
      const div = document.createElement('div');
      div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
      div.innerHTML = `
        <div class="font-medium">${guest.name || 'Unknown'}</div>
        <div class="text-sm text-gray-500">${guest.email || ''} ${guest.phone || ''}</div>
      `;
      div.addEventListener('click', () => selectGuest(guest));
      guestSearchResults.appendChild(div);
    });
  }

  function selectGuest(guest) {
    selectedGuestName.textContent = guest.name || 'Unknown';
    guestIdInput.value = guest.id;
    selectedGuestInfo.classList.remove('hidden');
    guestSearchResults.classList.add('hidden');
    guestSearch.value = guest.name || '';
  }

  // Load rooms from API
  async function loadRooms() {
    try {
      roomsStatus.textContent = 'Loading...';
      const response = await fetch('http://localhost/hmscore1last1/api/rooms');
      const data = await response.json();

      if (data.data && Array.isArray(data.data)) {
        rooms = data.data;
        populateRoomSelect(rooms);
        roomsStatus.textContent = `Loaded (${rooms.length} rooms)`;
      } else {
        rooms = [];
        roomsStatus.textContent = 'Error loading rooms';
      }
    } catch (error) {
      console.error('Error loading rooms:', error);
      rooms = [];
      roomsStatus.textContent = 'Error loading rooms';
    }
  }

  function populateRoomSelect(rooms) {
    roomSelect.innerHTML = '<option value="">Select a room...</option>';

    // Filter only VACANT rooms and sort by room number
    const vacantRooms = rooms
      .filter(room => room.status === 'Vacant' || room.status === 'VACANT')
      .sort((a, b) => {
        const aNum = parseInt(a.room_number);
        const bNum = parseInt(b.room_number);
        return aNum - bNum;
      });

    vacantRooms.forEach(room => {
      const option = document.createElement('option');
      option.value = room.id;
      option.textContent = `${room.room_number} - ${room.room_type} (₱${room.rate || 0})`;
      roomSelect.appendChild(option);
    });

    if (vacantRooms.length === 0) {
      roomSelect.innerHTML = '<option value="">No vacant rooms available</option>';
    }
  }

  // Room selection change handler
  if (roomSelect) {
    roomSelect.addEventListener('change', function() {
      const selectedRoomId = this.value;
      const room = rooms.find(r => r.id == selectedRoomId);

      if (room) {
        selectedRoomInfo.innerHTML = `
          <div>
            <div class="font-medium">${room.room_number} - ${room.room_type}</div>
            <div class="text-sm text-gray-500">Rate: ₱${room.rate || 0} | Floor: ${room.floor_number} | Max Guests: ${room.max_guests}</div>
            ${room.amenities ? `<div class="text-sm text-gray-500 mt-1">Amenities: ${room.amenities}</div>` : ''}
          </div>
        `;
      } else {
        selectedRoomInfo.innerHTML = '<span class="text-sm text-gray-500">Select a room to see details</span>';
      }
    });
  }

  // Form submission
  if (reservationForm) {
    reservationForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creating...';

      try {
        // Determine if we're using existing guest or creating new guest
        let guestId = null;

        if (!newGuestSection.classList.contains('hidden')) {
          // Creating new guest first
          const newGuestData = {
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            country: document.getElementById('country').value,
            id_type: document.getElementById('id_type').value,
            id_number: document.getElementById('id_number').value,
            date_of_birth: document.getElementById('date_of_birth').value,
            nationality: document.getElementById('nationality').value
          };

          const guestResponse = await fetch('http://localhost/hmscore1last1/api/guests', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(newGuestData)
          });

          const guestData = await guestResponse.json();

          if (!guestData.ok) {
            throw new Error(guestData.message || 'Failed to create guest');
          }

          guestId = guestData.id;
        } else {
          // Using existing guest
          guestId = guestIdInput.value;
          if (!guestId) {
            throw new Error('Please select a guest');
          }
        }

        // Create reservation
        const reservationData = {
          guest_id: guestId,
          room_id: document.getElementById('room_id').value,
          check_in_date: document.getElementById('check_in_date').value,
          check_out_date: document.getElementById('check_out_date').value,
          status: document.getElementById('status').value,
          invoice_method: document.querySelector('input[name="invoice_method"]:checked').value,
          payment_source: document.querySelector('input[name="payment_source"]:checked').value
        };

        // Here you would submit to your reservations API endpoint
        console.log('Reservation data:', reservationData);

        // For now, just show success
        alert('Reservation created successfully!');
        closeModal();

      } catch (error) {
        console.error('Error creating reservation:', error);
        alert('Error: ' + error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Reservation';
      }
    });
  }

  // Helper function to format datetime for input
  function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }
});
</script>
