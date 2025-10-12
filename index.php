<?php
  // Dashboard (Front Desk) - PHP version of React Index page
  $today = (new DateTime())->format('l, F j, Y');
?>
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
    <title>Dashboard - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Inn Nexus - Hotel Management System Dashboard" />
    <meta name="description" content="Professional hotel management system for reservations, billing, housekeeping, and guest services. Streamline your hospitality operations with Inn Nexus." />
    <meta name="keywords" content="hotel management, PMS, property management, reservations, billing, hospitality, hotel software, guest management" />
    <meta name="author" content="Inn Nexus Team" />
    <meta name="robots" content="index, follow" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" />
    <meta property="og:title" content="Inn Nexus - Hotel Management System" />
    <meta property="og:description" content="Professional hotel management system for reservations, billing, housekeeping, and guest services." />
    <meta property="og:image" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']); ?>/public/favicon.svg" />
    <meta property="og:site_name" content="Inn Nexus" />
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" />
    <meta property="twitter:title" content="Inn Nexus - Hotel Management System" />
    <meta property="twitter:description" content="Professional hotel management system for reservations, billing, housekeeping, and guest services." />
    <meta property="twitter:image" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']); ?>/public/favicon.svg" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    <link rel="icon" type="image/png" href="./public/favicon.ico" />
    <link rel="apple-touch-icon" href="./public/favicon.svg" />
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6" />
    <meta name="msapplication-TileColor" content="#3b82f6" />
    
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

    <main class="container mx-auto px-4 py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Front Desk Dashboard</h1>
          <p class="text-muted-foreground"><?php echo htmlspecialchars($today, ENT_QUOTES); ?></p>
        </div>
      </div>

      <?php include __DIR__ . '/partials/stats-overview.php'; ?>
      <?php include __DIR__ . '/partials/arrivals-departures.php'; ?>
      <?php include __DIR__ . '/partials/room-grid.php'; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();
    </script>
  </body>
  </html>


