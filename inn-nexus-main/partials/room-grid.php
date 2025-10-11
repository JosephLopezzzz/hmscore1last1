<?php
$rooms = [
  [ 'number' => '101', 'floor' => 1, 'status' => 'occupied', 'guest' => 'J. Smith' ],
  [ 'number' => '102', 'floor' => 1, 'status' => 'occupied', 'guest' => 'E. Williams' ],
  [ 'number' => '103', 'floor' => 1, 'status' => 'vacant' ],
  [ 'number' => '104', 'floor' => 1, 'status' => 'dirty' ],
  [ 'number' => '201', 'floor' => 2, 'status' => 'occupied', 'guest' => 'S. Johnson' ],
  [ 'number' => '202', 'floor' => 2, 'status' => 'vacant' ],
  [ 'number' => '203', 'floor' => 2, 'status' => 'reserved' ],
  [ 'number' => '204', 'floor' => 2, 'status' => 'reserved' ],
  [ 'number' => '301', 'floor' => 3, 'status' => 'occupied', 'guest' => 'M. Chen' ],
  [ 'number' => '302', 'floor' => 3, 'status' => 'maintenance' ],
  [ 'number' => '303', 'floor' => 3, 'status' => 'vacant' ],
  [ 'number' => '304', 'floor' => 3, 'status' => 'occupied', 'guest' => 'A. Taylor' ],
];

$statusClass = [
  'vacant' => 'bg-room-vacant text-white',
  'occupied' => 'bg-room-occupied text-white',
  'dirty' => 'bg-room-dirty text-white',
  'maintenance' => 'bg-room-maintenance text-white',
  'reserved' => 'bg-room-reserved text-white',
];

$floors = array_values(array_unique(array_map(fn($r) => $r['floor'], $rooms)));
sort($floors);
?>
<div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
  <div class="flex items-center justify-between mb-6">
    <h3 class="text-lg font-semibold">Room Status</h3>
    <div class="flex gap-2 flex-wrap">
      <?php foreach ($statusClass as $key => $class): ?>
        <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs">
          <span class="w-2 h-2 rounded-full <?php echo $class; ?>"></span>
          <?php echo ucfirst($key); ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="space-y-6">
    <?php foreach ($floors as $floor): ?>
      <div>
        <h4 class="text-sm font-medium text-muted-foreground mb-3">Floor <?php echo $floor; ?></h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <?php foreach (array_filter($rooms, fn($r) => $r['floor'] === $floor) as $room): ?>
            <?php $class = $statusClass[$room['status']]; ?>
            <button class="p-4 rounded-lg text-left transition-all hover:scale-105 hover:shadow-lg <?php echo $class; ?>" data-room-number="<?php echo $room['number']; ?>" data-room-status="<?php echo $room['status']; ?>">
              <div class="font-bold text-lg"><?php echo $room['number']; ?></div>
              <?php if (!empty($room['guest'])): ?>
                <div class="text-xs mt-1 opacity-90"><?php echo $room['guest']; ?></div>
              <?php endif; ?>
              <div class="text-xs mt-2 opacity-75"><?php echo ucwords(str_replace('-', ' ', $room['status'])); ?></div>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <script>
    // Toggle on click (cycle) or choose via context menu (right-click)
    const cycle = ['vacant','reserved','occupied','dirty','maintenance'];
    const bgClasses = ['bg-room-vacant','bg-room-occupied','bg-room-dirty','bg-room-maintenance','bg-room-reserved'];
    const statusToBg = { vacant: 'bg-room-vacant', reserved: 'bg-room-reserved', occupied: 'bg-room-occupied', dirty: 'bg-room-dirty', maintenance: 'bg-room-maintenance' };
    function nextStatus(current){ const i = cycle.indexOf(current); return cycle[(i + 1) % cycle.length]; }
    async function updateRoom(number, status){
      try {
        const res = await fetch('<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/api/rooms', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ number, status }) });
        return await res.json();
      } catch(e) { return null; }
    }
    function applyStatusToButton(btn, status){
      btn.setAttribute('data-room-status', status);
      const label = btn.querySelector('div:last-child');
      if (label) label.textContent = status.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase());
      bgClasses.forEach(cls => btn.classList.remove(cls));
      btn.classList.add(statusToBg[status] || 'bg-room-vacant');
    }
    const menu = document.createElement('div');
    menu.style.position = 'fixed'; menu.style.display = 'none'; menu.style.zIndex = '9999';
    menu.className = 'rounded-md border bg-card text-card-foreground shadow-lg p-1';
    cycle.forEach(s => { const b = document.createElement('button'); b.textContent = s.charAt(0).toUpperCase()+s.slice(1); b.className = 'block w-full text-left px-3 py-1 text-sm hover:bg-accent/10 rounded'; b.dataset.status = s; menu.appendChild(b); });
    document.body.appendChild(menu);
    let menuTargetBtn = null;
    menu.addEventListener('click', async (e) => {
      const t = e.target; if (!(t instanceof Element)) return; const status = t.getAttribute('data-status'); if (!status || !menuTargetBtn) return;
      const number = menuTargetBtn.getAttribute('data-room-number'); const resp = await updateRoom(number, status);
      if (resp && resp.ok) applyStatusToButton(menuTargetBtn, status); menu.style.display = 'none'; menuTargetBtn = null;
    });
    document.addEventListener('click', () => { menu.style.display = 'none'; menuTargetBtn = null; });
    document.querySelectorAll('[data-room-number]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const number = btn.getAttribute('data-room-number'); const current = btn.getAttribute('data-room-status'); const status = nextStatus(current);
        const resp = await updateRoom(number, status);
        if (resp && resp.ok) applyStatusToButton(btn, status); else alert('Failed to update room ' + number);
      });
      btn.addEventListener('contextmenu', (e) => { e.preventDefault(); menuTargetBtn = btn; menu.style.left = e.clientX + 'px'; menu.style.top = e.clientY + 'px'; menu.style.display = 'block'; });
    });
  </script>
</div>


