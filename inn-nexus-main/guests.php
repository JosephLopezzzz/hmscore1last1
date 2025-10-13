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
      if (!$guests || count($guests) === 0) {
        $guests = [
          [ 'id' => 1, 'name' => 'Sarah Johnson', 'email' => 'sarah.j@email.com', 'phone' => '+63 912 345 6789', 'stays' => 12, 'tier' => 'gold', 'lastVisit' => '2025-01-15' ],
          [ 'id' => 2, 'name' => 'Michael Chen', 'email' => 'm.chen@email.com', 'phone' => '+63 917 555 0102', 'stays' => 8, 'tier' => 'silver', 'lastVisit' => '2025-01-10' ],
          [ 'id' => 3, 'name' => 'Emma Williams', 'email' => 'emma.w@email.com', 'phone' => '+63 918 555 0103', 'stays' => 5, 'tier' => 'silver', 'lastVisit' => '2025-01-08' ],
          [ 'id' => 4, 'name' => 'David Brown', 'email' => 'd.brown@email.com', 'phone' => '+63 919 555 0104', 'stays' => 3, 'tier' => 'member', 'lastVisit' => '2024-12-20' ],
          [ 'id' => 5, 'name' => 'Lisa Anderson', 'email' => 'lisa.a@email.com', 'phone' => '+63 920 555 0105', 'stays' => 15, 'tier' => 'platinum', 'lastVisit' => '2025-01-05' ],
          [ 'id' => 6, 'name' => 'James Wilson', 'email' => 'j.wilson@email.com', 'phone' => '+63 921 555 0106', 'stays' => 2, 'tier' => 'member', 'lastVisit' => '2024-11-15' ],
        ];
      }
      $tierColors = [
        'platinum' => 'bg-gold/10 text-gold border border-gold/20',
        'gold' => 'bg-warning/10 text-warning border border-warning/20',
        'silver' => 'bg-muted text-muted-foreground border border-border',
        'member' => 'bg-accent/10 text-accent border border-accent/20',
      ];
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
          <p class="text-sm text-muted-foreground mb-1">Platinum Members</p>
          <p class="text-2xl font-bold"><?php echo count(array_filter($guests, fn($g) => $g['tier'] === 'platinum')); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Gold Members</p>
          <p class="text-2xl font-bold"><?php echo count(array_filter($guests, fn($g) => $g['tier'] === 'gold')); ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Avg. Stays</p>
          <p class="text-2xl font-bold">7.5</p>
        </div>
      </div>

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
        <div class="mb-6">
          <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
            <input id="guestSearch" placeholder="Search guests by name or email..." class="pl-9 h-9 w-full rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-primary/50" />
          </div>
        </div>

        <?php if (function_exists('getPdo') && getPdo()): ?>
          <form method="post" class="grid gap-3 md:grid-cols-6 items-end mb-6">
            <input type="hidden" name="_action" value="add_guest" />
            <div class="md:col-span-2">
              <label class="text-xs text-muted-foreground">Name</label>
              <input name="name" required class="h-9 w-full rounded-md border bg-background px-3 text-sm" />
            </div>
            <div class="md:col-span-2">
              <label class="text-xs text-muted-foreground">Email</label>
              <input name="email" type="email" required class="h-9 w-full rounded-md border bg-background px-3 text-sm" />
            </div>
            <div>
              <label class="text-xs text-muted-foreground">Phone</label>
              <input name="phone" class="h-9 w-full rounded-md border bg-background px-3 text-sm" />
            </div>
            <div>
              <button class="h-9 px-3 rounded-md bg-primary text-primary-foreground text-sm w-full">Add Guest</button>
            </div>
          </form>
          <?php
            if (($_POST['_action'] ?? '') === 'add_guest') {
              include_once __DIR__ . '/includes/db.php';
              createGuest([
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'stays' => 0,
                'tier' => 'member',
                'last_visit' => date('Y-m-d'),
              ]);
              header('Location: ' . $_SERVER['REQUEST_URI']);
              exit;
            }
          ?>
        <?php endif; ?>

        <div id="guestList" class="space-y-4">
          <?php foreach ($guests as $guest): ?>
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 hover:shadow-md transition-shadow">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-lg font-bold"><?php echo $guest['name']; ?></h3>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs <?php echo $tierColors[$guest['tier']]; ?>">
                      <i data-lucide="star" class="h-3 w-3 mr-1"></i>
                      <?php echo $guest['tier']; ?>
                    </span>
                  </div>
                  <div class="grid gap-2 text-sm mb-4">
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <i data-lucide="mail" class="h-4 w-4"></i>
                      <?php echo $guest['email']; ?>
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground">
                      <i data-lucide="phone" class="h-4 w-4"></i>
                      <?php echo $guest['phone']; ?>
                    </div>
                  </div>
                  <div class="flex gap-6 text-sm">
                    <div>
                      <span class="text-muted-foreground">Total Stays:</span>
                      <span class="font-medium ml-2"><?php echo $guest['stays']; ?></span>
                    </div>
                    <div>
                      <span class="text-muted-foreground">Last Visit:</span>
                      <span class="font-medium ml-2"><?php echo date('m/d/Y', strtotime($guest['lastVisit'])); ?></span>
                    </div>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="inline-flex items-center rounded-md border px-3 py-2 text-sm">View Profile</button>
                  <button class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm">New Booking</button>
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
  </body>
  </html>


