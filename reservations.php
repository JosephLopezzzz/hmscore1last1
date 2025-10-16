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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    
    <!-- Primary Meta Tags -->
    <title>Reservations - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Reservations Management - Inn Nexus Hotel Management System" />
    <meta name="description" content="Manage hotel reservations, bookings, and guest check-ins with Inn Nexus reservation management system. Streamline your booking process." />
    <meta name="keywords" content="hotel reservations, booking management, guest check-in, hotel booking system, reservation software" />
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
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin','receptionist']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/helpers.php'; ?>
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php
      $reservations = fetchAllReservations() ?: [];
      // Filter to only show pending and cancelled reservations
      $reservations = array_filter($reservations, function($res) {
        return isset($res['status']) && ($res['status'] === 'pending' || $res['status'] === 'cancelled');
      });
      // Reindex to avoid undefined index 0 after filtering
      $reservations = array_values($reservations);
      $statusClasses = [
        'confirmed' => 'bg-success/10 text-success border border-success/20',
        'pending' => 'bg-warning/10 text-warning border border-warning/20',
        'checked-in' => 'bg-accent/10 text-accent border border-accent/20',
        'checked in' => 'bg-accent/10 text-accent border border-accent/20',
        'cancelled' => 'bg-destructive/10 text-destructive border border-destructive/20',
      ];
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Reservations</h1>
          <p class="text-muted-foreground">Manage bookings and availability</p>
        </div>
      </div>

      <?php
        $today = date('Y-m-d');
        $arrivingToday = 0;
        $departingToday = 0;
        $totalRates = 0;
        $reservationsWithRates = 0;

        // Count arriving and departing guests
        // Note: Same-day bookings (check-in and check-out on same day) will be counted in both categories

        foreach ($reservations as $res) {
          // Check if checkin is today (within 24-hour range)
          $isArrivingToday = false;
          $isDepartingToday = false;

          if (isset($res['checkin']) && !empty($res['checkin'])) {
            // Convert database datetime to date-only for comparison
            $checkinDateTime = strtotime($res['checkin']);
            $todayStart = strtotime('today');
            $todayEnd = strtotime('tomorrow') - 1; // End of today

            if ($checkinDateTime >= $todayStart && $checkinDateTime <= $todayEnd) {
              $isArrivingToday = true;
            }
          }

          if (isset($res['checkout']) && !empty($res['checkout'])) {
            // Convert database datetime to date-only for comparison
            $checkoutDateTime = strtotime($res['checkout']);
            $todayStart = strtotime('today');
            $todayEnd = strtotime('tomorrow') - 1; // End of today

            if ($checkoutDateTime >= $todayStart && $checkoutDateTime <= $todayEnd) {
              $isDepartingToday = true;
            }
          }

          if ($isArrivingToday) {
            $arrivingToday++;
          }
          if ($isDepartingToday) {
            $departingToday++;
          }

          // Calculate average rate
          if (isset($res['rate']) && is_numeric($res['rate']) && $res['rate'] > 0) {
            $totalRates += floatval($res['rate']);
            $reservationsWithRates++;
          }
        }

        $averageRate = $reservationsWithRates > 0 ? $totalRates / $reservationsWithRates : 0;
      ?>

      <!-- Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Total Reservations</p>
          <p class="text-2xl font-bold"><?php echo count($reservations); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Arriving Today</p>
          <p class="text-2xl font-bold"><?php echo $arrivingToday; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Departing Today</p>
          <p class="text-2xl font-bold"><?php echo $departingToday; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Average Rate</p>
          <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso($averageRate, 2); ?></p>
        </div>
      </div>

      <!-- Hotel Reservations Section -->
      <div class="rounded-lg border bg-card text-card-foreground shadow-sm mb-6 overflow-hidden">
        <!-- Enhanced Top Section -->
        <div class="bg-gradient-to-r from-primary/5 to-primary/10 p-6 border-b border-border">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-foreground">Hotel Reservations</h3>
                <p class="text-muted-foreground">Manage guest bookings and check-ins</p>
              </div>
            </div>
            <div class="flex gap-3">
              <button id="openModalBtn" class="gap-2 inline-flex items-center rounded-lg bg-blue-600 text-white px-6 py-3 text-sm font-semibold hover:bg-blue-700 transition-colors shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Reservation
              </button>
            </div>
          </div>
        </div>
        
        <!-- Enhanced Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-muted/50">
              <tr class="border-b border-border">
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Confirmation
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Guest
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Room
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Check-in
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Check-out
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Nights
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Rate
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Status
                  </div>
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Actions
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <?php foreach ($reservations as $res): ?>
                <tr class="hover:bg-muted/50 transition-colors">
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                      </div>
                      <div>
                        <div class="font-medium text-foreground"><?php echo $res['id']; ?></div>
                        <div class="text-xs text-muted-foreground">Reservation</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg class="w-3 h-3 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                      </div>
                      <span class="text-sm font-medium"><?php echo $res['guest']; ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                      </div>
                      <span class="text-sm font-medium"><?php echo $res['room']; ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <span class="text-sm"><?php echo date('m/d/Y', strtotime($res['checkin'])); ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <span class="text-sm"><?php echo date('m/d/Y', strtotime($res['checkout'])); ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-4">
                    <span class="text-sm font-medium"><?php echo $res['nights']; ?> nights</span>
                  </td>
                  <td class="px-4 py-4">
                    <span class="text-sm font-medium"><?php echo formatCurrencyPhpPeso($res['rate'], 2); ?></span>
                  </td>
                  <td class="px-4 py-4">
                    <?php 
                      $status = strtolower($res['status']);
                      $statusClass = $statusClasses[$status] ?? 'bg-muted/10 text-muted border border-muted/20';
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $statusClass; ?>">
                      <?php echo ucfirst($res['status']); ?>
                    </span>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <button class="h-8 px-3 rounded-lg border border-border hover:bg-muted text-foreground text-xs font-medium transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View
                      </button>
                      <button class="h-8 px-3 rounded-lg border border-border hover:bg-blue-50 text-blue-600 text-xs font-medium transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Events & Conferences Section -->
      <div class="space-y-6">
        <div class="bg-card border border-border rounded-lg p-6">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-2xl font-bold text-foreground">Events & Conferences</h3>
                <p class="text-muted-foreground">Manage events, conferences, and group bookings</p>
              </div>
            </div>
            <div class="flex gap-3">
              <button id="toggleCalendarBtn" class="h-10 px-4 rounded-lg border border-border hover:bg-muted text-foreground transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Calendar View
              </button>
              <button id="addEventBtn" class="h-10 px-4 rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Event
              </button>
            </div>
          </div>

          <!-- Room Status Legend for Events -->
          <div class="mb-6 p-4 bg-muted/30 rounded-lg border border-border">
            <h4 class="text-sm font-semibold text-foreground mb-3">Event Room Status Legend</h4>
            <div class="flex flex-wrap gap-4">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background: #8b5cf6;"></div>
                <span class="text-sm text-muted-foreground">Event Reserved</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background: #ec4899;"></div>
                <span class="text-sm text-muted-foreground">Event Ongoing</span>
              </div>
            </div>
            <p class="text-xs text-muted-foreground mt-2">
              When events are created with room blocks, rooms show as "Event Reserved" (purple). 
              After check-in at front desk, rooms change to "Event Ongoing" (pink).
            </p>
          </div>

          <!-- Metrics Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-card border border-border rounded-lg p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Total Events</p>
                  <p id="metricTotalEvents" class="text-2xl font-bold text-foreground">0</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
              </div>
            </div>
            
            <div class="bg-card border border-border rounded-lg p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Upcoming Events</p>
                  <p id="metricUpcomingEvents" class="text-2xl font-bold text-foreground">0</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-warning/10 flex items-center justify-center">
                  <svg class="h-6 w-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>
            </div>
            
            <div class="bg-card border border-border rounded-lg p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Approved Events</p>
                  <p id="metricApprovedEvents" class="text-2xl font-bold text-foreground">0</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-success/10 flex items-center justify-center">
                  <svg class="h-6 w-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>
            </div>
            
            <div class="bg-card border border-border rounded-lg p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Revenue Estimate</p>
                  <p id="metricRevenueEstimate" class="text-2xl font-bold text-foreground">₱0</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Events List View -->
          <div id="eventsListView" class="space-y-6">
            <!-- Filter and Search Controls -->
            <div class="bg-card border border-border rounded-lg p-6">
              <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                  <input 
                    type="text" 
                    id="eventSearchInput" 
                    placeholder="Search events..." 
                    class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div class="flex gap-2">
                  <select id="eventStatusFilter" class="px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Cancelled">Cancelled</option>
                  </select>
                  <input 
                    type="date" 
                    id="eventDateFilter" 
                    class="px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                  <button id="refreshEventsBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button id="exportEventsBtn" class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Events Table -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-muted/50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Event</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Organizer</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date & Time</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Setup</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Rooms</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="eventsTableBody" class="divide-y divide-border">
                    <!-- Events will be loaded here -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Calendar View -->
          <div id="eventsCalendarView" class="hidden">
            <div class="bg-card border border-border rounded-lg p-6">
              <div id="calendar" class="bg-background rounded-lg border border-border p-4"></div>
            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- Event Modal -->
    <div id="eventModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-card border border-border rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h2 id="eventModalTitle" class="text-xl font-semibold text-foreground">New Event / Conference</h2>
            <p class="text-sm text-muted-foreground">Create a new event or conference booking</p>
          </div>
          <button id="closeEventModal" class="text-muted-foreground hover:text-foreground transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <form id="eventForm" class="p-6 space-y-6">
          <input type="hidden" id="eventId" name="event_id">
          
          <!-- Basic Information Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h4 class="font-medium text-foreground">Basic Information</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="eventTitle" class="block text-sm font-medium text-foreground mb-2">Event Title *</label>
                <input type="text" id="eventTitle" name="title" required class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter event title">
              </div>
              <div>
                <label for="eventOrganizer" class="block text-sm font-medium text-foreground mb-2">Organizer Name *</label>
                <input type="text" id="eventOrganizer" name="organizer_name" required class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter organizer name">
              </div>
              <div>
                <label for="eventContact" class="block text-sm font-medium text-foreground mb-2">Organizer Contact</label>
                <input type="text" id="eventContact" name="organizer_contact" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter contact information">
              </div>
              <div>
                <label for="eventAttendees" class="block text-sm font-medium text-foreground mb-2">Expected Attendees</label>
                <input type="number" id="eventAttendees" name="attendees_expected" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Number of attendees">
              </div>
            </div>
            
            <div>
              <label for="eventDescription" class="block text-sm font-medium text-foreground mb-2">Description</label>
              <textarea id="eventDescription" name="description" rows="3" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter event description"></textarea>
            </div>
          </div>

          <!-- Date & Time Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h4 class="font-medium text-foreground">Date & Time</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="eventStartDate" class="block text-sm font-medium text-foreground mb-2">Start Date & Time *</label>
                <input type="datetime-local" id="eventStartDate" name="start_datetime" required class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label for="eventEndDate" class="block text-sm font-medium text-foreground mb-2">End Date & Time *</label>
                <input type="datetime-local" id="eventEndDate" name="end_datetime" required class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
              </div>
            </div>
          </div>

          <!-- Setup & Rooms Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center">
                <svg class="w-3 h-3 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h4 class="font-medium text-foreground">Setup & Rooms</h4>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="eventSetupType" class="block text-sm font-medium text-foreground mb-2">Setup Type</label>
                <select id="eventSetupType" name="setup_type" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                  <option value="Conference">Conference</option>
                  <option value="Banquet">Banquet</option>
                  <option value="Theater">Theater</option>
                  <option value="Classroom">Classroom</option>
                  <option value="U-Shape">U-Shape</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="eventPrice" class="block text-sm font-medium text-foreground mb-2">Price Estimate (₱)</label>
                <input type="number" id="eventPrice" name="price_estimate" step="0.01" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0.00">
              </div>
            </div>
            
            <div>
              <label for="eventRoomBlocks" class="block text-sm font-medium text-foreground mb-2">Room Blocks</label>
              <select id="eventRoomBlocks" name="room_blocks[]" multiple class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                <!-- Rooms will be loaded here -->
              </select>
              <p class="text-xs text-muted-foreground mt-1">Hold Ctrl/Cmd to select multiple rooms</p>
            </div>
          </div>

          <!-- Status Section -->
          <div class="space-y-4">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center">
                <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h4 class="font-medium text-foreground">Status</h4>
            </div>
            
            <div>
              <label for="eventStatus" class="block text-sm font-medium text-foreground mb-2">Event Status</label>
              <select id="eventStatus" name="status" class="w-full px-4 py-2 border border-border rounded-lg bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="flex justify-end gap-3 pt-6 border-t border-border">
            <button type="button" id="cancelEventBtn" class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors">
              Cancel
            </button>
            <button type="submit" id="saveEventBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
              Save Event
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Event Detail Modal -->
    <div id="eventDetailModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-card border border-border rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h2 id="eventDetailTitle" class="text-xl font-semibold text-foreground">Event Details</h2>
            <p class="text-sm text-muted-foreground">View and manage event information</p>
          </div>
          <button id="closeEventDetailModal" class="text-muted-foreground hover:text-foreground transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <div id="eventDetailContent" class="p-6">
          <!-- Event details will be loaded here -->
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-end gap-3 p-6 border-t border-border">
          <button type="button" id="closeEventDetailBtn" class="px-4 py-2 border border-border rounded-lg hover:bg-muted transition-colors">
            Close
          </button>
          <button type="button" id="editEventBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
            Edit Event
          </button>
          <button type="button" id="cancelEventDetailBtn" class="px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
            Cancel Event
          </button>
        </div>
      </div>
    </div>

    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Hotel Sync JS -->
    <script src="./public/js/hotel-sync.js"></script>
    
    <script>
      // Global variables
      let currentEvents = [];
      let currentRooms = [];
      let calendar = null;
      let currentView = 'list'; // 'list' or 'calendar'
      let currentEvent = null;

      // Initialize on page load
      document.addEventListener('DOMContentLoaded', async function() {
        console.log('Initializing Events & Conferences module...');
        
        // Initialize hotel sync
        if (window.hotelSync) {
          await window.hotelSync.init();
        }
        
        // Load initial data
        await loadRooms();
        await loadEvents();
        
        // Setup event listeners
        setupEventListeners();
        
        console.log('Events & Conferences module initialized');
      });

      // Setup event listeners
      function setupEventListeners() {
        // Add Event button
        document.getElementById('addEventBtn').addEventListener('click', showEventModal);
        
        // Close Event Modal
        document.getElementById('closeEventModal').addEventListener('click', hideEventModal);
        document.getElementById('cancelEventBtn').addEventListener('click', hideEventModal);
        
        // Event Form submission
        document.getElementById('eventForm').addEventListener('submit', handleEventSubmit);
        
        // Toggle Calendar View
        document.getElementById('toggleCalendarBtn').addEventListener('click', toggleView);
        
        // Refresh Events
        document.getElementById('refreshEventsBtn').addEventListener('click', loadEvents);
        
        // Export Events
        document.getElementById('exportEventsBtn').addEventListener('click', exportEvents);
        
        // Search and Filter
        document.getElementById('eventSearchInput').addEventListener('input', filterEvents);
        document.getElementById('eventStatusFilter').addEventListener('change', filterEvents);
        document.getElementById('eventDateFilter').addEventListener('change', filterEvents);
        
        // Event Detail Modal
        document.getElementById('closeEventDetailModal').addEventListener('click', hideEventDetailModal);
        document.getElementById('closeEventDetailBtn').addEventListener('click', hideEventDetailModal);
        document.getElementById('editEventBtn').addEventListener('click', editEvent);
        document.getElementById('cancelEventDetailBtn').addEventListener('click', cancelEvent);
        
        // Date change handlers for room loading
        document.getElementById('eventStartDate').addEventListener('change', loadRooms);
        document.getElementById('eventEndDate').addEventListener('change', loadRooms);
      }

      // Load rooms for room blocks dropdown
      async function loadRooms() {
        try {
          console.log('Loading rooms...');
          
          // Try to get rooms from hotel sync first
          if (window.hotelSync && window.hotelSync.getRooms) {
            const rooms = window.hotelSync.getRooms();
            if (rooms && rooms.length > 0) {
              currentRooms = rooms;
              updateRoomBlocksDropdown();
              return;
            }
          }
          
          // Fallback to direct API call
          const response = await fetch('event_actions.php?action=get_rooms', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON. Please refresh the page to reload session.');
          }
          
          const data = await response.json();
          
          if (data.success && data.rooms) {
            currentRooms = data.rooms;
            updateRoomBlocksDropdown();
          } else {
            console.error('Failed to load rooms:', data.message || 'Unknown error');
            showToast('Failed to load rooms', 'error');
          }
        } catch (error) {
          console.error('Error loading rooms:', error);
          showToast('Error loading rooms: ' + error.message, 'error');
        }
      }

      // Update room blocks dropdown
      function updateRoomBlocksDropdown() {
        const roomBlocksSelect = document.getElementById('eventRoomBlocks');
        if (!roomBlocksSelect) return;
        
        // Clear existing options
        roomBlocksSelect.innerHTML = '';
        
        // Get date filters
        const startDate = document.getElementById('eventStartDate').value;
        const endDate = document.getElementById('eventEndDate').value;
        
        // Filter available rooms
        const availableRooms = currentRooms.filter(room => {
          // Basic availability check
          if (room.status !== 'Vacant' && room.status !== 'Available' && room.status !== 'Cleaning') {
            return false;
          }
          
          // If dates are selected, check for conflicts
          if (startDate && endDate) {
            // This is a simplified check - in a real implementation,
            // you'd check against existing reservations and events
            return true;
          }
          
          return true;
        });
        
        // Sort rooms by room number
        availableRooms.sort((a, b) => {
          const aNum = parseInt(a.room_number) || 0;
          const bNum = parseInt(b.room_number) || 0;
          return aNum - bNum;
        });
        
        // Add room options
        availableRooms.forEach(room => {
          const option = document.createElement('option');
          option.value = room.id;
          option.textContent = `Room ${room.room_number} (${room.room_type || 'Standard'}) - ${room.status}`;
          roomBlocksSelect.appendChild(option);
        });
        
        if (availableRooms.length === 0) {
          const option = document.createElement('option');
          option.value = '';
          option.textContent = 'No available rooms';
          option.disabled = true;
          roomBlocksSelect.appendChild(option);
        }
      }

      // Load events
      async function loadEvents() {
        try {
          console.log('Loading events...');
          
          // Try to get events from hotel sync first
          if (window.hotelSync && window.hotelSync.getEvents) {
            const events = window.hotelSync.getEvents();
            if (events && events.length >= 0) {
              currentEvents = events;
              displayEvents();
              updateEventStatistics();
              return;
            }
          }
          
          // Fallback to direct API call
          const response = await fetch('event_actions.php?action=get_all_events', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON. Please refresh the page to reload session.');
          }
          
          const data = await response.json();
          
          if (data.success && data.events) {
            currentEvents = data.events;
            displayEvents();
            updateEventStatistics();
          } else {
            console.error('Failed to load events:', data.message || 'Unknown error');
            showToast('Failed to load events', 'error');
          }
        } catch (error) {
          console.error('Error loading events:', error);
          showToast('Error loading events: ' + error.message, 'error');
        }
      }

      // Update event statistics
      function updateEventStatistics() {
        const totalEvents = currentEvents.length;
        const upcomingEvents = currentEvents.filter(event => {
          const eventDate = new Date(event.start_datetime);
          const today = new Date();
          return eventDate >= today && event.status !== 'Cancelled';
        }).length;
        const approvedEvents = currentEvents.filter(event => event.status === 'Approved').length;
        const revenueEstimate = currentEvents.reduce((total, event) => {
          return total + (parseFloat(event.price_estimate) || 0);
        }, 0);
        
        // Update metric elements
        const metricElement = document.getElementById('metricTotalEvents');
        if (metricElement) {
          metricElement.textContent = totalEvents;
        }
        
        document.getElementById('metricUpcomingEvents').textContent = upcomingEvents;
        document.getElementById('metricApprovedEvents').textContent = approvedEvents;
        document.getElementById('metricRevenueEstimate').textContent = '₱' + revenueEstimate.toLocaleString();
      }

      // Display events in table
      function displayEvents() {
        const tbody = document.getElementById('eventsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (currentEvents.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-muted-foreground">
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <p>No events found</p>
                  <p class="text-sm">Create your first event to get started</p>
                </div>
              </td>
            </tr>
          `;
          return;
        }
        
        currentEvents.forEach(event => {
          const row = createEventRow(event);
          tbody.appendChild(row);
        });
      }

      // Create event row
      function createEventRow(event) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-muted/50 transition-colors';
        
        // Format dates
        const startDate = new Date(event.start_datetime);
        const endDate = new Date(event.end_datetime);
        const dateTimeStr = `${startDate.toLocaleDateString()} ${startDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${endDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
        
        // Handle room numbers
        let rooms = 'None';
        if (event.room_numbers && Array.isArray(event.room_numbers) && event.room_numbers.length > 0) {
          rooms = event.room_numbers.join(', ');
        }
        
        // Status badge
        const statusClass = getStatusClass(event.status);
        const statusText = event.status || 'Pending';
        const displayStatus = statusText.charAt(0).toUpperCase() + statusText.slice(1).toLowerCase();
        
        row.innerHTML = `
          <td class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <div class="font-medium text-foreground">${event.title}</div>
                <div class="text-xs text-muted-foreground">${event.setup_type || 'Conference'}</div>
              </div>
            </div>
          </td>
          <td class="px-6 py-4">
            <div class="flex items-center gap-2">
              <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-3 h-3 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <span class="text-sm font-medium">${event.organizer_name}</span>
            </div>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm">${dateTimeStr}</div>
          </td>
          <td class="px-6 py-4">
            <span class="text-sm">${event.setup_type || 'Not specified'}</span>
          </td>
          <td class="px-6 py-4">
            <span class="text-sm">${rooms}</span>
          </td>
          <td class="px-6 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
              ${displayStatus}
            </span>
          </td>
          <td class="px-6 py-4">
            <div class="flex items-center gap-2">
              <button onclick="viewEvent(${event.id})" class="text-primary hover:text-primary/80 transition-colors" title="View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
              </button>
              <button onclick="editEvent(${event.id})" class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
              </button>
              ${event.status !== 'Cancelled' ? `
                <button onclick="cancelEvent(${event.id})" class="text-red-600 hover:text-red-800 transition-colors" title="Cancel">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              ` : ''}
            </div>
          </td>
        `;
        
        return row;
      }

      // Get status class for styling
      function getStatusClass(status) {
        switch (status) {
          case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
          case 'Approved':
            return 'bg-green-100 text-green-800';
          case 'Cancelled':
            return 'bg-red-100 text-red-800';
          default:
            return 'bg-gray-100 text-gray-800';
        }
      }

      // Filter events
      function filterEvents() {
        const searchTerm = document.getElementById('eventSearchInput').value.toLowerCase();
        const statusFilter = document.getElementById('eventStatusFilter').value;
        const dateFilter = document.getElementById('eventDateFilter').value;
        
        const filteredEvents = currentEvents.filter(event => {
          // Search filter
          if (searchTerm && !event.title.toLowerCase().includes(searchTerm) && 
              !event.organizer_name.toLowerCase().includes(searchTerm)) {
            return false;
          }
          
          // Status filter
          if (statusFilter && event.status !== statusFilter) {
            return false;
          }
          
          // Date filter
          if (dateFilter) {
            const eventDate = new Date(event.start_datetime).toDateString();
            const filterDate = new Date(dateFilter).toDateString();
            if (eventDate !== filterDate) {
              return false;
            }
          }
          
          return true;
        });
        
        // Update display with filtered events
        displayFilteredEvents(filteredEvents);
      }

      // Display filtered events
      function displayFilteredEvents(events) {
        const tbody = document.getElementById('eventsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (events.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-muted-foreground">
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                  <p>No events match your filters</p>
                </div>
              </td>
            </tr>
          `;
          return;
        }
        
        events.forEach(event => {
          const row = createEventRow(event);
          tbody.appendChild(row);
        });
      }

      // Export events
      function exportEvents() {
        if (currentEvents.length === 0) {
          showToast('No events to export', 'warning');
          return;
        }
        
        const csvContent = generateEventsCSV(currentEvents);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `events_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showToast('Events exported successfully', 'success');
      }

      // Generate CSV content
      function generateEventsCSV(events) {
        const headers = ['Title', 'Organizer', 'Start Date', 'End Date', 'Setup Type', 'Rooms', 'Status', 'Price Estimate'];
        const rows = events.map(event => [
          event.title,
          event.organizer_name,
          event.start_datetime,
          event.end_datetime,
          event.setup_type || 'Not specified',
          event.room_numbers ? event.room_numbers.join(', ') : 'None',
          event.status,
          event.price_estimate || '0'
        ]);
        
        return [headers, ...rows].map(row => row.map(field => `"${field}"`).join(',')).join('\n');
      }

      // Show event modal
      function showEventModal() {
        document.getElementById('eventModal').classList.remove('hidden');
        document.getElementById('eventModalTitle').textContent = 'New Event / Conference';
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        
        // Load rooms for room blocks
        loadRooms();
      }

      // Hide event modal
      function hideEventModal() {
        document.getElementById('eventModal').classList.add('hidden');
      }

      // Handle event form submission
      async function handleEventSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const eventData = Object.fromEntries(formData.entries());
        
        // Convert room_blocks to array
        eventData.room_blocks = Array.from(document.getElementById('eventRoomBlocks').selectedOptions).map(option => option.value);
        
        // Validate required fields
        if (!eventData.title || !eventData.organizer_name || !eventData.start_datetime || !eventData.end_datetime) {
          showToast('Please fill in all required fields', 'error');
          return;
        }
        
        // Validate dates
        if (new Date(eventData.start_datetime) >= new Date(eventData.end_datetime)) {
          showToast('End date must be after start date', 'error');
          return;
        }
        
        try {
          const eventId = document.getElementById('eventId').value;
          let result;
          
          if (eventId) {
            // Update existing event
            if (window.hotelSync && window.hotelSync.updateEvent) {
              result = await window.hotelSync.updateEvent(eventId, eventData);
            } else {
              result = await updateEventDirect(eventId, eventData);
            }
          } else {
            // Create new event
            if (window.hotelSync && window.hotelSync.createEvent) {
              result = await window.hotelSync.createEvent(eventData);
            } else {
              result = await createEventDirect(eventData);
            }
          }
          
          if (result) {
            hideEventModal();
            await loadEvents();
            showToast(eventId ? 'Event updated successfully' : 'Event created successfully', 'success');
          }
        } catch (error) {
          console.error('Error saving event:', error);
          showToast('Error saving event: ' + error.message, 'error');
        }
      }

      // Create event directly (fallback)
      async function createEventDirect(eventData) {
        const response = await fetch('event_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'add_event',
            ...eventData
          })
        });
        
        const data = await response.json();
        return data.success;
      }

      // Update event directly (fallback)
      async function updateEventDirect(eventId, eventData) {
        const response = await fetch('event_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'update_event',
            event_id: eventId,
            ...eventData
          })
        });
        
        const data = await response.json();
        return data.success;
      }

      // View event details
      async function viewEvent(eventId) {
        try {
          const event = currentEvents.find(e => e.id == eventId);
          if (!event) {
            showToast('Event not found', 'error');
            return;
          }
          
          currentEvent = event;
          showEventDetailModal(event);
        } catch (error) {
          console.error('Error viewing event:', error);
          showToast('Error loading event details', 'error');
        }
      }

      // Show event detail modal
      function showEventDetailModal(event) {
        const modal = document.getElementById('eventDetailModal');
        const content = document.getElementById('eventDetailContent');
        
        // Format dates
        const startDate = new Date(event.start_datetime);
        const endDate = new Date(event.end_datetime);
        
        // Handle room numbers
        let rooms = 'None';
        if (event.room_numbers && Array.isArray(event.room_numbers) && event.room_numbers.length > 0) {
          rooms = event.room_numbers.join(', ');
        }
        
        content.innerHTML = `
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="font-semibold text-foreground mb-2">Event Information</h3>
                <div class="space-y-2">
                  <div><span class="font-medium">Title:</span> ${event.title}</div>
                  <div><span class="font-medium">Organizer:</span> ${event.organizer_name}</div>
                  <div><span class="font-medium">Contact:</span> ${event.organizer_contact || 'Not provided'}</div>
                  <div><span class="font-medium">Attendees:</span> ${event.attendees_expected || 'Not specified'}</div>
                  <div><span class="font-medium">Setup:</span> ${event.setup_type || 'Not specified'}</div>
                  <div><span class="font-medium">Status:</span> <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(event.status)}">${event.status}</span></div>
                </div>
              </div>
              <div>
                <h3 class="font-semibold text-foreground mb-2">Schedule</h3>
                <div class="space-y-2">
                  <div><span class="font-medium">Start:</span> ${startDate.toLocaleString()}</div>
                  <div><span class="font-medium">End:</span> ${endDate.toLocaleString()}</div>
                  <div><span class="font-medium">Duration:</span> ${Math.ceil((endDate - startDate) / (1000 * 60 * 60))} hours</div>
                </div>
              </div>
            </div>
            
            <div>
              <h3 class="font-semibold text-foreground mb-2">Description</h3>
              <p class="text-muted-foreground">${event.description || 'No description provided'}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="font-semibold text-foreground mb-2">Room Assignment</h3>
                <p class="text-muted-foreground">${rooms}</p>
              </div>
              <div>
                <h3 class="font-semibold text-foreground mb-2">Pricing</h3>
                <p class="text-2xl font-bold text-primary">₱${(event.price_estimate || 0).toLocaleString()}</p>
              </div>
            </div>
          </div>
        `;
        
        modal.classList.remove('hidden');
      }

      // Hide event detail modal
      function hideEventDetailModal() {
        document.getElementById('eventDetailModal').classList.add('hidden');
        currentEvent = null;
      }

      // Edit event
      async function editEvent(eventId) {
        try {
          const event = currentEvents.find(e => e.id == eventId);
          if (!event) {
            showToast('Event not found', 'error');
            return;
          }
          
          // Populate form with event data
          document.getElementById('eventId').value = event.id;
          document.getElementById('eventTitle').value = event.title;
          document.getElementById('eventOrganizer').value = event.organizer_name;
          document.getElementById('eventContact').value = event.organizer_contact || '';
          document.getElementById('eventAttendees').value = event.attendees_expected || '';
          document.getElementById('eventDescription').value = event.description || '';
          document.getElementById('eventStartDate').value = event.start_datetime.substring(0, 16);
          document.getElementById('eventEndDate').value = event.end_datetime.substring(0, 16);
          document.getElementById('eventSetupType').value = event.setup_type || 'Conference';
          document.getElementById('eventPrice').value = event.price_estimate || '';
          document.getElementById('eventStatus').value = event.status || 'Pending';
          
          // Load rooms and select current room blocks
          await loadRooms();
          
          // Select current room blocks
          if (event.room_numbers && Array.isArray(event.room_numbers)) {
            const roomBlocksSelect = document.getElementById('eventRoomBlocks');
            Array.from(roomBlocksSelect.options).forEach(option => {
              option.selected = event.room_numbers.includes(option.value);
            });
          }
          
          // Show modal
          document.getElementById('eventModal').classList.remove('hidden');
          document.getElementById('eventModalTitle').textContent = 'Edit Event / Conference';
          
          // Hide detail modal
          hideEventDetailModal();
        } catch (error) {
          console.error('Error editing event:', error);
          showToast('Error loading event for editing', 'error');
        }
      }

      // Cancel event
      async function cancelEvent(eventId) {
        try {
          const result = await Swal.fire({
            title: 'Cancel Event',
            text: 'Are you sure you want to cancel this event? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, cancel event',
            cancelButtonText: 'No, keep event'
          });
          
          if (result.isConfirmed) {
            let success = false;
            
            if (window.hotelSync && window.hotelSync.updateEvent) {
              success = await window.hotelSync.updateEvent(eventId, { status: 'Cancelled' });
            } else {
              success = await cancelEventDirect(eventId);
            }
            
            if (success) {
              await loadEvents();
              showToast('Event cancelled successfully', 'success');
              hideEventDetailModal();
            } else {
              showToast('Failed to cancel event', 'error');
            }
          }
        } catch (error) {
          console.error('Error cancelling event:', error);
          showToast('Error cancelling event: ' + error.message, 'error');
        }
      }

      // Cancel event directly (fallback)
      async function cancelEventDirect(eventId) {
        const response = await fetch('event_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'cancel_event',
            event_id: eventId
          })
        });
        
        const data = await response.json();
        return data.success;
      }

      // Toggle between list and calendar view
      function toggleView() {
        const listView = document.getElementById('eventsListView');
        const calendarView = document.getElementById('eventsCalendarView');
        const toggleBtn = document.getElementById('toggleCalendarBtn');
        
        if (currentView === 'list') {
          // Switch to calendar view
          listView.classList.add('hidden');
          calendarView.classList.remove('hidden');
          toggleBtn.textContent = 'List View';
          currentView = 'calendar';
          initializeCalendar();
        } else {
          // Switch to list view
          calendarView.classList.add('hidden');
          listView.classList.remove('hidden');
          toggleBtn.textContent = 'Calendar View';
          currentView = 'list';
        }
      }

      // Initialize calendar
      function initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
          console.error('Calendar element not found');
          return;
        }
        
        // Destroy existing calendar if it exists
        if (calendar) {
          calendar.destroy();
        }
        
        // Create calendar
        calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          height: 'auto',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
          },
          events: currentEvents.map(event => ({
            id: event.id,
            title: event.title,
            start: event.start_datetime,
            end: event.end_datetime,
            backgroundColor: getEventColor(event.status),
            borderColor: getEventColor(event.status),
            textColor: '#ffffff',
            extendedProps: {
              organizer: event.organizer_name,
              setup: event.setup_type,
              rooms: event.room_numbers ? event.room_numbers.join(', ') : 'None',
              status: event.status
            }
          })),
          eventClick: function(info) {
            viewEvent(info.event.id);
          },
          eventDidMount: function(info) {
            // Add tooltip
            info.el.title = `${info.event.title} - ${info.event.extendedProps.organizer}`;
          },
          locale: 'en',
          firstDay: 1,
          weekends: true,
          selectable: false,
          dayMaxEvents: true,
          moreLinkClick: 'popover'
        });
        
        // Render calendar
        setTimeout(() => {
          calendar.render();
        }, 100);
      }

      // Get event color based on status
      function getEventColor(status) {
        switch (status) {
          case 'Pending':
            return '#f59e0b';
          case 'Approved':
            return '#10b981';
          case 'Cancelled':
            return '#ef4444';
          default:
            return '#6b7280';
        }
      }

      // Show toast notification
      function showToast(message, type = 'info') {
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: type,
          title: message
        });
      }
    </script>
    
    <?php include __DIR__ . '/reservation-modal.php'; ?>
  </body>
</html>
