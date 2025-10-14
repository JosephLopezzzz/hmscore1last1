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
    <title>Guests - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin','receptionist']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php
      require_once __DIR__ . '/includes/db.php';
      $guests = fetchAllGuests();
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Guest Management</h1>
          <p class="text-muted-foreground">CRM and guest profiles</p>
        </div>
        <button class="gap-2 inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm">
          <i data-lucide="plus" class="h-4 w-4"></i>
          Add Guest
        </button>
      </div>

      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Total Guests</p>
          <p class="text-2xl font-bold"><?php echo count($guests); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Guests with Email</p>
          <p class="text-2xl font-bold"><?php echo count(array_filter($guests, fn($g) => !empty($g['email']))); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Guests with Phone</p>
          <p class="text-2xl font-bold"><?php echo count(array_filter($guests, fn($g) => !empty($g['phone']))); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Guests with Address</p>
          <p class="text-2xl font-bold"><?php echo count(array_filter($guests, fn($g) => !empty($g['address']))); ?></p>
        </div>
      </div>

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
        <div class="mb-6">
          <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
            <input id="guestSearch" placeholder="Search guests by name or email..." class="pl-9 h-9 w-full rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-primary/50" />
          </div>
        </div>

      

        <div id="guestList" class="space-y-4">
          <?php foreach ($guests as $guest): ?>
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 hover:shadow-md transition-shadow">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h3>
                  </div>  
                  <div class="grid gap-2 text-sm mb-4">
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <i data-lucide="mail" class="h-4 w-4"></i>
                      <?php echo htmlspecialchars($guest['email'] ?? 'N/A'); ?>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <i data-lucide="phone" class="h-4 w-4"></i>
                      <?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?>
                    </div>
                    <?php if (!empty($guest['address'])): ?>
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <i data-lucide="map-pin" class="h-4 w-4"></i>
                      <?php echo htmlspecialchars($guest['address']); ?><?php if (!empty($guest['city'])) echo ', ' . htmlspecialchars($guest['city']); ?><?php if (!empty($guest['country'])) echo ', ' . htmlspecialchars($guest['country']); ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="inline-flex items-center rounded-md border px-3 py-2 text-sm">View Profile</button>
                  <?php 
                    $guestPayload = [
                      'first_name' => $guest['first_name'] ?? '',
                      'last_name' => $guest['last_name'] ?? '',
                      'email' => $guest['email'] ?? '',
                      'phone' => $guest['phone'] ?? '',
                      'address' => $guest['address'] ?? '',
                      'city' => $guest['city'] ?? '',
                      'country' => $guest['country'] ?? '',
                      'id_type' => $guest['id_type'] ?? 'National ID',
                      'id_number' => $guest['id_number'] ?? '',
                      'date_of_birth' => $guest['date_of_birth'] ?? '',
                      'nationality' => $guest['nationality'] ?? ''
                    ];
                    $guestJson = htmlspecialchars(json_encode($guestPayload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP), ENT_QUOTES);
                    $guestName = htmlspecialchars(trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')), ENT_QUOTES);
                  ?>
                  <button class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm"
                    onclick="openReservationModalForGuest(<?php echo (int)($guest['id'] ?? 0); ?>, '<?php echo $guestName; ?>', <?php echo $guestJson; ?>)">
                    New Booking
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    <script>
      const input = document.getElementById('guestSearch');
      const cards = Array.from(document.querySelectorAll('#guestList > div'));
      input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        cards.forEach(card => {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(q) ? '' : 'none';
        });
      });
    </script>
    <?php include __DIR__ . '/reservation-modal.php'; ?>
  </body>
  </html>


