<?php
require_once __DIR__ . '/includes/security.php';
?>

<!-- Reservation Modal -->
<div id="reservationModal" class="fixed inset-0 modal-overlay z-50 hidden">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="modal-content rounded-xl w-full max-w-7xl h-[95vh] overflow-hidden flex flex-col bg-card shadow-2xl border border-border">
      <!-- Enhanced Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-border flex-shrink-0 bg-gradient-to-r from-primary/5 to-primary/10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-2xl font-bold text-card-foreground">New Reservation</h2>
            <p class="text-sm text-muted-foreground">Create a new hotel reservation for your guest</p>
          </div>
        </div>
        <button id="closeModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-lg">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Modal Body - Scrollable Content -->
      <div class="flex-1 overflow-y-auto bg-background">
        <form id="reservationForm" class="p-6">
          <!-- Two Column Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Guest Information -->
            <div class="space-y-6">
              <div class="bg-card rounded-lg p-6 border border-border shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <h3 class="text-xl font-semibold text-card-foreground">Guest Information</h3>
                </div>

              <!-- Search Section -->
                <div class="mb-6">
                  <label class="block text-sm font-semibold text-card-foreground mb-2">Search Existing Guests</label>
                <div class="relative">
                    <input type="text" id="guestSearch" placeholder="Search guests by name, email, or phone..." class="w-full px-4 py-3 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    <div id="guestSearchResults" class="search-results absolute z-10 w-full mt-2 rounded-lg max-h-60 overflow-y-auto hidden bg-card border border-border shadow-lg">
                      <div id="guestsLoading" class="p-4 text-sm text-muted-foreground flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Loading guests...
                  </div>
                      <div id="noGuestsFound" class="p-4 text-sm text-muted-foreground hidden">No guests found</div>
                </div>
                  </div>
                  <div id="selectedGuestInfo" class="mt-3 p-4 bg-primary/10 border border-primary/20 rounded-lg hidden">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                      <p class="text-sm text-primary font-medium">Selected: <span id="selectedGuestName" class="font-semibold"></span></p>
                    </div>
                  <input type="hidden" id="guest_id" name="guest_id">
                </div>
                  <div id="guestsFetchStatus" class="mt-2 text-xs text-muted-foreground flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Guests: <span id="guestsStatus" class="font-medium">Not loaded</span>
                  </div>
              </div>

              <!-- New Guest Form -->
              <div id="newGuestSection">
                  <div class="mb-4">
                    <div class="flex items-center gap-2 mb-3">
                      <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                </div>
                      <label class="text-sm font-semibold text-card-foreground">Or Create New Guest</label>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 gap-4">
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                    </div>
                  </div>
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Email *</label>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Phone</label>
                        <input type="tel" id="phone" name="phone" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    </div>
                  </div>
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Address</label>
                        <input type="text" id="address" name="address" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">City</label>
                        <input type="text" id="city" name="city" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    </div>
                  </div>
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Country</label>
                        <input type="text" id="country" name="country" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Nationality</label>
                        <input type="text" id="nationality" name="nationality" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                    </div>
                  </div>
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">ID Type</label>
                        <select id="id_type" name="id_type" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors">
                        <option value="National ID">National ID</option>
                        <option value="Passport">Passport</option>
                        <option value="Driver License">Driver License</option>
                      </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">ID Number *</label>
                        <input type="text" id="id_number" name="id_number" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                    </div>
                  </div>
                  <div>
                      <label class="block text-sm font-medium text-card-foreground mb-2">Date of Birth *</label>
                      <input type="date" id="date_of_birth" name="date_of_birth" class="w-full px-3 py-2.5 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                      <div id="dateOfBirthError" class="mt-2 text-sm text-destructive hidden"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Room & Reservation Info -->
            <div class="space-y-6">
              <!-- Room Selection Section -->
              <div class="bg-card rounded-lg p-6 border border-border shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                  </div>
                  <h3 class="text-xl font-semibold text-card-foreground">Room Selection</h3>
                </div>
                <!-- Room Selection Tabs -->
                <div class="flex space-x-1 mb-4 bg-muted p-1 rounded-lg">
                  <button id="hotelRoomViewTab" class="flex-1 py-2 px-3 text-sm font-medium rounded-md bg-primary text-primary-foreground transition-colors">
                    Visual Selection
                  </button>
                  <button id="hotelRoomListTab" class="flex-1 py-2 px-3 text-sm font-medium rounded-md text-muted-foreground hover:text-foreground transition-colors">
                    List View
                  </button>
                </div>
                
                <!-- Visual Room Grid -->
                <div id="hotelRoomVisualGrid" class="space-y-4">
                  <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                    <p class="text-sm text-muted-foreground">Loading rooms...</p>
                  </div>
                </div>
                
                <!-- List View (Hidden by default) -->
                <div id="hotelRoomListView" class="hidden">
                  <select id="room_id" name="room_id" class="w-full px-4 py-3 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required>
                      <option value="">Loading rooms...</option>
                    </select>
                  </div>
                
                <!-- Room Preview -->
                <div class="mt-4">
                  <label class="block text-sm font-semibold text-card-foreground mb-2">Room Preview</label>
                  <div id="selectedRoomInfo" class="p-4 bg-muted/50 rounded-lg min-h-[60px] flex items-center text-sm border border-border">
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      <span>Select a room to see details</span>
                    </div>
                  </div>
                </div>
                <div id="roomsFetchStatus" class="mt-3 text-xs text-muted-foreground flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Rooms: <span id="roomsStatus" class="font-medium">Not loaded</span>
                  <button id="retryRoomsBtn" onclick="loadRooms()" class="ml-2 px-2 py-1 text-xs bg-primary/10 text-primary rounded hover:bg-primary/20 transition-colors hidden">
                    Retry
                  </button>
                </div>
              </div>

              <!-- Date and Time Section -->
              <div class="bg-card rounded-lg p-6 border border-border shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                  <h3 class="text-xl font-semibold text-card-foreground">Reservation Dates</h3>
                </div>
                <div class="grid grid-cols-1 gap-4">
              <div>
                    <label class="block text-sm font-semibold text-card-foreground mb-2">Check-in Date & Time *</label>
                    <input type="datetime-local" id="check_in_date" name="check_in_date" class="w-full px-4 py-3 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required min="">
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-card-foreground mb-2">Check-out Date & Time *</label>
                    <input type="datetime-local" id="check_out_date" name="check_out_date" class="w-full px-4 py-3 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-background text-foreground transition-colors" required min="">
                  </div>
                </div>
                
                <!-- Enhanced Action Buttons -->
                <div class="mt-6 flex justify-end gap-4">
                  <button type="button" id="cancelBtn" class="px-6 py-3 text-sm font-semibold rounded-lg border border-border hover:bg-muted transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                  </button>
                  <button type="submit" id="submitBtn" class="px-6 py-3 text-sm font-semibold rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2 shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Create Reservation
                  </button>
                </div>
              </div>

              <!-- Status Section (hidden: always pending on create) -->
              <div class="hidden">
                <h3 class="text-lg font-medium mb-2 border-b border-border pb-2 text-card-foreground">Reservation Status</h3>
                <select id="status" name="status" class="w-full px-3 py-1.5 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                  <option value="Pending" selected>Pending</option>
                  <option value="Checked In">Checked In</option>
                  <option value="Checked Out">Checked Out</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>

              <!-- Hidden Payment Status Field -->
              <input type="hidden" id="payment_status" name="payment_status" value="PENDING">
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
      div.className = 'p-3 hover:bg-muted cursor-pointer border-b border-border last:border-b-0 transition-colors';
      
      // Properly format guest name from first_name and last_name
      const guestName = guest.first_name && guest.last_name 
        ? `${guest.first_name} ${guest.last_name}` 
        : (guest.name || 'Unknown');
        
      div.innerHTML = `
        <div class="font-medium text-card-foreground">${guestName}</div>
        <div class="text-sm text-muted-foreground">${guest.email || ''} ${guest.phone || ''}</div>
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
    console.log('Loading rooms for hotel reservation...'); // Debug log
    
    try {
      roomsStatus.textContent = 'Loading...';
      
      // Try multiple endpoints to see which one works
      const endpoints = [
        'api/rooms.php',
        './api/rooms.php',
        '/api/rooms',
        'http://localhost/hmscore1last1/api/rooms.php',
        'http://localhost/hmscore1last1/api/rooms'
      ];
      
      let response = null;
      let workingEndpoint = null;
      
      for (const endpoint of endpoints) {
        try {
          console.log(`Trying endpoint: ${endpoint}`);
          response = await fetch(endpoint, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
            }
          });
          
          console.log(`Endpoint ${endpoint} response status:`, response.status);
          
          if (response.ok) {
            workingEndpoint = endpoint;
            break;
          }
        } catch (err) {
          console.log(`Endpoint ${endpoint} failed:`, err.message);
        }
      }
      
      if (!response || !response.ok) {
        console.error('All endpoints failed');
        roomsStatus.textContent = 'Error: All API endpoints failed';
        rooms = [];
        showRetryButton();
        return;
      }
      
      console.log(`Using working endpoint: ${workingEndpoint}`);
      const data = await response.json();
      console.log('Rooms API response data:', data); // Debug log

      if (data.data && Array.isArray(data.data)) {
        rooms = data.data;
        console.log('Updating room select with', rooms.length, 'rooms'); // Debug log
        console.log('Calling populateRoomSelect...'); // Debug log
        populateRoomSelect(rooms);
        console.log('Calling updateHotelRoomVisualGrid...'); // Debug log
        updateHotelRoomVisualGrid(rooms);
        console.log('populateRoomSelect completed'); // Debug log
        roomsStatus.textContent = `Loaded (${rooms.length} rooms)`;
        hideRetryButton();
      } else {
        console.error('Invalid rooms data format:', data);
        rooms = [];
        roomsStatus.textContent = 'Error: Invalid data format';
        showRetryButton();
      }
    } catch (error) {
      console.error('Error loading rooms:', error);
      rooms = [];
      roomsStatus.textContent = `Error: ${error.message}`;
      showRetryButton();
    }
  }

  // Show/hide retry button
  function showRetryButton() {
    const retryBtn = document.getElementById('retryRoomsBtn');
    if (retryBtn) retryBtn.classList.remove('hidden');
  }

  function hideRetryButton() {
    const retryBtn = document.getElementById('retryRoomsBtn');
    if (retryBtn) retryBtn.classList.add('hidden');
  }

  function populateRoomSelect(rooms) {
    console.log('populateRoomSelect called with', rooms.length, 'rooms'); // Debug log
    console.log('Room statuses:', rooms.map(r => r.status)); // Debug log
    
    if (!roomSelect) {
      console.error('roomSelect element not found!');
      return;
    }
    
    console.log('roomSelect element found:', roomSelect); // Debug log
    roomSelect.innerHTML = '<option value="">Select a room...</option>';

    // Filter only VACANT rooms and sort by room number
    const vacantRooms = rooms
      .filter(room => room.status === 'Vacant' || room.status === 'VACANT')
      .sort((a, b) => {
        const aNum = parseInt(a.room_number);
        const bNum = parseInt(b.room_number);
        return aNum - bNum;
      });

    console.log('Found', vacantRooms.length, 'vacant rooms'); // Debug log
    console.log('Vacant rooms:', vacantRooms); // Debug log

    vacantRooms.forEach(room => {
      const option = document.createElement('option');
      option.value = room.id;
      option.textContent = `${room.room_number} - ${room.room_type} (₱${room.rate || 0})`;
      roomSelect.appendChild(option);
    });

    if (vacantRooms.length === 0) {
      console.log('No vacant rooms found - showing all rooms for debugging'); // Debug log
      // Show all rooms for debugging
      rooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = `${room.room_number} - ${room.room_type} (${room.status}) (₱${room.rate || 0})`;
        roomSelect.appendChild(option);
      });
      
      if (rooms.length === 0) {
        roomSelect.innerHTML = '<option value="">No rooms available</option>';
      }
    }
  }

  // Update hotel room visual grid
  function updateHotelRoomVisualGrid(rooms) {
    console.log('🏨 updateHotelRoomVisualGrid called with', rooms.length, 'rooms');
    
    const gridContainer = document.getElementById('hotelRoomVisualGrid');
    if (!gridContainer) {
      console.error('💥 hotelRoomVisualGrid element not found!');
      return;
    }
    
    console.log('✅ hotelRoomVisualGrid element found:', gridContainer);

    // Group rooms by floor
    const roomsByFloor = {};
    rooms.forEach(room => {
      const floor = room.floor_number || 1;
      if (!roomsByFloor[floor]) {
        roomsByFloor[floor] = [];
      }
      roomsByFloor[floor].push(room);
    });

    console.log('🏢 Rooms grouped by floor:', roomsByFloor);

    // Sort floors
    const sortedFloors = Object.keys(roomsByFloor).sort((a, b) => parseInt(a) - parseInt(b));
    console.log('📋 Sorted floors:', sortedFloors);

    gridContainer.innerHTML = '';

    if (sortedFloors.length === 0) {
      console.log('⚠️ No floors found, showing empty message');
      gridContainer.innerHTML = `
        <div class="text-center py-8">
          <p class="text-sm text-muted-foreground">No rooms found</p>
        </div>
      `;
      return;
    }

    sortedFloors.forEach(floorNum => {
      const floorRooms = roomsByFloor[floorNum];
      console.log(`🏢 Processing floor ${floorNum} with ${floorRooms.length} rooms`);
      
      // Create floor section
      const floorSection = document.createElement('div');
      floorSection.className = 'mb-6';
      
      // Floor header
      const floorHeader = document.createElement('div');
      floorHeader.className = 'flex items-center justify-between mb-3';
      floorHeader.innerHTML = `
        <h5 class="text-sm font-semibold text-card-foreground">Floor ${floorNum}</h5>
        <span class="text-xs text-muted-foreground">${floorRooms.length} rooms</span>
      `;
      
      // Room grid
      const roomGrid = document.createElement('div');
      roomGrid.className = 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2';
      
      floorRooms.forEach(room => {
        console.log(`🏨 Creating room card for ${room.room_number} (${room.status})`);
        const roomCard = createHotelVisualRoomCard(room);
        roomGrid.appendChild(roomCard);
      });
      
      floorSection.appendChild(floorHeader);
      floorSection.appendChild(roomGrid);
      gridContainer.appendChild(floorSection);
    });
    
    console.log('✅ Hotel room visual grid updated successfully');
  }

  // Create visual room card for hotel reservation
  function createHotelVisualRoomCard(room) {
    const card = document.createElement('div');
    card.className = 'relative cursor-pointer transition-all duration-200 hover:scale-105';
    
    // Determine room status and styling
    let statusClass = '';
    let statusColor = '';
    let isSelectable = false;
    
    switch (room.status) {
      case 'Vacant':
        statusClass = 'bg-green-500 hover:bg-green-600';
        statusColor = 'text-white';
        isSelectable = true;
        break;
      case 'Cleaning':
        statusClass = 'bg-orange-500 hover:bg-orange-600';
        statusColor = 'text-white';
        isSelectable = true;
        break;
      case 'Occupied':
        statusClass = 'bg-red-500';
        statusColor = 'text-white';
        isSelectable = false;
        break;
      case 'Reserved':
        statusClass = 'bg-purple-500';
        statusColor = 'text-white';
        isSelectable = false;
        break;
      case 'Event Reserved':
        statusClass = 'bg-purple-500';
        statusColor = 'text-white';
        isSelectable = false;
        break;
      case 'Event Ongoing':
        statusClass = 'bg-pink-500';
        statusColor = 'text-white';
        isSelectable = false;
        break;
      case 'Maintenance':
        statusClass = 'bg-gray-500';
        statusColor = 'text-white';
        isSelectable = false;
        break;
      default:
        statusClass = 'bg-gray-400';
        statusColor = 'text-white';
        isSelectable = false;
    }
    
    card.innerHTML = `
      <div class="w-full h-20 ${statusClass} rounded-lg flex flex-col items-center justify-center p-2 ${!isSelectable ? 'opacity-60 cursor-not-allowed' : ''}" 
           data-room-id="${room.id}" 
           data-room-number="${room.room_number}"
           data-room-type="${room.room_type}"
           data-room-rate="${room.rate}"
           data-room-floor="${room.floor_number}"
           data-room-status="${room.status}">
        <div class="text-sm font-bold ${statusColor}">${room.room_number}</div>
        <div class="text-xs ${statusColor} opacity-90">${room.room_type || 'Standard'}</div>
        <div class="text-xs ${statusColor} opacity-75">₱${parseFloat(room.rate || 0).toLocaleString()}</div>
      </div>
    `;
    
    // Add click handler for selectable rooms
    if (isSelectable) {
      card.addEventListener('click', function() {
        selectHotelRoom(room);
      });
    }
    
    return card;
  }

  // Select hotel room
  function selectHotelRoom(room) {
    console.log('🏨 Hotel room selected:', room);
    
    // Update the hidden select element
    if (roomSelect) {
      roomSelect.value = room.id;
    }
    
    // Update room preview
    if (selectedRoomInfo) {
      selectedRoomInfo.innerHTML = `
        <div>
          <div class="font-medium">${room.room_number} - ${room.room_type}</div>
          <div class="text-sm text-gray-500">Rate: ₱${room.rate || 0} | Floor: ${room.floor_number}</div>
          ${room.amenities ? `<div class="text-sm text-gray-500 mt-1">Amenities: ${room.amenities}</div>` : ''}
        </div>
      `;
    }
    
    // Update visual selection
    updateHotelRoomSelection(room.id);
  }

  // Update hotel room selection visual
  function updateHotelRoomSelection(selectedRoomId) {
    const roomCards = document.querySelectorAll('#hotelRoomVisualGrid [data-room-id]');
    
    roomCards.forEach(card => {
      const roomId = card.getAttribute('data-room-id');
      const isSelected = roomId == selectedRoomId;
      
      if (isSelected) {
        card.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
      } else {
        card.classList.remove('ring-2', 'ring-primary', 'ring-offset-2');
      }
    });
  }

  // Debug function
  function debugRooms() {
    console.log('=== HOTEL RESERVATION DEBUG ===');
    console.log('roomSelect element:', roomSelect);
    console.log('rooms array:', rooms);
    console.log('rooms.length:', rooms ? rooms.length : 'undefined');
    console.log('roomSelect.innerHTML:', roomSelect ? roomSelect.innerHTML : 'roomSelect not found');
    console.log('roomSelect.options.length:', roomSelect ? roomSelect.options.length : 'roomSelect not found');
    
    if (rooms && rooms.length > 0) {
      console.log('Room statuses:', rooms.map(r => r.status));
      const vacantRooms = rooms.filter(room => room.status === 'Vacant' || room.status === 'VACANT');
      console.log('Vacant rooms:', vacantRooms.length);
      console.log('Vacant rooms details:', vacantRooms);
    }
    
    // Try to manually populate
    if (rooms && rooms.length > 0) {
      console.log('Manually populating room select...');
      populateRoomSelect(rooms);
    }
  }

  // Make functions globally available for debugging
  window.loadRooms = loadRooms;
  window.debugRooms = debugRooms;

  // Setup hotel room selection tabs
  function setupHotelRoomSelectionTabs() {
    const visualTab = document.getElementById('hotelRoomViewTab');
    const listTab = document.getElementById('hotelRoomListTab');
    const visualGrid = document.getElementById('hotelRoomVisualGrid');
    const listView = document.getElementById('hotelRoomListView');

    if (visualTab && listTab && visualGrid && listView) {
      // Visual tab click
      visualTab.addEventListener('click', function() {
        visualTab.classList.add('bg-primary', 'text-primary-foreground');
        visualTab.classList.remove('text-muted-foreground');
        listTab.classList.remove('bg-primary', 'text-primary-foreground');
        listTab.classList.add('text-muted-foreground');
        visualGrid.classList.remove('hidden');
        listView.classList.add('hidden');
      });

      // List tab click
      listTab.addEventListener('click', function() {
        listTab.classList.add('bg-primary', 'text-primary-foreground');
        listTab.classList.remove('text-muted-foreground');
        visualTab.classList.remove('bg-primary', 'text-primary-foreground');
        visualTab.classList.add('text-muted-foreground');
        listView.classList.remove('hidden');
        visualGrid.classList.add('hidden');
      });
    }
  }

  // Initialize hotel room selection tabs
  setupHotelRoomSelectionTabs();

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
        
        // Update visual selection if visual tab is active
        updateHotelRoomSelection(selectedRoomId);
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

<style>
/* Dark mode improvements for guest search */
#guestSearch {
  background-color: hsl(var(--background)) !important;
  color: hsl(var(--foreground)) !important;
  border-color: hsl(var(--border)) !important;
}

#guestSearch:focus {
  background-color: hsl(var(--background)) !important;
  color: hsl(var(--foreground)) !important;
  border-color: hsl(var(--primary)) !important;
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2) !important;
}

#guestSearch::placeholder {
  color: hsl(var(--muted-foreground)) !important;
}

/* Search results dropdown dark mode */
#guestSearchResults {
  background-color: hsl(var(--card)) !important;
  border-color: hsl(var(--border)) !important;
  color: hsl(var(--card-foreground)) !important;
}

#guestSearchResults .p-3 {
  background-color: hsl(var(--card)) !important;
  color: hsl(var(--card-foreground)) !important;
}

#guestSearchResults .p-3:hover {
  background-color: hsl(var(--muted)) !important;
}

#guestSearchResults .font-medium {
  color: hsl(var(--card-foreground)) !important;
}

#guestSearchResults .text-sm {
  color: hsl(var(--muted-foreground)) !important;
}

/* Ensure proper contrast in dark mode */
@media (prefers-color-scheme: dark) {
  #guestSearch {
    background-color: hsl(var(--background)) !important;
    color: hsl(var(--foreground)) !important;
  }
  
  #guestSearchResults {
    background-color: hsl(var(--card)) !important;
    border-color: hsl(var(--border)) !important;
  }
  
  #guestSearchResults .p-3 {
    background-color: hsl(var(--card)) !important;
    color: hsl(var(--card-foreground)) !important;
  }
  
  #guestSearchResults .p-3:hover {
    background-color: hsl(var(--muted)) !important;
  }
}
</style>

<style>
/* Global Scrollbar Styling for Modal - Consistent Blue Theme */
::-webkit-scrollbar {
  width: 12px;
  height: 12px;
}

::-webkit-scrollbar-track {
  background: hsl(var(--muted));
  border-radius: 6px;
}

::-webkit-scrollbar-thumb {
  background: #3b82f6; /* Blue-500 */
  border-radius: 6px;
  border: 2px solid hsl(var(--muted));
  transition: background 0.2s ease;
}

::-webkit-scrollbar-thumb:hover {
  background: #2563eb; /* Blue-600 */
}

::-webkit-scrollbar-thumb:active {
  background: #1d4ed8; /* Blue-700 */
}

::-webkit-scrollbar-corner {
  background: hsl(var(--muted));
}

/* Scrollbar buttons (arrows) */
::-webkit-scrollbar-button {
  background: #60a5fa; /* Light blue */
  border-radius: 6px;
  height: 12px;
  width: 12px;
}

::-webkit-scrollbar-button:hover {
  background: #93c5fd; /* Lighter blue */
}

::-webkit-scrollbar-button:vertical:start:decrement {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 15l7-7 7 7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 8px;
}

::-webkit-scrollbar-button:vertical:end:increment {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 8px;
}

::-webkit-scrollbar-button:horizontal:start:decrement {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 8px;
}

::-webkit-scrollbar-button:horizontal:end:increment {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ffffff'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 8px;
}

/* Firefox scrollbar styling */
* {
  scrollbar-width: thin;
  scrollbar-color: #3b82f6 hsl(var(--muted));
}

/* Modal specific scrollbar styling */
.modal-content::-webkit-scrollbar,
.modal-content .flex-1::-webkit-scrollbar,
.overflow-y-auto::-webkit-scrollbar {
  width: 12px;
  height: 12px;
}

.modal-content::-webkit-scrollbar-track,
.modal-content .flex-1::-webkit-scrollbar-track,
.overflow-y-auto::-webkit-scrollbar-track {
  background: hsl(var(--muted));
  border-radius: 6px;
}

.modal-content::-webkit-scrollbar-thumb,
.modal-content .flex-1::-webkit-scrollbar-thumb,
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #3b82f6;
  border-radius: 6px;
  border: 2px solid hsl(var(--muted));
}

.modal-content::-webkit-scrollbar-thumb:hover,
.modal-content .flex-1::-webkit-scrollbar-thumb:hover,
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #2563eb;
}

/* Search results dropdown scrollbar */
.search-results::-webkit-scrollbar {
  width: 8px;
}

.search-results::-webkit-scrollbar-track {
  background: hsl(var(--muted));
  border-radius: 4px;
}

.search-results::-webkit-scrollbar-thumb {
  background: #3b82f6;
  border-radius: 4px;
  border: 1px solid hsl(var(--muted));
}

.search-results::-webkit-scrollbar-thumb:hover {
  background: #2563eb;
}
</style>
