<?php
require_once __DIR__ . '/../includes/db.php';

$arrivals = fetchArrivals();
$departures = fetchDepartures();
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
      <?php foreach ($arrivals as $reservation): ?>
        <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
          <div class="flex-1">
            <p class="font-medium"><?php echo $reservation['name']; ?></p>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Room <?php echo $reservation['room']; ?></span>
              <span>•</span>
              <i data-lucide="clock" class="h-3 w-3"></i>
              <span><?php echo $reservation['time']; ?></span>
            </div>
          </div>
          <?php
          $status = strtolower($reservation['status']);
          if ($status === 'pending'): ?>
            <button onclick="checkInGuest('<?php echo $reservation['id']; ?>')" class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm">Check In</button>
          <?php elseif ($status === 'checked in'): ?>
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs bg-success/10 text-success border-success/20">Checked In</span>
          <?php elseif ($status === 'checked out'): ?>
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs bg-muted text-muted-foreground">Checked Out</span>
          <?php else: ?>
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs bg-secondary text-secondary-foreground"><?php echo ucfirst($status); ?></span>
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
      <?php foreach ($departures as $reservation): ?>
        <?php
        $status = strtolower($reservation['status']);
        if ($status !== 'checked in') continue;
        ?>
        <div class="flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
          <div class="flex-1">
            <p class="font-medium"><?php echo $reservation['name']; ?></p>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Room <?php echo $reservation['room']; ?></span>
              <span>•</span>
              <i data-lucide="clock" class="h-3 w-3"></i>
              <span><?php echo $reservation['time']; ?></span>
            </div>
          </div>
          <?php
          if ($status === 'checked in'): ?>
            <button onclick="checkOutGuest('<?php echo $reservation['id']; ?>')" class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm">Check Out</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
function checkInGuest(reservationId) {
    if (confirm('Are you sure you want to check in this guest?')) {
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/hmscore1last1/api/check-in-guest.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('AJAX response status:', xhr.status);
                console.log('AJAX response text:', xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update the UI to show checked in status
                            location.reload();
                        } else {
                            alert('Error checking in guest: ' + response.message);
                        }
                    } catch (e) {
                        alert('Error parsing response: ' + e.message);
                    }
                } else {
                    alert('Error checking in guest. Status: ' + xhr.status + '. Please try again.');
                }
            }
        };

        xhr.send('reservation_id=' + encodeURIComponent(reservationId));
    }
}

function checkOutGuest(reservationId) {
    if (confirm('Are you sure you want to check out this guest?')) {
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/hmscore1last1/api/check-out-guest.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('AJAX response status:', xhr.status);
                console.log('AJAX response text:', xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update the UI to show checked out status
                            location.reload();
                        } else {
                            alert('Error checking out guest: ' + response.message);
                        }
                    } catch (e) {
                        alert('Error parsing response: ' + e.message);
                    }
                } else {
                    alert('Error checking out guest. Status: ' + xhr.status + '. Please try again.');
                }
            }
        };

        xhr.send('reservation_id=' + encodeURIComponent(reservationId));
    }
}
</script>


