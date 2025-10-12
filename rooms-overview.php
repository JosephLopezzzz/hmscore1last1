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
    <title>Rooms Overview - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Rooms Overview - Inn Nexus Hotel Management System" />
    <meta name="description" content="Visual room management system for Inn Nexus. View room status, occupancy, and floor layouts with real-time updates and maintenance tracking." />
    <meta name="keywords" content="room management, hotel rooms, room status, occupancy tracking, floor layout, housekeeping, room maintenance" />
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
      .floor-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-left: 4px solid #3b82f6;
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
    </style>
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin','receptionist']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Rooms Overview</h1>
        <p class="text-muted-foreground mb-6">Visual layout of rooms by floor — status and type at a glance</p>
        
        <!-- Legend -->
        <div class="flex flex-wrap gap-6 mb-6">
          <div class="flex items-center">
            <span class="legend-dot dot-vacant"></span>
            <span class="text-sm font-medium">Vacant</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-occupied"></span>
            <span class="text-sm font-medium">Occupied</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-cleaning"></span>
            <span class="text-sm font-medium">For Cleaning</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-maintenance"></span>
            <span class="text-sm font-medium">Maintenance</span>
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
      <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
          <h2 id="modalRoomNumber" class="text-xl font-bold">Room 101</h2>
          <button id="closeModal" class="text-gray-500 hover:text-gray-700">
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
          <button id="modalActionSecondary" class="flex-1 rounded-md border px-4 py-2 text-sm hover:bg-muted">
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
        "dirty": "room-cleaning"
      };

      // Status options for dropdown
      const statusOptions = [
        { value: 'Vacant', label: 'Vacant', color: 'green' },
        { value: 'Occupied', label: 'Occupied', color: 'red' },
        { value: 'Cleaning', label: 'Cleaning', color: 'orange' },
        { value: 'Maintenance', label: 'Maintenance', color: 'gray' },
        { value: 'Reserved', label: 'Reserved', color: 'blue' }
      ];

      const floorsContainer = document.getElementById("floorsContainer");

      // Initialize
      async function init() {
        await hotelSync.init();
        
        // Listen for updates
        hotelSync.onRoomsUpdate(handleRoomsUpdate);
        
        // Initial render
        rooms = hotelSync.getRooms();
        renderRooms();
        
        // Setup modal
        setupModal();
      }

      // Handle rooms data update
      function handleRoomsUpdate(updatedRooms) {
        rooms = updatedRooms;
        renderRooms();
      }

      // Render all rooms grouped by floor
      function renderRooms() {
        if (!floorsContainer) return;

        floorsContainer.innerHTML = "";
        
        // Group by floor
        const grouped = rooms.reduce((acc, room) => {
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
        const statusClass = statusClassMap[room.status] || "room-vacant";
        card.className = `room-card ${statusClass} rounded-lg p-4 shadow-sm`;
        card.setAttribute('data-room-id', room.id);

        card.innerHTML = `
          <div class="flex flex-col justify-between h-full">
            <div>
              <div class="font-bold text-lg">${room.room_number}</div>
              <div class="text-sm opacity-90">${room.room_type || 'Standard'}</div>
            </div>
            <div class="mt-2">
              <div class="text-xs font-medium">${room.status}</div>
              ${room.guest_name ? `<div class="text-xs opacity-75 mt-1 truncate">Guest: ${room.guest_name}</div>` : ''}
              ${room.maintenance_notes ? `<div class="text-xs opacity-75 mt-1 truncate">${room.maintenance_notes}</div>` : ''}
            </div>
          </div>
        `;

        card.addEventListener("click", () => openRoomModal(room));

        return card;
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
      function openRoomModal(room) {
        currentRoomModal = room;
        
        document.getElementById("modalRoomNumber").textContent = `Room ${room.room_number}`;
        document.getElementById("modalRoomType").textContent = `Type: ${room.room_type || 'Standard'}`;
        document.getElementById("modalRoomStatus").textContent = `Status: ${room.status}`;
        
        const extraInfo = room.guest_name ? `Guest: ${room.guest_name}` : 
                         room.maintenance_notes ? `Note: ${room.maintenance_notes}` : '—';
        document.getElementById("modalExtra").textContent = `Info: ${extraInfo}`;

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
          <label class="block text-sm font-medium mb-2">Change Status:</label>
          <select id="newStatus" class="w-full px-3 py-2 rounded-md border bg-background">
            ${statusOptions.map(opt => `
              <option value="${opt.value}" ${opt.value === room.status ? 'selected' : ''}>
                ${opt.label}
              </option>
            `).join('')}
          </select>
          <div class="mt-2">
            <label class="block text-sm font-medium mb-1">Notes:</label>
            <input type="text" id="roomNotes" placeholder="Enter notes..." 
                   class="w-full px-3 py-2 rounded-md border bg-background text-sm"
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

      // Handle status update
      async function handleStatusUpdate() {
        if (!currentRoomModal) return;

        const newStatus = document.getElementById('newStatus')?.value;
        const notes = document.getElementById('roomNotes')?.value;

        if (newStatus && newStatus !== currentRoomModal.status) {
          const success = await hotelSync.updateRoom(
            currentRoomModal.id,
            newStatus,
            currentRoomModal.guest_name,
            notes
          );

          if (success) {
            closeModal();
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
  </body>
</html>
