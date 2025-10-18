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
<div class="guest-card arrival bg-card border border-border rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:transform hover:-translate-y-1 <?php echo $reservation['type'] === 'event' ? 'border-purple-200 bg-purple-50 dark:bg-purple-900/20 dark:border-purple-800' : ''; ?>">
          <div class="flex items-center justify-between mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <?php if ($reservation['type'] === 'event'): ?>
                  <i data-lucide="calendar" class="h-4 w-4 text-purple-600 dark:text-purple-400"></i>
                  <span class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Event</span>
                <?php else: ?>
                  <i data-lucide="user" class="h-4 w-4 text-blue-600 dark:text-blue-400"></i>
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Guest</span>
                <?php endif; ?>
              </div>
              <h3 class="font-bold text-card-foreground text-lg"><?php echo $reservation['name']; ?></h3>
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
              <button onclick="checkInGuest('<?php echo $reservation['id']; ?>')" class="h-8 px-4 rounded-md bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors">Check In</button>
            <?php elseif ($status === 'checked in'): ?>
              <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-success/10 text-success">Checked In</span>
            <?php elseif ($status === 'checked out'): ?>
              <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-muted text-muted-foreground">Checked Out</span>
            <?php else: ?>
              <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-secondary text-secondary-foreground"><?php echo ucfirst($status); ?></span>
            <?php endif; ?>
          </div>
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
        <div class="guest-card departure bg-card border border-border rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:transform hover:-translate-y-1 <?php echo $reservation['type'] === 'event' ? 'border-purple-200 bg-purple-50 dark:bg-purple-900/20 dark:border-purple-800' : ''; ?>">
          <div class="flex items-center justify-between mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <?php if ($reservation['type'] === 'event'): ?>
                  <i data-lucide="calendar" class="h-4 w-4 text-purple-600 dark:text-purple-400"></i>
                  <span class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">Event</span>
                <?php else: ?>
                  <i data-lucide="user" class="h-4 w-4 text-blue-600 dark:text-blue-400"></i>
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Guest</span>
                <?php endif; ?>
              </div>
              <h3 class="font-bold text-card-foreground text-lg"><?php echo $reservation['name']; ?></h3>
              <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <span>Room <?php echo $reservation['room']; ?></span>
                <span>•</span>
                <i data-lucide="clock" class="h-3 w-3"></i>
                <span><?php echo $reservation['time']; ?></span>
              </div>
            </div>
            <?php
            if ($status === 'checked in'): ?>
              <button onclick="checkOutGuest('<?php echo $reservation['id']; ?>')" class="h-8 px-4 rounded-md bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors">Check Out</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
// Show success message function
function showSuccessMessage(message) {
    // Remove any existing success messages
    const existingMessages = document.querySelectorAll('.success-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create success message element
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 transform transition-all duration-300 ease-in-out';
    successDiv.style.transform = 'translateX(100%)';
    successDiv.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span class="font-medium">${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(successDiv);
    
    // Animate in
    setTimeout(() => {
        successDiv.style.transform = 'translateX(0)';
    }, 10);
    
    // Remove after 3 seconds with animation
    setTimeout(() => {
        successDiv.style.transform = 'translateX(100%)';
        setTimeout(() => {
            successDiv.remove();
        }, 300);
    }, 3000);
}

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
                            // Show success message
                            showSuccessMessage('Checked in successfully!');
                            // Update the UI to show checked in status
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
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
                            // Show success message
                            showSuccessMessage('Checked out successfully!');
                            // Update the UI to show checked out status
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
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


