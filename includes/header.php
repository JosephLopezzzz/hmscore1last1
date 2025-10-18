<?php
// Shared header/navigation used across pages
  $current = basename($_SERVER['SCRIPT_NAME']);
  $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  if ($basePath === '' || $basePath === '.') {
    $basePath = '';
  }
  $navItems = [
    [ 'label' => 'Front Desk', 'path' => 'index.php' ],
    [ 'label' => 'Rooms', 'path' => 'rooms-overview.php' ],
    [ 'label' => 'Housekeeping', 'path' => 'housekeeping.php' ],
    [ 'label' => 'Inventory', 'path' => 'inventory.php' ],
    [ 'label' => 'Billing', 'path' => 'billing.php' ],
    [ 'label' => 'Guests', 'path' => 'guests.php' ],
    [ 'label' => 'Channels', 'path' => 'channel-management.php', 'hidden' => true ],
    [ 'label' => 'Marketing', 'path' => 'marketing.php' ],
    [ 'label' => 'Analytics', 'path' => 'analytics.php' ],
  ];
  
  // Reservations submenu
  $reservationsSubmenu = [
    [ 'label' => 'Hotel Reservations', 'path' => 'reservations.php', 'module' => 'hotel' ],
    [ 'label' => 'Events & Conferences', 'path' => 'reservations.php', 'module' => 'events' ],
  ];
?>
<header class="sticky top-0 z-50 w-full border-b bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/60 light-mode-header">
  <div class="max-w-7xl mx-auto flex h-16 items-center justify-between px-6 w-full">
    <div class="flex items-center gap-6">
      <a href="<?php echo $basePath; ?>/index.php" class="flex items-center gap-2">
        <i data-lucide="hotel" class="h-6 w-6 text-accent"></i>
        <span class="text-xl font-bold text-foreground">Core 1</span>
      </a>
      <nav class="hidden md:flex items-center gap-2">
        <?php foreach ($navItems as $item): 
          if (isset($item['hidden']) && $item['hidden']) continue;
          $isActive = ($current === $item['path']); ?>
          <a href="<?php echo $basePath; ?>/<?php echo $item['path']; ?>" class="inline-flex">
            <button class="px-3 py-2 rounded-md text-sm hover:bg-accent/10 hover:text-accent <?php echo $isActive ? 'bg-accent/10 text-accent font-medium' : ''; ?>">
              <?php echo $item['label']; ?>
            </button>
          </a>
        <?php endforeach; ?>
        
        <!-- Reservations Dropdown -->
        <div class="relative group">
          <button class="px-3 py-2 rounded-md text-sm hover:bg-accent/10 hover:text-accent flex items-center gap-1 <?php echo ($current === 'reservations.php') ? 'bg-accent/10 text-accent font-medium' : ''; ?>">
            Reservations
            <i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-hover:rotate-180"></i>
          </button>
          <div class="absolute top-full left-0 mt-1 w-56 bg-card border border-border rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
            <div class="py-1">
              <?php foreach ($reservationsSubmenu as $subItem): ?>
                <?php if (isset($subItem['module'])): ?>
                  <button onclick="navigateToReservationsModule('<?php echo $subItem['module']; ?>')" class="block w-full text-left px-4 py-2 text-sm hover:bg-accent/10 hover:text-accent">
                    <?php echo $subItem['label']; ?>
                  </button>
                <?php else: ?>
                  <a href="<?php echo $basePath; ?>/<?php echo $subItem['path']; ?>" class="block">
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-accent/10 hover:text-accent">
                      <?php echo $subItem['label']; ?>
                    </button>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </nav>
    </div>

    <div class="flex items-center gap-6">
      <button class="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-accent/10">
        <i data-lucide="bell" class="h-5 w-5"></i>
      </button>
      <?php require_once __DIR__ . '/db.php'; initSession(); ?>
      <?php if (currentUserRole()): ?>
        <span class="hidden sm:inline text-sm text-muted-foreground"><?php echo currentUserRole(); ?></span>
        <div class="relative">
          <button id="user-menu-toggle" class="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-accent/10">
            <i data-lucide="user" class="h-5 w-5"></i>
          </button>
          <div id="user-menu" class="hidden absolute right-0 top-10 w-48 rounded-md border bg-card text-card-foreground shadow-lg z-50">
            <div class="p-1">
              <button id="theme-toggle" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent/10 rounded">
                <i data-lucide="sun" class="h-4 w-4" id="theme-icon-sun"></i>
                <i data-lucide="moon" class="h-4 w-4" id="theme-icon-moon" style="display:none"></i>
                <span class="theme-text">Light Mode</span>
              </button>
              <a href="<?php echo $basePath; ?>/logout.php" class="block">
                <button class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent/10 rounded">
                  <i data-lucide="log-out" class="h-4 w-4"></i>
                  <span>Logout</span>
                </button>
              </a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <a href="<?php echo $basePath; ?>/login.php" class="inline-flex">
          <button class="h-9 px-3 rounded-md bg-primary text-primary-foreground text-sm">Login</button>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<script>
  // Navigate to reservations page and show specific module
  function navigateToReservationsModule(module) {
    // If we're already on reservations page, just show module
    if (window.location.pathname.includes('reservations.php')) {
      // Show module immediately
      showModule(module);
    } else {
      // Store module in sessionStorage for when page loads
      sessionStorage.setItem('selectedModule', module);
      // Navigate to reservations page
      window.location.href = '<?php echo $basePath; ?>/reservations.php';
    }
  }
  
  // Show specific module on reservations page
  function showModule(module) {
    // Find sections by their specific IDs
    const hotelSection = document.getElementById('hotel-reservations-section');
    const eventsSection = document.getElementById('events-conferences-section');
    
    if (module === 'hotel') {
      // Show ONLY hotel reservations, hide events completely
      if (hotelSection) {
        hotelSection.style.display = 'block';
      }
      if (eventsSection) {
        eventsSection.style.display = 'none';
      }
    } else if (module === 'events') {
      // Show ONLY events, hide hotel reservations completely
      if (hotelSection) {
        hotelSection.style.display = 'none';
      }
      if (eventsSection) {
        eventsSection.style.display = 'block';
      }
    } else if (module === 'both') {
      // Show both modules (default state)
      if (hotelSection) {
        hotelSection.style.display = 'block';
      }
      if (eventsSection) {
        eventsSection.style.display = 'block';
      }
    }
  }
