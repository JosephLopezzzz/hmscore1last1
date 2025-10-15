<?php
require_once __DIR__ . '/includes/security.php';
?>

<!-- Reservation Modal -->
<div id="reservationModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-4 border-b flex-shrink-0 bg-gray-50">
        <h2 class="text-lg font-semibold text-gray-800">New Reservation</h2>
        <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700 transition-colors p-2 hover:bg-gray-100 rounded-full">
          <i data-lucide="x" class="h-5 w-5"></i>
        </button>
      </div>

      <!-- Modal Body - Scrollable Content -->
      <div class="flex-1 overflow-y-auto bg-white">
        <form id="reservationForm" class="p-4">
          <!-- Two Column Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column: Guest Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-medium mb-4 border-b pb-2">Guest Information</h3>

              <!-- Search Section -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Existing Guests</label>
                <div class="relative">
                  <input type="text" id="guestSearch" placeholder="Search guests by name, email, or phone..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <div id="guestSearchResults" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden">
                    <div id="guestsLoading" class="p-3 text-sm text-gray-500">Loading guests...</div>
                    <div id="noGuestsFound" class="p-3 text-sm text-gray-500 hidden">No guests found</div>
                  </div>
                </div>
                <div id="selectedGuestInfo" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded hidden">
                  <p class="text-sm text-blue-600">Selected: <span id="selectedGuestName" class="font-medium"></span></p>
                  <input type="hidden" id="guest_id" name="guest_id">
                </div>
                <div id="guestsFetchStatus" class="mt-2 text-xs text-gray-500">Guests: <span id="guestsStatus">Not loaded</span></div>
              </div>

              <!-- New Guest Form -->
              <div id="newGuestSection">
                <div class="mb-3">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Or Create New Guest</label>
                </div>
                <div class="grid grid-cols-1 gap-3">
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                      <input type="text" id="first_name" name="first_name" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                      <input type="text" id="last_name" name="last_name" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                      <input type="email" id="email" name="email" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                      <input type="tel" id="phone" name="phone" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                      <input type="text" id="address" name="address" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                      <input type="text" id="city" name="city" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                      <input type="text" id="country" name="country" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                      <input type="text" id="nationality" name="nationality" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">ID Type</label>
                      <select id="id_type" name="id_type" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="National ID">National ID</option>
                        <option value="Passport">Passport</option>
                        <option value="Driver License">Driver License</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">ID Number *</label>
                      <input type="text" id="id_number" name="id_number" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                    <div id="dateOfBirthError" class="mt-1 text-sm text-red-600 hidden"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Room & Reservation Info -->
            <div class="space-y-4">
              <!-- Room Selection Section -->
              <div>
                <h3 class="text-lg font-medium mb-3 border-b pb-2">Room Selection</h3>
                <div class="grid grid-cols-1 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Room</label>
                    <select id="room_id" name="room_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required>
                      <option value="">Loading rooms...</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room Preview</label>
                    <div id="selectedRoomInfo" class="p-3 bg-gray-50 rounded min-h-[40px] flex items-center text-sm">
                      <span class="text-sm text-gray-500">Select a room to see details</span>
                    </div>
                  </div>
                </div>
                <div id="roomsFetchStatus" class="mt-2 text-xs text-gray-500">Rooms: <span id="roomsStatus">Not loaded</span></div>
              </div>

              <!-- Date and Time Section -->
              <div>
                <h3 class="text-lg font-medium mb-3 border-b pb-2">Reservation Dates</h3>
                <div class="grid grid-cols-1 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Check-in Date & Time *</label>
                    <input type="datetime-local" id="check_in_date" name="check_in_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required min="">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Check-out Date & Time *</label>
                    <input type="datetime-local" id="check_out_date" name="check_out_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" required min="">
                  </div>
                </div>
              </div>

              <!-- Status Section (hidden: always pending on create) -->
              <div class="hidden">
                <h3 class="text-lg font-medium mb-3 border-b pb-2">Reservation Status</h3>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                  <option value="Pending" selected>Pending</option>
                  <option value="Checked In">Checked In</option>
                  <option value="Checked Out">Checked Out</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>

              <!-- Hidden Payment Status Field -->
              <input type="hidden" id="payment_status" name="payment_status" value="PENDING">
          </div>

          <!-- Submit Section - Full Width -->
          <div class="mt-6 pt-4 border-t bg-gray-50 -mx-4 px-4 -mb-4 pb-4">
            <div class="flex justify-end gap-3">
              <button type="button" id="cancelBtn" class="px-4 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded transition-colors">
                Cancel
              </button>
              <button type="submit" id="submitBtn" class="px-4 py-2 text-sm bg-blue-600 text-white hover:bg-blue-700 rounded disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Create Reservation
              </button>
            </div>
          </div>
        </form>
      </div>
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

  // Helper: format Date to input[type=datetime-local] value (YYYY-MM-DDTHH:MM)
  function formatDateTimeLocal(date) {
    if (!(date instanceof Date)) return '';
    const pad = (n) => String(n).padStart(2, '0');
    const y = date.getFullYear();
    const m = pad(date.getMonth() + 1);
    const d = pad(date.getDate());
    const h = pad(date.getHours());
    const min = pad(date.getMinutes());
    return `${y}-${m}-${d}T${h}:${min}`;
  }

  // Initialize modal
  function initModal() {
    loadGuests();
    loadRooms();

    // Set default check-in time to now, check-out to tomorrow
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const checkinInput = document.getElementById('check_in_date');
    const checkoutInput = document.getElementById('check_out_date');

    checkinInput.value = formatDateTimeLocal(now);
    checkoutInput.value = formatDateTimeLocal(tomorrow);

    // Set minimum date/time to 5 minutes ago to allow reasonable form completion time
    const fiveMinutesAgo = new Date(now.getTime() - (5 * 60 * 1000));
    checkinInput.min = formatDateTimeLocal(fiveMinutesAgo);
    checkoutInput.min = formatDateTimeLocal(fiveMinutesAgo);

    // Initialize guest selection state
    checkGuestSelection();
  }

  // Modal event listeners
  if (openModalBtn) {
    openModalBtn.addEventListener('click', function() {
      modal.classList.remove('hidden');
      initModal();
    });
  }

  // Allow other pages to open and prefill the modal for a specific guest
  window.openReservationModalForGuest = function(guestId, guestName, guestData) {
    modal.classList.remove('hidden');
    try { initModal(); } catch (e) { console.error('initModal error:', e); }

    // Prefill display immediately
    if (guestId) {
      guestIdInput.value = guestId;
      selectedGuestName.textContent = guestName || '';
      guestSearch.value = guestName || '';
      selectedGuestInfo.classList.remove('hidden');
    }

    // Prefill detailed fields once guests are loaded
    function prefillGuestFields(g) {
      if (!g) return;
      // Write fields for clarity in case user wants to edit
      const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
      setVal('first_name', g.first_name || '');
      setVal('last_name', g.last_name || '');
      setVal('email', g.email || '');
      setVal('phone', g.phone || '');
      setVal('address', g.address || '');
      setVal('city', g.city || '');
      setVal('country', g.country || '');
      setVal('id_type', g.id_type || 'National ID');
      setVal('id_number', g.id_number || '');
      setVal('nationality', g.nationality || '');
      if (g.date_of_birth) {
        const dobEl = document.getElementById('date_of_birth');
        if (dobEl) dobEl.value = String(g.date_of_birth).substring(0, 10);
      }
      // Lock fields since we will use existing guest_id
      toggleNewGuestInputs(true);
      checkGuestSelection();
    }

    // Try immediate match from provided data first
    if (guestData && typeof guestData === 'object') {
      prefillGuestFields(guestData);
    }

    // Then try lookup from loaded guests; else wait until guests loaded
    let attempts = 0;
    (function tryPrefill() {
      attempts++;
      const g = Array.isArray(guests) ? guests.find(x => String(x.id) === String(guestId)) : null;
      if (g) {
        prefillGuestFields(g);
      } else if (attempts < 30) { // ~3s @100ms
        setTimeout(tryPrefill, 100);
      }
    })();
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
    // Reset guest selection
    selectedGuestInfo.classList.add('hidden');
    guestIdInput.value = '';
    // Re-enable new guest inputs
    toggleNewGuestInputs(false);
  }

  // Toggle new guest inputs based on search selection
  function toggleNewGuestInputs(disable) {
    const newGuestInputs = newGuestSection.querySelectorAll('input, select');
    newGuestInputs.forEach(input => {
      input.disabled = disable;
      if (disable) {
        input.classList.add('bg-gray-100', 'cursor-not-allowed');
      } else {
        input.classList.remove('bg-gray-100', 'cursor-not-allowed');
      }
    });
  }

  // Monitor guest selection changes
  function checkGuestSelection() {
    if (guestIdInput.value) {
      // Guest selected from search - disable new guest inputs
      toggleNewGuestInputs(true);
      selectedGuestInfo.classList.remove('hidden');
    } else {
      // No guest selected - enable new guest inputs
      toggleNewGuestInputs(false);
      selectedGuestInfo.classList.add('hidden');
    }
  }

  // Guest section toggle (REMOVED - now unified interface)
  // if (existingGuestBtn) {
  //   existingGuestBtn.addEventListener('click', showExistingGuestSection);
  // }

  // if (newGuestBtn) {
  //   newGuestBtn.addEventListener('click', showNewGuestSection);
  // }

  // function showExistingGuestSection() {
  //   existingGuestSection.classList.remove('hidden');
  //   newGuestSection.classList.add('hidden');
  //   existingGuestBtn.classList.add('bg-blue-600');
  //   existingGuestBtn.classList.remove('bg-gray-600');
  //   newGuestBtn.classList.add('bg-green-600');
  //   newGuestBtn.classList.remove('bg-gray-600');
  // }

  // function showNewGuestSection() {
  //   existingGuestSection.classList.add('hidden');
  //   newGuestSection.classList.remove('hidden');
  //   newGuestBtn.classList.add('bg-green-600');
  //   newGuestBtn.classList.remove('bg-gray-600');
  //   existingGuestBtn.classList.add('bg-blue-600');
  //   existingGuestBtn.classList.remove('bg-gray-600');
  // }

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
        // Search by first_name + last_name combination
        (guest.first_name && guest.last_name && 
         `${guest.first_name} ${guest.last_name}`.toLowerCase().includes(query)) ||
        // Search by individual fields
        (guest.first_name && guest.first_name.toLowerCase().includes(query)) ||
        (guest.last_name && guest.last_name.toLowerCase().includes(query)) ||
        (guest.email && guest.email.toLowerCase().includes(query)) ||
        (guest.phone && guest.phone.toLowerCase().includes(query)) ||
        // Fallback to name field if it exists
        (guest.name && guest.name.toLowerCase().includes(query))
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
      
      // Properly format guest name from first_name and last_name
      const guestName = guest.first_name && guest.last_name 
        ? `${guest.first_name} ${guest.last_name}` 
        : (guest.name || 'Unknown');
        
      div.innerHTML = `
        <div class="font-medium">${guestName}</div>
        <div class="text-sm text-gray-500">${guest.email || ''} ${guest.phone || ''}</div>
      `;
      div.addEventListener('click', () => selectGuest(guest));
      guestSearchResults.appendChild(div);
    });
  }

  function selectGuest(guest) {
    // Properly format guest name from first_name and last_name
    const guestName = guest.first_name && guest.last_name 
      ? `${guest.first_name} ${guest.last_name}` 
      : (guest.name || 'Unknown');
      
    selectedGuestName.textContent = guestName;
    guestIdInput.value = guest.id;
    selectedGuestInfo.classList.remove('hidden');
    guestSearchResults.classList.add('hidden');
    guestSearch.value = guestName;
    // Update UI state when guest is selected
    checkGuestSelection();
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
        // Validate dates are not in the past
        const checkinDateTime = new Date(document.getElementById('check_in_date').value);
        const checkoutDateTime = new Date(document.getElementById('check_out_date').value);
        const now = new Date();

        // Allow 5 minutes buffer for form completion time
        const fiveMinutesAgo = new Date(now.getTime() - (5 * 60 * 1000));

        if (checkinDateTime < fiveMinutesAgo) {
          throw new Error('Check-in date/time cannot be more than 5 minutes in the past');
        }

        if (checkoutDateTime < fiveMinutesAgo) {
          throw new Error('Check-out date/time cannot be more than 5 minutes in the past');
        }

        if (checkoutDateTime <= checkinDateTime) {
          throw new Error('Check-out date/time must be after check-in date/time');
        }

        // Validate date of birth before proceeding
        if (!validateDateOfBirth()) {
          throw new Error('Please fix the date of birth validation errors');
        }

        // Determine if we're using existing guest or creating new guest
        let guestId = null;

        // Clean logic: use hidden guest_id input as single source of truth
        if (guestIdInput.value) {
          // Use existing guest selected from search
          guestId = guestIdInput.value;
        } else if (document.getElementById('first_name').value.trim() && document.getElementById('last_name').value.trim()) {
          // Create new guest since no search selection exists
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
          throw new Error('Please select an existing guest from the search or fill in the guest information form');
        }

        // Create reservation
        const reservationData = {
          guest_id: guestId,
          room_id: document.getElementById('room_id').value,
          check_in_date: document.getElementById('check_in_date').value,
          check_out_date: document.getElementById('check_out_date').value,
          status: 'Pending'
        };

        const reservationResponse = await fetch('http://localhost/hmscore1last1/api/reservations', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(reservationData)
        });

        const reservationResult = await reservationResponse.json();

        if (!reservationResult.ok) {
          throw new Error(reservationResult.message || 'Failed to create reservation');
        }

        alert('Reservation created successfully!');
        location.reload(); // Refresh to show new reservation

        // Here you would submit to your reservations API endpoint
        console.log('Reservation data:', reservationData);

      } catch (error) {
        console.error('Error creating reservation:', error);
        alert('Error: ' + error.message);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Reservation';
      }
    });
  }

  // Date of birth validation
  const dateOfBirthInput = document.getElementById('date_of_birth');
  const dateOfBirthError = document.getElementById('dateOfBirthError');

  function validateDateOfBirth() {
    const selectedDate = new Date(dateOfBirthInput.value);
    const today = new Date();
    const errorMessages = [];

    // Reset error display
    dateOfBirthError.classList.add('hidden');
    dateOfBirthError.textContent = '';

    // Check if date is selected
    if (!dateOfBirthInput.value) {
      return true; // Allow empty dates for optional fields
    }

    // Check for future dates
    if (selectedDate > today) {
      errorMessages.push('Date of birth cannot be in the future');
    }

    // Calculate age
    const age = Math.floor((today - selectedDate) / (365.25 * 24 * 60 * 60 * 1000));

    // Check minimum age (18)
    if (age < 18) {
      errorMessages.push('Must be at least 18 years old');
    }

    // Check maximum age (80)
    if (age >= 80) {
      errorMessages.push('Age 80 or older is not allowed');
    }

    // Display errors if any
    if (errorMessages.length > 0) {
      dateOfBirthError.textContent = errorMessages.join(', ');
      dateOfBirthError.classList.remove('hidden');
      return false;
    }

    return true;
  }

  // Set date input constraints
  function setDateOfBirthConstraints() {
    const today = new Date();
    const minDate = new Date(today);
    const maxDate = new Date(today);

    // Set minimum date to 80 years ago (maximum allowed age)
    minDate.setFullYear(today.getFullYear() - 80);

    // Set maximum date to 18 years ago (minimum allowed age)
    maxDate.setFullYear(today.getFullYear() - 18);

    // Format dates for input constraints
    const minDateString = minDate.toISOString().split('T')[0];
    const maxDateString = maxDate.toISOString().split('T')[0];

    dateOfBirthInput.setAttribute('min', minDateString);
    dateOfBirthInput.setAttribute('max', maxDateString);
  }

  // Initialize date constraints and validation
  setDateOfBirthConstraints();

  // Add validation listener
  if (dateOfBirthInput) {
    dateOfBirthInput.addEventListener('change', validateDateOfBirth);
    dateOfBirthInput.addEventListener('input', validateDateOfBirth);
  }
});
</script>
