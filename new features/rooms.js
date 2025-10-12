/* rooms.js
   Dynamically render floors and rooms from sample data.
   Clicking a room opens a modal with details.
*/

// ---------- Sample data ----------
const rooms = [
  { floor: 1, number: 101, type: "Single", status: "Occupied", extra: "Guest: John Doe" },
  { floor: 1, number: 102, type: "Double", status: "Vacant", extra: "Sea view" },
  { floor: 1, number: 103, type: "Deluxe", status: "For Cleaning", extra: "Cleaning in progress" },

  { floor: 2, number: 201, type: "Suite", status: "Vacant", extra: "Ready" },
  { floor: 2, number: 202, type: "Double", status: "Occupied", extra: "Guest: Maria L." },
  { floor: 2, number: 203, type: "Single", status: "Vacant", extra: "Promo rate" },

  { floor: 3, number: 301, type: "Single", status: "For Cleaning", extra: "Towel refill" },
  { floor: 3, number: 302, type: "Deluxe", status: "Occupied", extra: "Guest: Alex T." },
  { floor: 3, number: 303, type: "Suite", status: "Vacant", extra: "VIP ready" }
];

// ---------- Utilities ----------
const statusClassMap = {
  "Vacant": "room-vacant",
  "Occupied": "room-occupied",
  "For Cleaning": "room-cleaning"
};

const floorsContainer = document.getElementById("floorsContainer");

// Group rooms by floor (sorted)
const grouped = rooms.reduce((acc, r) => {
  if (!acc[r.floor]) acc[r.floor] = [];
  acc[r.floor].push(r);
  return acc;
}, {});

// Sort floors ascending
const floorNumbers = Object.keys(grouped).map(Number).sort((a,b) => a - b);

// ---------- Render ----------
function renderFloors() {
  floorsContainer.innerHTML = ""; // clear
  floorNumbers.forEach(floorNum => {
    const floorRooms = grouped[floorNum].sort((a,b)=> a.number - b.number);

    // Floor card
    const floorEl = document.createElement("section");
    floorEl.className = "floor";

    const header = document.createElement("div");
    header.className = "floor-header";
    header.innerHTML = `<div class="floor-title">Floor ${floorNum}</div><div class="floor-divider"></div>`;

    floorEl.appendChild(header);

    // Rooms grid
    const grid = document.createElement("div");
    grid.className = "rooms-grid";

    floorRooms.forEach(room => {
      const card = document.createElement("div");
      card.className = `room-card ${statusClassMap[room.status] || ""}`;

      card.innerHTML = `
        <div>
          <div class="room-number">${room.number}</div>
          <div class="room-type">${room.type}</div>
        </div>
        <div>
          <div class="room-status">${room.status}</div>
          <div class="room-meta"><div>•</div><div style="color: #9aa6b2; font-size:12px;">${room.extra || ''}</div></div>
        </div>
      `;

      // Click to open modal with details
      card.addEventListener("click", () => openRoomModal(room));

      grid.appendChild(card);
    });

    floorEl.appendChild(grid);
    floorsContainer.appendChild(floorEl);
  });
}

// ---------- Modal ----------
const modal = document.getElementById("roomModal");
const closeModalBtn = document.getElementById("closeModal");
const modalRoomNumber = document.getElementById("modalRoomNumber");
const modalRoomType = document.getElementById("modalRoomType");
const modalRoomStatus = document.getElementById("modalRoomStatus");
const modalExtra = document.getElementById("modalExtra");
const modalActionSecondary = document.getElementById("modalActionSecondary");
const modalActionPrimary = document.getElementById("modalActionPrimary");

function openRoomModal(room) {
  modalRoomNumber.textContent = `Room ${room.number}`;
  modalRoomType.textContent = `Type: ${room.type}`;
  modalRoomStatus.textContent = `Status: ${room.status}`;
  modalExtra.textContent = `Info: ${room.extra || '—'}`;
  modal.style.display = "flex";

  // Primary action (placeholder)
  modalActionPrimary.onclick = () => {
    alert(`Primary action for room ${room.number} (${room.type})`);
    closeModal();
  };
}
function closeModal(){ modal.style.display = "none"; }

closeModalBtn.addEventListener("click", closeModal);
modalActionSecondary.addEventListener("click", closeModal);

// close modal on outside click
window.addEventListener("click", (e) => {
  if (e.target === modal) closeModal();
});
// close modal on Esc
window.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeModal();
});

// initial render
renderFloors();