</script>

<script>
  // User menu and theme toggle functionality
  document.addEventListener('DOMContentLoaded', function() {
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userMenu = document.getElementById('user-menu');
    const themeToggle = document.getElementById('theme-toggle');
    const themeText = document.querySelector('.theme-text');
    const themeIconSun = document.getElementById('theme-icon-sun');
    const themeIconMoon = document.getElementById('theme-icon-moon');
    const html = document.documentElement;
    
    // Check for saved theme preference or default to 'light'
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');
    // Label should indicate the option to switch TO
    if (themeText) themeText.textContent = currentTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
    if (themeIconSun && themeIconMoon) {
      // In dark mode, show sun (switch to light). In light mode, show moon
      themeIconSun.style.display = currentTheme === 'dark' ? 'inline' : 'none';
      themeIconMoon.style.display = currentTheme === 'dark' ? 'none' : 'inline';
    }
    
    // User menu toggle
    if (userMenuToggle && userMenu) {
      userMenuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
      });
      
      // Close menu when clicking outside
      document.addEventListener('click', function(e) {
        if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
          userMenu.classList.add('hidden');
        }
      });
    }
    
    // Theme toggle
    if (themeToggle) {
      themeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isDark = html.classList.contains('dark');
        const newTheme = isDark ? 'light' : 'dark';
        html.classList.toggle('dark', !isDark);
        localStorage.setItem('theme', newTheme);
        // Label shows the mode to switch TO next
        if (themeText) themeText.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
        if (themeIconSun && themeIconMoon) {
          themeIconSun.style.display = newTheme === 'dark' ? 'inline' : 'none';
          themeIconMoon.style.display = newTheme === 'dark' ? 'none' : 'inline';
        }
        if (userMenu) userMenu.classList.add('hidden');
      });
    }
  });
</script>


