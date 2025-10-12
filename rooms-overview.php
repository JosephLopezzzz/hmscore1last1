<!doctype html>
<html lang="en">
  <head>
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
      // Sample room data (in a real app, this would come from the server)
      const rooms = [
        // Floor 1
        { floor: 1, number: 101, type: "Single", status: "Occupied", extra: "Guest: John Doe" },
        { floor: 1, number: 102, type: "Double", status: "Vacant", extra: "Sea view" },
        { floor: 1, number: 103, type: "Deluxe", status: "For Cleaning", extra: "Cleaning in progress" },
        { floor: 1, number: 104, type: "Single", status: "Vacant", extra: "Ready" },
        { floor: 1, number: 105, type: "Suite", status: "Maintenance", extra: "AC repair" },

        // Floor 2
        { floor: 2, number: 201, type: "Suite", status: "Vacant", extra: "Ready" },
        { floor: 2, number: 202, type: "Double", status: "Occupied", extra: "Guest: Maria L." },
        { floor: 2, number: 203, type: "Single", status: "Vacant", extra: "Promo rate" },
        { floor: 2, number: 204, type: "Deluxe", status: "For Cleaning", extra: "Towel refill" },
        { floor: 2, number: 205, type: "Double", status: "Occupied", extra: "Guest: Alex T." },

        // Floor 3
        { floor: 3, number: 301, type: "Single", status: "For Cleaning", extra: "Towel refill" },
        { floor: 3, number: 302, type: "Deluxe", status: "Occupied", extra: "Guest: Alex T." },
        { floor: 3, number: 303, type: "Suite", status: "Vacant", extra: "VIP ready" },
        { floor: 3, number: 304, type: "Single", status: "Maintenance", extra: "Plumbing" },
        { floor: 3, number: 305, type: "Double", status: "Vacant", extra: "Ready" }
      ];

      // Status class mapping
      const statusClassMap = {
        "Vacant": "room-vacant",
        "Occupied": "room-occupied",
        "For Cleaning": "room-cleaning",
        "Maintenance": "room-maintenance"
      };

      const floorsContainer = document.getElementById("floorsContainer");

      // Group rooms by floor
      const grouped = rooms.reduce((acc, r) => {
        if (!acc[r.floor]) acc[r.floor] = [];
        acc[r.floor].push(r);
        return acc;
      }, {});

      // Sort floors ascending
      const floorNumbers = Object.keys(grouped).map(Number).sort((a,b) => a - b);

      // Render floors
      function renderFloors() {
        floorsContainer.innerHTML = "";
        
        floorNumbers.forEach(floorNum => {
          const floorRooms = grouped[floorNum].sort((a,b) => a.number - b.number);

          // Floor section
          const floorEl = document.createElement("section");
          floorEl.className = "bg-card rounded-lg border shadow-sm overflow-hidden";

          // Floor header
          const header = document.createElement("div");
          header.className = "floor-header px-6 py-4 border-b";
          header.innerHTML = `
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-gray-800">Floor ${floorNum}</h3>
                <span class="text-sm text-gray-600">${floorRooms.length} rooms</span>
              </div>
              <div class="flex gap-2 text-sm">
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full">${floorRooms.filter(r => r.status === 'Vacant').length} Vacant</span>
                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full">${floorRooms.filter(r => r.status === 'Occupied').length} Occupied</span>
              </div>
            </div>
          `;

          floorEl.appendChild(header);

          // Rooms grid
          const grid = document.createElement("div");
          grid.className = "p-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4";

          floorRooms.forEach(room => {
            const card = document.createElement("div");
            card.className = `room-card ${statusClassMap[room.status] || ""} rounded-lg p-4 shadow-sm`;

            card.innerHTML = `
              <div class="flex flex-col justify-between h-full">
                <div>
                  <div class="font-bold text-lg">${room.number}</div>
                  <div class="text-sm opacity-90">${room.type}</div>
                </div>
                <div class="mt-2">
                  <div class="text-xs font-medium">${room.status}</div>
                  <div class="text-xs opacity-75 mt-1 truncate">${room.extra || ''}</div>
                </div>
              </div>
            `;

            // Click to open modal
            card.addEventListener("click", () => openRoomModal(room));
            grid.appendChild(card);
          });

          floorEl.appendChild(grid);
          floorsContainer.appendChild(floorEl);
        });
      }

      // Modal functionality
      const modal = document.getElementById("roomModal");
      const closeModalBtn = document.getElementById("closeModal");
      const modalActionSecondary = document.getElementById("modalActionSecondary");
      const modalActionPrimary = document.getElementById("modalActionPrimary");

      function openRoomModal(room) {
        document.getElementById("modalRoomNumber").textContent = `Room ${room.number}`;
        document.getElementById("modalRoomType").textContent = `Type: ${room.type}`;
        document.getElementById("modalRoomStatus").textContent = `Status: ${room.status}`;
        document.getElementById("modalExtra").textContent = `Info: ${room.extra || '—'}`;
        
        modal.classList.remove("hidden");
        modal.classList.add("flex");

        // Primary action
        modalActionPrimary.onclick = () => {
          alert(`Primary action for room ${room.number} (${room.type})`);
          closeModal();
        };
      }

      function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
      }

      // Event listeners
      closeModalBtn.addEventListener("click", closeModal);
      modalActionSecondary.addEventListener("click", closeModal);

      // Close modal when clicking outside
      modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
      });

      // Close modal on Escape key
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !modal.classList.contains("hidden")) {
          closeModal();
        }
      });

      // Initialize
      renderFloors();
    </script>
  </body>
</html>
