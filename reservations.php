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
      $statusClasses = [
        'confirmed' => 'bg-success/10 text-success border border-success/20',
        'pending' => 'bg-warning/10 text-warning border border-warning/20',
        'checked-in' => 'bg-accent/10 text-accent border border-accent/20',
        'cancelled' => 'bg-destructive/10 text-destructive border border-destructive/20',
      ];
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Reservations</h1>
          <p class="text-muted-foreground">Manage bookings and availability</p>
        </div>
        <button id="openModalBtn" class="gap-2 inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm">
          <i data-lucide="plus" class="h-4 w-4"></i>
          New Reservation
        </button>
      </div>

      <?php
        $today = date('Y-m-d');
        $arrivingToday = 0;
        $departingToday = 0;
        $totalRates = 0;

        foreach ($reservations as $res) {
          if (isset($res['checkin']) && $res['checkin'] === $today) {
            $arrivingToday++;
          }
          if (isset($res['checkout']) && $res['checkout'] === $today) {
            $departingToday++;
          }
          if (isset($res['rate'])) {
            $totalRates += $res['rate'];
          }
        }

        $averageRate = count($reservations) > 0 ? $totalRates / count($reservations) : 0;
      ?>
      <div class="grid gap-6 mb-6 md:grid-cols-4">
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

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b text-left">
              <th class="pb-3 text-sm font-medium text-muted-foreground">Confirmation</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Guest</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Room</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Check-in</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Check-out</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Nights</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Rate</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Status</th>
              <th class="pb-3 text-sm font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $res): ?>
              <tr class="border-b hover:bg-muted/50 transition-colors">
                <td class="py-4 font-medium"><?php echo $res['id']; ?></td>
                <td class="py-4"><?php echo $res['guest']; ?></td>
                <td class="py-4"><?php echo $res['room']; ?></td>
                <td class="py-4 text-sm"><?php echo date('m/d/Y', strtotime($res['checkin'])); ?></td>
                <td class="py-4 text-sm"><?php echo date('m/d/Y', strtotime($res['checkout'])); ?></td>
                <td class="py-4 text-sm"><?php echo $res['nights']; ?></td>
                <td class="py-4 font-medium"><?php echo formatCurrencyPhpPeso($res['rate'], 2); ?></td>
                <td class="py-4">
                  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs <?php echo $statusClasses[$res['status']]; ?>"><?php echo $res['status']; ?></span>
                </td>
                <td class="py-4">
                  <button class="text-sm px-2 py-1 rounded-md hover:bg-accent/10">View</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
  </body>
</html>


