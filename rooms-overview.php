<?php 
  // PHP session and auth MUST be at the very top before any HTML
  require_once __DIR__ . '/includes/db.php'; 
  requireAuth(['admin','receptionist']); 
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
    <title>Rooms Overview - Core 1 Hotel Management System</title>
    <meta name="title" content="Rooms Overview - Core 1 Hotel Management System" />
    <meta name="description" content="Visual room management system for Core 1. View room status, occupancy, and floor layouts with real-time updates and maintenance tracking." />
    <meta name="keywords" content="room management, hotel rooms, room status, occupancy tracking, floor layout, housekeeping, room maintenance" />
    <meta name="author" content="Core 1 Team" />
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
    <script src="./public/js/hotel-sync.js"></script>
    
    <!-- Security -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
    <style>
      .room-card {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
      }
      .room-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      }
      .room-vacant {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
      }
      .room-occupied {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
      }
      .room-cleaning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
      }
      .room-maintenance {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        color: white;
      }
      .room-event-reserved {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
      }
      .room-event-ongoing {
        background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        color: white;
      }
      .floor-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-left: 4px solid #3b82f6;
      }
      /* Ensure light mode text is dark */
      .floor-header .text-gray-800 {
        color: #1f2937 !important;
      }
      .floor-header .text-gray-600 {
        color: #4b5563 !important;
      }
      /* Dark mode overrides */
      .dark .floor-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-left: 4px solid #60a5fa;
      }
      .dark .floor-header .text-gray-800 {
        color: #e2e8f0 !important;
      }
      .dark .floor-header .text-gray-600 {
        color: #cbd5e1 !important;
      }
      .dark .floor-header .bg-green-100 {
        background-color: #065f46 !important;
        color: #d1fae5 !important;
      }
      .dark .floor-header .bg-red-100 {
        background-color: #7f1d1d !important;
        color: #fecaca !important;
      }
      .dark .floor-header .bg-orange-100 {
        background-color: #78350f !important;
        color: #fed7aa !important;
      }
      .dark .floor-header .bg-gray-100 {
        background-color: #374151 !important;
        color: #e5e7eb !important;
      }
      .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
      }
      .dot-vacant { background: #10b981; }
      .dot-occupied { background: #ef4444; }
      .dot-cleaning { background: #f59e0b; }
      .dot-maintenance { background: #6b7280; }
      .dot-event-reserved { background: #8b5cf6; }
      .dot-event-ongoing { background: #ec4899; }
      
      /* Active legend item styling */
      .legend-item.active {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 6px;
        padding: 4px 8px;
      }
      
    </style>
  </head>
  <body class="min-h-screen bg-background">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Rooms Overview</h1>
        <p class="text-muted-foreground mb-6">Visual layout of rooms by floor — status and type at a glance</p>
        
        <!-- Legend -->
        <div class="flex flex-wrap gap-6 mb-6">
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Vacant')" data-status="Vacant">
            <span class="legend-dot dot-vacant"></span>
            <span class="text-sm font-medium">Vacant</span>
          </div>
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Occupied')" data-status="Occupied">
            <span class="legend-dot dot-occupied"></span>
            <span class="text-sm font-medium">Occupied</span>
          </div>
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Cleaning')" data-status="Cleaning">
            <span class="legend-dot dot-cleaning"></span>
            <span class="text-sm font-medium">For Cleaning</span>
          </div>
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Maintenance')" data-status="Maintenance">
            <span class="legend-dot dot-maintenance"></span>
            <span class="text-sm font-medium">Maintenance</span>
          </div>
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Event Reserved')" data-status="Event Reserved">
            <span class="legend-dot dot-event-reserved"></span>
            <span class="text-sm font-medium">Event Reserved</span>
          </div>
          <div class="legend-item flex items-center cursor-pointer hover:opacity-80 transition-opacity" onclick="filterByStatus('Event Ongoing')" data-status="Event Ongoing">
            <span class="legend-dot dot-event-ongoing"></span>
            <span class="text-sm font-medium">Event Ongoing</span>
          </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-4 mb-2">
          <div>
            <label for="filterStatus" class="block text-xs text-muted-foreground mb-1">Status</label>
            <select id="filterStatus" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All statuses</option>
              <option value="Vacant">Vacant</option>
              <option value="Occupied">Occupied</option>
              <option value="Cleaning">Cleaning</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Reserved">Reserved</option>
              <option value="Event Reserved">Event Reserved</option>
              <option value="Event Ongoing">Event Ongoing</option>
            </select>
          </div>
          <div>
            <label for="filterType" class="block text-xs text-muted-foreground mb-1">Type</label>
            <select id="filterType" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All types</option>
            </select>
          </div>
          <div>
            <label for="filterFloor" class="block text-xs text-muted-foreground mb-1">Floor</label>
            <select id="filterFloor" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All floors</option>
            </select>
          </div>
          <div>
            <label for="filterSearch" class="block text-xs text-muted-foreground mb-1">Search</label>
            <input id="filterSearch" type="text" placeholder="Room or guest" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground placeholder:text-muted-foreground text-sm" />
          </div>
        </div>
      </div>

      <!-- Floors Container -->
      <div id="floorsContainer" class="space-y-8">
        <!-- Floors will be dynamically generated here -->
      </div>
    </main>

    <!-- Room Detail Modal -->
    <div id="roomModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-card text-card-foreground rounded-lg p-6 w-full max-w-md mx-4 shadow-xl border border-border">
        <div class="flex items-center justify-between mb-4">
          <h2 id="modalRoomNumber" class="text-xl font-bold text-foreground">Room 101</h2>
          <button id="closeModal" class="text-muted-foreground hover:text-foreground">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>
        
        <div class="space-y-3">
          <p id="modalRoomType" class="text-sm text-muted-foreground">Type: Single</p>
          <p id="modalRoomStatus" class="text-sm text-muted-foreground">Status: Vacant</p>
          <p id="modalExtra" class="text-sm text-muted-foreground">Extra: —</p>
        </div>
        
        <div class="flex gap-3 pt-6">
          <button id="modalActionPrimary" class="flex-1 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">
            View / Assign
          </button>
          <button id="modalActionSecondary" class="flex-1 rounded-md border border-border px-4 py-2 text-sm hover:bg-muted bg-background text-foreground">
            Close
          </button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    
    <script>
      // Real-time room data from API
      let rooms = [];

      let currentRoomModal = null;

        // UI filters
        const filters = {
          status: 'all',
          type: 'all',
          floor: 'all',
          search: ''
        };

      // Status class mapping (handle both capitalized and lowercase)
      const statusClassMap = {
        "Vacant": "room-vacant",
        "vacant": "room-vacant",
        "Occupied": "room-occupied",
        "occupied": "room-occupied",
        "Cleaning": "room-cleaning",
        "cleaning": "room-cleaning",
        "For Cleaning": "room-cleaning",
        "Maintenance": "room-maintenance",
        "maintenance": "room-maintenance",
        "Reserved": "room-occupied",
        "reserved": "room-occupied",
        "Event Reserved": "room-event-reserved",
        "event reserved": "room-event-reserved",
        "Event Ongoing": "room-event-ongoing",
        "event ongoing": "room-event-ongoing",
        "dirty": "room-cleaning"
      };

      // Status options for dropdown
      const statusOptions = [
        { value: 'Vacant', label: 'Vacant', color: 'green' },
        { value: 'Occupied', label: 'Occupied', color: 'red' },
        { value: 'Cleaning', label: 'Cleaning', color: 'orange' },
        { value: 'Maintenance', label: 'Maintenance', color: 'gray' },
        { value: 'Reserved', label: 'Reserved', color: 'blue' },
        { value: 'Event Reserved', label: 'Event Reserved', color: 'purple' },
        { value: 'Event Ongoing', label: 'Event Ongoing', color: 'pink' }
      ];

      const floorsContainer = document.getElementById("floorsContainer");

      // Initialize
      async function init() {
        await hotelSync.init();
        
        // Listen for updates
        hotelSync.onRoomsUpdate(handleRoomsUpdate);
        
        // Initial render
        rooms = hotelSync.getRooms();
        setupFilters();
        populateFilterOptions();
        renderRooms();
        
        // Setup modal
        setupModal();
      }

      // Handle rooms data update
      function handleRoomsUpdate(updatedRooms) {
        rooms = updatedRooms;
        populateFilterOptions();
        renderRooms();
      }

      // Render all rooms grouped by floor
      function renderRooms() {
        if (!floorsContainer) return;

        floorsContainer.innerHTML = "";
        
        
        const normalizedSearch = (filters.search || '').toString().trim().toLowerCase();

        // Apply filters
        const visibleRooms = rooms.filter(r => {
          // Determine actual status for filtering
          let actualStatus = r.status;
          if (r.status === 'Reserved' && r.guest_name && r.guest_name.startsWith('Event:')) {
            actualStatus = 'Event Reserved';
          } else if (r.status === 'Event Ongoing') {
            actualStatus = 'Event Ongoing';
          }
          
          const statusOk = filters.status === 'all' || (actualStatus === filters.status || (actualStatus || '').toString().toLowerCase() === filters.status.toLowerCase());
          const typeOk = filters.type === 'all' || (r.room_type === filters.type);
          const floorOk = filters.floor === 'all' || ((r.floor_number || 1).toString() === filters.floor.toString());
          if (!normalizedSearch) return statusOk && typeOk && floorOk;
          const hay = `${r.room_number || ''} ${r.guest_name || ''}`.toLowerCase();
          return statusOk && typeOk && floorOk && hay.includes(normalizedSearch);
        });

        if (visibleRooms.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'text-sm text-muted-foreground px-1';
          empty.textContent = 'No rooms match the current filters.';
          floorsContainer.appendChild(empty);
          return;
        }

        // Group by floor
        const grouped = visibleRooms.reduce((acc, room) => {
          const floor = room.floor_number || 1;
          if (!acc[floor]) acc[floor] = [];
          acc[floor].push(room);
          return acc;
        }, {});

        // Sort floors
        const floorNumbers = Object.keys(grouped).map(Number).sort((a, b) => a - b);

        floorNumbers.forEach(floorNum => {
          const floorRooms = grouped[floorNum].sort((a, b) => {
            const numA = parseInt(a.room_number) || 0;
            const numB = parseInt(b.room_number) || 0;
            return numA - numB;
          });

          const floorEl = createFloorSection(floorNum, floorRooms);
          floorsContainer.appendChild(floorEl);
        });
      }

      // Create floor section
      function createFloorSection(floorNum, floorRooms) {
        const section = document.createElement("section");
        section.className = "bg-card rounded-lg border shadow-sm overflow-hidden";

        // Header
        const header = document.createElement("div");
        header.className = "floor-header px-6 py-4 border-b";
        
        const vacantCount = floorRooms.filter(r => r.status === 'Vacant').length;
        const occupiedCount = floorRooms.filter(r => r.status === 'Occupied').length;
        const cleaningCount = floorRooms.filter(r => r.status === 'Cleaning' || r.status === 'For Cleaning').length;
        const maintenanceCount = floorRooms.filter(r => r.status === 'Maintenance').length;
        const eventReservedCount = floorRooms.filter(r => r.status === 'Reserved' && r.guest_name && r.guest_name.startsWith('Event:')).length;
        const eventOngoingCount = floorRooms.filter(r => r.status === 'Event Ongoing').length;

        header.innerHTML = `
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <h3 class="text-lg font-semibold text-gray-800">Floor ${floorNum}</h3>
              <span class="text-sm text-gray-600">${floorRooms.length} rooms</span>
            </div>
            <div class="flex gap-2 text-sm flex-wrap">
              ${vacantCount > 0 ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded-full">${vacantCount} Vacant</span>` : ''}
              ${occupiedCount > 0 ? `<span class="bg-red-100 text-red-800 px-2 py-1 rounded-full">${occupiedCount} Occupied</span>` : ''}
              ${cleaningCount > 0 ? `<span class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full">${cleaningCount} Cleaning</span>` : ''}
              ${maintenanceCount > 0 ? `<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full">${maintenanceCount} Maintenance</span>` : ''}
              ${eventReservedCount > 0 ? `<span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full">${eventReservedCount} Event Reserved</span>` : ''}
              ${eventOngoingCount > 0 ? `<span class="bg-pink-100 text-pink-800 px-2 py-1 rounded-full">${eventOngoingCount} Event Ongoing</span>` : ''}
            </div>
          </div>
        `;

        // Grid
        const grid = document.createElement("div");
        grid.className = "p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4";

        floorRooms.forEach(room => {
          const card = createRoomCard(room);
          grid.appendChild(card);
        });

        section.appendChild(header);
        section.appendChild(grid);

        return section;
      }

      // Create room card
      function createRoomCard(room) {
        const card = document.createElement("div");
        
        // Check if room is reserved for an event or has an ongoing event
        let actualStatus = room.status;
        if (room.status === 'Reserved' && room.guest_name && room.guest_name.startsWith('Event:')) {
          actualStatus = 'Event Reserved';
        } else if (room.status === 'Event Ongoing') {
          actualStatus = 'Event Ongoing';
        }
        
        
        const statusClass = statusClassMap[actualStatus] || "room-vacant";
        card.className = `room-card ${statusClass} rounded-lg p-4 shadow-sm`;
        card.setAttribute('data-room-id', room.id);

        card.innerHTML = `
          <div class="flex flex-col justify-between h-full">
            <div>
              <div class="font-bold text-lg">${room.room_number}</div>
              <div class="text-sm opacity-90">${room.room_type || 'Standard'}</div>
            </div>
            <div class="mt-2">
              <div class="text-xs font-medium">${actualStatus}</div>
              ${room.guest_name ? `<div class="text-xs opacity-75 mt-1 truncate">${room.guest_name}</div>` : ''}
              ${room.maintenance_notes ? `<div class="text-xs opacity-75 mt-1 truncate">${room.maintenance_notes}</div>` : ''}
            </div>
          </div>
        `;

        card.addEventListener("click", () => openRoomModal(room));

        return card;
      }

      // Filter by status from legend click
      function filterByStatus(status) {
        filters.status = status;
        
        // Update the dropdown to match
        const statusEl = document.getElementById('filterStatus');
        if (statusEl) {
          statusEl.value = status;
        }
        
        // Update legend item visual feedback
        document.querySelectorAll('.legend-item').forEach(item => {
          item.classList.remove('active');
        });
        
        const activeItem = document.querySelector(`[data-status="${status}"]`);
        if (activeItem) {
          activeItem.classList.add('active');
        }
        
        renderRooms();
      }

      // Filters setup and helpers
      function setupFilters() {
        const statusEl = document.getElementById('filterStatus');
        const typeEl = document.getElementById('filterType');
        const floorEl = document.getElementById('filterFloor');
        const searchEl = document.getElementById('filterSearch');

        if (statusEl) statusEl.addEventListener('change', () => { 
          filters.status = statusEl.value; 
          
          // Update legend item visual feedback
          document.querySelectorAll('.legend-item').forEach(item => {
            item.classList.remove('active');
          });
          
          const activeItem = document.querySelector(`[data-status="${statusEl.value}"]`);
          if (activeItem) {
            activeItem.classList.add('active');
          }
          
          renderRooms(); 
        });
        if (typeEl) typeEl.addEventListener('change', () => { filters.type = typeEl.value; renderRooms(); });
        if (floorEl) floorEl.addEventListener('change', () => { filters.floor = floorEl.value; renderRooms(); });
        if (searchEl) searchEl.addEventListener('input', () => { filters.search = searchEl.value; renderRooms(); });
      }

      function populateFilterOptions() {
        const typeEl = document.getElementById('filterType');
        const floorEl = document.getElementById('filterFloor');
        if (!typeEl || !floorEl) return;

        const types = Array.from(new Set((rooms || []).map(r => r.room_type).filter(Boolean))).sort();
        const floors = Array.from(new Set((rooms || []).map(r => r.floor_number || 1))).sort((a,b) => (a||0)-(b||0));

        const currentType = filters.type;
        const currentFloor = filters.floor;

        typeEl.innerHTML = '<option value="all">All types</option>' + types.map(t => `<option value="${t}">${t}</option>`).join('');
        floorEl.innerHTML = '<option value="all">All floors</option>' + floors.map(f => `<option value="${f}">Floor ${f}</option>`).join('');

        // Restore selected values if still valid
        if ([...typeEl.options].some(o => o.value === currentType)) typeEl.value = currentType; else typeEl.value = 'all';
        if ([...floorEl.options].some(o => o.value.toString() === currentFloor.toString())) floorEl.value = currentFloor; else floorEl.value = 'all';
      }

      // Setup modal
      function setupModal() {
        const modal = document.getElementById("roomModal");
        const closeModalBtn = document.getElementById("closeModal");
        const modalActionSecondary = document.getElementById("modalActionSecondary");

        if (closeModalBtn) {
          closeModalBtn.addEventListener("click", closeModal);
        }

        if (modalActionSecondary) {
          modalActionSecondary.addEventListener("click", closeModal);
        }

        // Close on outside click
        if (modal) {
          modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
          });
        }

        // Close on Escape
        document.addEventListener("keydown", (e) => {
          if (e.key === "Escape" && modal && !modal.classList.contains("hidden")) {
            closeModal();
          }
        });
      }

      // Open room modal
      async function openRoomModal(room) {
        currentRoomModal = room;
        
        // Determine actual status for display
        let actualStatus = room.status;
        if (room.status === 'Reserved' && room.guest_name && room.guest_name.startsWith('Event:')) {
          actualStatus = 'Event Reserved';
        } else if (room.status === 'Event Ongoing') {
          actualStatus = 'Event Ongoing';
        }
        
        document.getElementById("modalRoomNumber").textContent = `Room ${room.room_number}`;
        document.getElementById("modalRoomType").textContent = `Type: ${room.room_type || 'Standard'}`;
        document.getElementById("modalRoomStatus").textContent = `Status: ${actualStatus}`;
        
        // Check if this room is reserved for an event
        let extraInfo = '—';
        if (room.guest_name && room.guest_name.startsWith('Event:')) {
          extraInfo = room.guest_name;
        } else if (room.guest_name) {
          extraInfo = `Guest: ${room.guest_name}`;
        } else if (room.maintenance_notes) {
          extraInfo = `Note: ${room.maintenance_notes}`;
        }
        document.getElementById("modalExtra").textContent = `Info: ${extraInfo}`;
        
        // Load event information if this room is reserved for an event
        if (room.status === 'Reserved' && room.guest_name && room.guest_name.startsWith('Event:')) {
          await loadRoomEvents(room.id);
        }

        // Create status change options
        const modalContent = document.querySelector('#roomModal .bg-card');
        
        // Remove existing status selector if any
        const existingSelector = document.getElementById('statusSelector');
        if (existingSelector) {
          existingSelector.remove();
        }

        // Add status selector
        const statusSelector = document.createElement('div');
        statusSelector.id = 'statusSelector';
        statusSelector.className = 'mt-4 p-4 bg-muted/30 rounded-lg';
        statusSelector.innerHTML = `
          <label class="block text-sm font-medium mb-2 text-foreground">Change Status:</label>
          <select id="newStatus" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground">
            ${statusOptions.map(opt => `
              <option value="${opt.value}" ${opt.value === actualStatus ? 'selected' : ''}>
                ${opt.label}
              </option>
            `).join('')}
          </select>
          <div class="mt-2">
            <label class="block text-sm font-medium mb-1 text-foreground">Notes:</label>
            <input type="text" id="roomNotes" placeholder="Enter notes..." 
                   class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground placeholder:text-muted-foreground text-sm"
                   value="${room.maintenance_notes || ''}">
          </div>
        `;

        const modalFooter = document.querySelector('#roomModal .flex.gap-3');
        if (modalFooter) {
          modalFooter.parentElement.insertBefore(statusSelector, modalFooter);
        }

        // Update primary action
        const modalActionPrimary = document.getElementById("modalActionPrimary");
        if (modalActionPrimary) {
          modalActionPrimary.textContent = "Update Status";
          modalActionPrimary.onclick = handleStatusUpdate;
        }

        const modal = document.getElementById("roomModal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      }

      // Load events for a specific room
      async function loadRoomEvents(roomId) {
        try {
          const response = await fetch(`event_actions.php?action=get_room_events&room_id=${roomId}`);
          const result = await response.json();
          
          if (result.success && result.data.length > 0) {
            displayRoomEvents(result.data);
          }
        } catch (error) {
          console.error('Error loading room events:', error);
        }
      }

      // Display events in the room modal
      function displayRoomEvents(events) {
        // Remove existing event info if any
        const existingEventInfo = document.getElementById('roomEventInfo');
        if (existingEventInfo) {
          existingEventInfo.remove();
        }

        // Create event info section
        const eventInfo = document.createElement('div');
        eventInfo.id = 'roomEventInfo';
        eventInfo.className = 'mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800';
        eventInfo.innerHTML = `
          <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">📅 Event Bookings</h4>
          ${events.map(event => `
            <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">
              <strong>${event.title}</strong> - ${event.organizer_name}
            </div>
            <div class="text-xs text-blue-600 dark:text-blue-400 mb-2">
              ${new Date(event.start_datetime).toLocaleDateString()} - ${new Date(event.end_datetime).toLocaleDateString()}
            </div>
          `).join('')}
        `;

        // Insert before the status selector
        const modalContent = document.querySelector('#roomModal .bg-card');
        const statusSelector = document.getElementById('statusSelector');
        if (statusSelector) {
          modalContent.insertBefore(eventInfo, statusSelector);
        } else {
          // If no status selector, add before the buttons
          const buttons = document.querySelector('#roomModal .flex.gap-3');
          if (buttons) {
            buttons.parentElement.insertBefore(eventInfo, buttons);
          }
        }
      }

      // Handle status update
      async function handleStatusUpdate() {
        if (!currentRoomModal) return;

        const newStatus = document.getElementById('newStatus')?.value;
        const notes = document.getElementById('roomNotes')?.value;

        // Determine current actual status
        let currentActualStatus = currentRoomModal.status;
        if (currentRoomModal.status === 'Reserved' && currentRoomModal.guest_name && currentRoomModal.guest_name.startsWith('Event:')) {
          currentActualStatus = 'Event Reserved';
        } else if (currentRoomModal.status === 'Event Ongoing') {
          currentActualStatus = 'Event Ongoing';
        }
        
        if (newStatus && newStatus !== currentActualStatus) {
          // Handle special case: changing from Event Reserved
          let statusToUpdate = newStatus;
          let guestNameToUpdate = currentRoomModal.guest_name;
          let notesToUpdate = notes;
          
          if ((currentActualStatus === 'Event Reserved' || currentActualStatus === 'Event Ongoing') && newStatus !== 'Event Reserved' && newStatus !== 'Event Ongoing') {
            // If changing from Event Reserved/Event Ongoing to something else, clear event info
            guestNameToUpdate = null;
            notesToUpdate = notes || '';
          } else if (newStatus === 'Event Reserved' && currentActualStatus !== 'Event Reserved') {
            // If changing to Event Reserved, this shouldn't happen through normal flow
            // But if it does, we'll keep the current guest_name
            statusToUpdate = 'Reserved'; // Store as Reserved in database
          } else if (newStatus === 'Event Ongoing' && currentActualStatus !== 'Event Ongoing') {
            // If changing to Event Ongoing, this shouldn't happen through normal flow
            // But if it does, we'll keep the current guest_name
            statusToUpdate = 'Event Ongoing'; // Store as Event Ongoing in database
          }
          
          // Show loading state
          const updateBtn = document.getElementById('modalActionPrimary');
          const originalText = updateBtn.textContent;
          updateBtn.textContent = 'Updating...';
          updateBtn.disabled = true;

          const success = await hotelSync.updateRoom(
            currentRoomModal.id,
            statusToUpdate,
            guestNameToUpdate,
            notesToUpdate
          );

          if (success) {
            // Force immediate refresh of room data
            console.log('Status update successful, refreshing data...');
            await new Promise(resolve => setTimeout(resolve, 500)); // Wait a bit for DB to commit
            await hotelSync.init(); // Force reload from API
            console.log('Data refreshed, rooms:', hotelSync.getRooms());
            closeModal();
          } else {
            console.error('Status update failed');
            // Restore button
            updateBtn.textContent = originalText;
            updateBtn.disabled = false;
          }
        } else {
          closeModal();
        }
      }

      // Close modal
      function closeModal() {
        const modal = document.getElementById("roomModal");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        currentRoomModal = null;

        // Remove status selector
        const existingSelector = document.getElementById('statusSelector');
        if (existingSelector) {
          existingSelector.remove();
        }
      }

      // Start when DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
    </script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
  </body>
</html>
