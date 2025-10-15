<?php
// Shared header/navigation used across pages
  $current = basename($_SERVER['SCRIPT_NAME']);
  $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  if ($basePath === '' || $basePath === '.') {
    $basePath = '';
  }
  $navItems = [
    [ 'label' => 'Front Desk', 'path' => 'index.php' ],
    [ 'label' => 'Reservations', 'path' => 'reservations.php' ],
    [ 'label' => 'Rooms', 'path' => 'rooms-overview.php' ],
    [ 'label' => 'Housekeeping', 'path' => 'housekeeping.php' ],
    [ 'label' => 'Billing', 'path' => 'billing.php' ],
    [ 'label' => 'Guests', 'path' => 'guests.php' ],
    [ 'label' => 'Analytics', 'path' => 'analytics.php' ],
  ];
?>
<header class="sticky top-0 z-50 w-full border-b bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/60">
  <div class="container mx-auto flex h-16 items-center justify-between px-4">
    <div class="flex items-center gap-6">
      <a href="<?php echo $basePath; ?>/index.php" class="flex items-center gap-2">
        <i data-lucide="hotel" class="h-6 w-6 text-accent"></i>
        <span class="text-xl font-bold text-foreground">Inn Nexus</span>
      </a>
      <nav class="hidden md:flex items-center gap-1">
        <?php foreach ($navItems as $item): $isActive = ($current === $item['path']); ?>
          <a href="<?php echo $basePath; ?>/<?php echo $item['path']; ?>" class="inline-flex">
            <button class="px-3 py-2 rounded-md text-sm hover:bg-accent/10 hover:text-accent <?php echo $isActive ? 'bg-accent/10 text-accent font-medium' : ''; ?>">
              <?php echo $item['label']; ?>
            </button>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="flex items-center gap-4">
      <div class="relative hidden sm:block w-64">
        <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
        <input type="text" placeholder="Search guests, rooms..." class="pl-9 h-9 w-full rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-primary/50" />
      </div>
      <button class="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-accent/10">
        <i data-lucide="bell" class="h-5 w-5"></i>
      </button>
      <?php require_once __DIR__ . '/db.php'; initSession(); ?>
      <?php if (currentUserRole()): ?>
        <span class="hidden sm:inline text-sm text-muted-foreground"><?php echo htmlspecialchars(currentUserEmail() ?? '', ENT_QUOTES); ?> (<?php echo currentUserRole(); ?>)</span>
        <div class="relative">
          <button id="user-menu-toggle" class="h-9 w-9 inline-flex items-center justify-center rounded-md hover:bg-accent/10">
            <i data-lucide="user" class="h-5 w-5"></i>
          </button>
          <div id="user-menu" class="hidden absolute right-0 top-10 w-48 rounded-md border bg-card text-card-foreground shadow-lg z-50">
            <div class="p-1">
              <button id="theme-toggle" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent/10 rounded">
                <i data-lucide="sun" class="h-4 w-4 dark:hidden"></i>
                <i data-lucide="moon" class="h-4 w-4 hidden dark:block"></i>
                <span class="theme-text">Light Mode</span>
              </button>
              <a href="<?php echo $basePath; ?>/setup-2fa.php" class="block">
                <button class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent/10 rounded">
                  <i data-lucide="shield" class="h-4 w-4"></i>
                  <span>2FA Settings</span>
                </button>
              </a>
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
  // User menu and theme toggle functionality
  document.addEventListener('DOMContentLoaded', function() {
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userMenu = document.getElementById('user-menu');
    const themeToggle = document.getElementById('theme-toggle');
    const themeText = document.querySelector('.theme-text');
    const html = document.documentElement;
    
    // Check for saved theme preference or default to 'light'
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');
    if (themeText) {
      themeText.textContent = currentTheme === 'dark' ? 'Dark Mode' : 'Light Mode';
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
        if (themeText) {
          themeText.textContent = newTheme === 'dark' ? 'Dark Mode' : 'Light Mode';
        }
        // Close menu after theme change
        if (userMenu) {
          userMenu.classList.add('hidden');
        }
      });
    }
  });
</script>


