<?php
require_once __DIR__ . '/../includes/db.php';
$arrivals = fetchArrivals();
$departures = fetchDepartures();
if (!$arrivals || !$departures) {
  $arrivals = [
    [ 'id' => 1, 'name' => 'Sarah Johnson', 'room' => '204', 'time' => '14:00', 'status' => 'pending' ],
    [ 'id' => 2, 'name' => 'Michael Chen', 'room' => '315', 'time' => '15:30', 'status' => 'pending' ],
    [ 'id' => 3, 'name' => 'Emma Williams', 'room' => '102', 'time' => '16:00', 'status' => 'checked-in' ],
  ];
  $departures = [
    [ 'id' => 1, 'name' => 'David Brown', 'room' => '410', 'time' => '11:00', 'status' => 'pending' ],
    [ 'id' => 2, 'name' => 'Lisa Anderson', 'room' => '208', 'time' => '12:00', 'status' => 'checked-out' ],
    [ 'id' => 3, 'name' => 'James Wilson', 'room' => '506', 'time' => '10:30', 'status' => 'pending' ],
  ];
}
?>
<div class="grid gap-6 lg:grid-cols-2">
  <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <i data-lucide="arrow-down-to-line" class="h-5 w-5 text-success"></i>
        <h3 class="text-lg font-semibold">Today's Arrivals</h3>
      </div>
      <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs"><?php echo count($arrivals); ?> guests</span>
    </div>
    <div class="space-y-3">
      <?php foreach ($arrivals as $guest): ?>
        <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
          <div class="flex-1">
            <p class="font-medium"><?php echo $guest['name']; ?></p>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Room <?php echo $guest['room']; ?></span>
              <span>•</span>
              <i data-lucide="clock" class="h-3 w-3"></i>
              <span><?php echo $guest['time']; ?></span>
            </div>
          </div>
          <?php if ($guest['status'] === 'pending'): ?>
            <button class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm">Check In</button>
          <?php else: ?>
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs bg-success/10 text-success border-success/20">Checked In</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <i data-lucide="arrow-up-from-line" class="h-5 w-5 text-warning"></i>
        <h3 class="text-lg font-semibold">Today's Departures</h3>
      </div>
      <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs"><?php echo count($departures); ?> guests</span>
    </div>
    <div class="space-y-3">
      <?php foreach ($departures as $guest): ?>
        <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
          <div class="flex-1">
            <p class="font-medium"><?php echo $guest['name']; ?></p>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Room <?php echo $guest['room']; ?></span>
              <span>•</span>
              <i data-lucide="clock" class="h-3 w-3"></i>
              <span><?php echo $guest['time']; ?></span>
            </div>
          </div>
          <?php if ($guest['status'] === 'pending'): ?>
            <button class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm">Check Out</button>
          <?php else: ?>
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs bg-muted text-muted-foreground">Checked Out</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>


