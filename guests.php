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
        <div class="flex gap-3">
          <button id="addGuestBtn" class="gap-2 inline-flex items-center rounded-md bg-primary text-primary-foreground px-3 py-2 text-sm">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Guest
          </button>
          <button id="rewardsBtn" class="gap-2 inline-flex items-center rounded-md border border-primary text-primary hover:bg-primary hover:text-primary-foreground px-3 py-2 text-sm">
            <i data-lucide="gift" class="h-4 w-4"></i>
            Membership Rewards
          </button>
        </div>
      </div>

      

      <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
        <div class="mb-6">
          <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
            <input id="guestListSearch" placeholder="Search guests by name or email..." class="pl-9 h-9 w-full rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-primary/50" />
          </div>
        </div>

      

        <div id="guestList" class="space-y-4">
          <?php foreach ($guests as $guest): ?>
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 hover:shadow-md transition-shadow">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-lg font-bold"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h3>
                    <?php
                      // Calculate guest tier based on paid transactions
                      $guestTier = 'NORMAL';
                      $guestDiscount = 0;
                      if (isset($guest['paid_transactions_count'])) {
                        $paidCount = (int)$guest['paid_transactions_count'];
                        if ($paidCount >= 100) {
                          $guestTier = 'PLATINUM';
                          $guestDiscount = 40;
                        } elseif ($paidCount >= 50) {
                          $guestTier = 'GOLD';
                          $guestDiscount = 30;
                        } elseif ($paidCount >= 20) {
                          $guestTier = 'SILVER';
                          $guestDiscount = 20;
                        }
                      }
                    ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php 
                      echo match($guestTier) {
                        'PLATINUM' => 'bg-purple-100 text-purple-800',
                        'GOLD' => 'bg-yellow-100 text-yellow-800',
                        'SILVER' => 'bg-gray-100 text-gray-800',
                        default => 'bg-blue-100 text-blue-800'
                      }; 
                    ?>">
                      <?php echo htmlspecialchars($guestTier); ?>
                      <?php if ($guestDiscount > 0): ?>
                        (<?php echo $guestDiscount; ?>%)
                      <?php endif; ?>
                    </span>
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
                  <button class="inline-flex items-center rounded-md border px-3 py-2 text-sm" onclick="openGuestProfileModal(<?php echo (int)($guest['id'] ?? 0); ?>)">View Profile</button>
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

    <!-- Add Guest Modal -->
    <div id="addGuestModal" class="fixed inset-0 modal-overlay z-50 hidden">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content rounded-lg w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-4 border-b border-border flex-shrink-0 bg-card">
            <h2 class="text-lg font-semibold text-card-foreground">Create New Guest</h2>
            <button id="closeAddGuestModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
              <i data-lucide="x" class="h-5 w-5"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="flex-1 overflow-y-auto bg-card">
            <form id="addGuestForm" class="p-4">
              <!-- Two Column Layout -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column: Guest Information -->
                <div class="space-y-4">
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">First Name *</label>
                      <input type="text" id="add_first_name" name="first_name" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground" required>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Last Name *</label>
                      <input type="text" id="add_last_name" name="last_name" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground" required>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Email *</label>
                      <input type="email" id="add_email" name="email" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground" required>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Phone</label>
                      <input type="tel" id="add_phone" name="phone" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Address</label>
                      <input type="text" id="add_address" name="address" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">City</label>
                      <input type="text" id="add_city" name="city" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Country</label>
                      <input type="text" id="add_country" name="country" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">Nationality</label>
                      <input type="text" id="add_nationality" name="nationality" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                    </div>
                  </div>
                </div>

                <!-- Right Column: ID and Date Information -->
                <div class="space-y-4">
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">ID Type</label>
                      <select id="add_id_type" name="id_type" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground">
                        <option value="National ID">National ID</option>
                        <option value="Passport">Passport</option>
                        <option value="Driver License">Driver License</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-card-foreground mb-1">ID Number *</label>
                      <input type="text" id="add_id_number" name="id_number" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground" required>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-card-foreground mb-1">Date of Birth *</label>
                    <input type="date" id="add_date_of_birth" name="date_of_birth" class="w-full px-2 py-2 border border-border rounded-md focus:outline-none focus:ring-2 focus:ring-primary text-sm bg-background text-foreground" required>
                  </div>
                  
                  <!-- Buttons in right column -->
                  <div class="mt-6 flex justify-end gap-3">
                    <button type="button" id="cancelAddGuestBtn" class="btn-secondary px-4 py-2 text-sm rounded transition-colors">
                      Cancel
                    </button>
                    <button type="submit" id="submitAddGuestBtn" class="btn-primary px-4 py-2 text-sm rounded disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                      Create Guest
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Rewards Modal -->
    <div id="rewardsModal" class="hidden fixed inset-0 bg-black/60 z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-card text-card-foreground rounded-lg shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden border">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-4 border-b border-border flex-shrink-0">
            <h2 class="text-lg font-semibold text-card-foreground">Membership Rewards Program</h2>
            <button id="closeRewardsModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
              <i data-lucide="x" class="h-5 w-5"></i>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="flex-1 overflow-y-auto p-6">
            <div class="space-y-6">
              <!-- Program Overview -->
              <div class="text-center mb-8">
                <h3 class="text-xl font-bold mb-2">Earn Rewards Through Your Stays</h3>
                <p class="text-muted-foreground">The more you stay with us, the more benefits you unlock!</p>
              </div>

              <!-- Membership Tiers -->
              <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Normal Tier -->
                <div class="border rounded-lg p-4 bg-blue-50 border-blue-200">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-lg text-blue-800">Normal</h4>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      0+ stays
                    </span>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-blue-600"></i>
                      <span>Standard check-in process</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-blue-600"></i>
                      <span>Access to all room types</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-blue-600"></i>
                      <span>Basic customer support</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-blue-600"></i>
                      <span>Standard cancellation policy</span>
                    </div>
                  </div>
                </div>

                <!-- Silver Tier -->
                <div class="border rounded-lg p-4 bg-gray-50 border-gray-200">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-lg text-gray-800">Silver</h4>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      20+ stays
                    </span>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-gray-600"></i>
                      <span>All Normal benefits</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-gray-600"></i>
                      <span class="font-semibold text-green-600">20% discount</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-gray-600"></i>
                      <span>Priority booking</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-gray-600"></i>
                      <span>Late checkout (subject to availability)</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-gray-600"></i>
                      <span>Complimentary welcome drink</span>
                    </div>
                  </div>
                </div>

                <!-- Gold Tier -->
                <div class="border rounded-lg p-4 bg-yellow-50 border-yellow-200">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-lg text-yellow-800">Gold</h4>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      50+ stays
                    </span>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span>All Silver benefits</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span class="font-semibold text-green-600">30% discount</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span>Room upgrades (subject to availability)</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span>Express check-in/out</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span>Complimentary breakfast</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-yellow-600"></i>
                      <span>Free WiFi premium</span>
                    </div>
                  </div>
                </div>

                <!-- Platinum Tier -->
                <div class="border rounded-lg p-4 bg-purple-50 border-purple-200">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-lg text-purple-800">Platinum</h4>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                      100+ stays
                    </span>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>All Gold benefits</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span class="font-semibold text-green-600">40% discount</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>Guaranteed room upgrades</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>Personal concierge service</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>Complimentary spa services</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>Airport transfer service</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i data-lucide="check-circle" class="h-4 w-4 text-purple-600"></i>
                      <span>Exclusive VIP lounge access</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- How to Earn -->
              <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-4">How to Earn Rewards</h3>
                <div class="grid gap-4 md:grid-cols-2">
                  <div class="flex items-start gap-3">
                    <i data-lucide="calendar" class="h-5 w-5 text-primary mt-0.5"></i>
                    <div>
                      <h4 class="font-medium">Book Direct Stays</h4>
                      <p class="text-sm text-muted-foreground">Each paid transaction counts towards your membership tier</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <i data-lucide="star" class="h-5 w-5 text-primary mt-0.5"></i>
                    <div>
                      <h4 class="font-medium">Maintain Activity</h4>
                      <p class="text-sm text-muted-foreground">Keep your membership active with regular stays</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="border-t p-4 flex-shrink-0">
            <div class="flex justify-end">
              <button id="closeRewardsModalFooterBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                Got it!
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    <script>
      const input = document.getElementById('guestListSearch');
      const cards = Array.from(document.querySelectorAll('#guestList > div'));
      input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        cards.forEach(card => {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(q) ? '' : 'none';
        });
      });

      // Guest Profile Modal
      function openGuestProfileModal(guestId){
        const modal = document.getElementById('guestProfileModal');
        const body = document.getElementById('guestProfileBody');
        const metrics = document.getElementById('guestProfileMetrics');
        modal.classList.remove('hidden');
        body.innerHTML = '<div class="text-sm text-muted-foreground">Loading...</div>';
        metrics.innerHTML = '';
        if (window.lucide && window.lucide.createIcons) { window.lucide.createIcons(); }
        fetch(`<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/api/guests/` + guestId)
          .then(r => r.json())
          .then(data => {
            const g = data.data || {};
            const m = data.metrics || { timesCheckedIn: 0, totalPaid: 0 };
            body.innerHTML = `
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${inputField('First Name','first_name', g.first_name)}
                ${inputField('Last Name','last_name', g.last_name)}
                ${inputField('Email','email', g.email, 'email')}
                ${inputField('Phone','phone', g.phone)}
                ${inputField('Address','address', g.address)}
                ${inputField('City','city', g.city)}
                ${inputField('Country','country', g.country)}
                ${inputField('ID Type','id_type', g.id_type || 'National ID')}
                ${inputField('ID Number','id_number', g.id_number)}
                ${inputField('Date of Birth','date_of_birth', (g.date_of_birth||'').substring(0,10), 'date')}
                ${inputField('Nationality','nationality', g.nationality)}
              </div>
              <div>
                <label class="block text-xs text-muted-foreground mb-1">Notes</label>
                <textarea id="notes" class="w-full px-3 py-2 rounded-md border bg-background" rows="3">${escapeHtml(g.notes||'')}</textarea>
              </div>
              <div class="flex justify-end">
                <button id="saveGuestBtn" class="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground">Save Changes</button>
              </div>
            `;
            metrics.innerHTML = `
              <div class="flex items-center justify-between"><span>Times Checked In</span><span class="font-semibold">${m.timesCheckedIn||0}</span></div>
              <div class="flex items-center justify-between"><span>Total Paid</span><span class="font-semibold">₱${Number(m.totalPaid||0).toFixed(2)}</span></div>
              <div class="flex items-center justify-between"><span>Paid Transactions</span><span class="font-semibold">${m.paidTransactionsCount||0}</span></div>
              <div class="flex items-center justify-between">
                <span>Tier</span>
                <span class="font-semibold inline-flex items-center px-2 py-1 rounded-full text-xs ${getTierBadgeClass(m.tier||'NORMAL')}">
                  ${m.tier||'NORMAL'} ${m.discountPercentage > 0 ? `(${m.discountPercentage}%)` : ''}
                </span>
              </div>
            `;
            document.getElementById('saveGuestBtn').onclick = () => saveGuest(guestId);
            if (window.lucide && window.lucide.createIcons) { window.lucide.createIcons(); }
          });
      }

      function inputField(label, id, value, type='text'){
        return `
          <div>
            <label class="block text-xs text-muted-foreground mb-1">${label}</label>
            <input id="${id}" type="${type}" value="${escapeHtml(value||'')}" class="w-full px-3 py-2 rounded-md border bg-background" />
          </div>
        `;
      }
      function getTierBadgeClass(tier) {
        return {
          'PLATINUM': 'bg-purple-100 text-purple-800',
          'GOLD': 'bg-yellow-100 text-yellow-800',
          'SILVER': 'bg-gray-100 text-gray-800',
          'NORMAL': 'bg-blue-100 text-blue-800'
        }[tier] || 'bg-blue-100 text-blue-800';
      }
      function closeGuestProfileModal(){
        document.getElementById('guestProfileModal').classList.add('hidden');
      }
      // minimize/maximize removed per request
      function saveGuest(guestId){
        const payload = {
          first_name: document.getElementById('first_name').value,
          last_name: document.getElementById('last_name').value,
          email: document.getElementById('email').value,
          phone: document.getElementById('phone').value,
          address: document.getElementById('address').value,
          city: document.getElementById('city').value,
          country: document.getElementById('country').value,
          id_type: document.getElementById('id_type').value,
          id_number: document.getElementById('id_number').value,
          date_of_birth: document.getElementById('date_of_birth').value,
          nationality: document.getElementById('nationality').value,
          notes: document.getElementById('notes').value,
        };
        fetch(`<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/api/guests/` + guestId, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).then(r => r.json()).then(() => closeGuestProfileModal());
      }
    </script>
    <!-- Guest Profile Modal -->
    <div id="guestProfileModal" class="hidden fixed inset-0 bg-black/60 z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div id="guestProfilePanel" class="bg-card text-card-foreground rounded-lg shadow-2xl w-full max-w-3xl overflow-hidden border">
          <div class="flex items-center justify-between p-2 pl-4 border-b">
            <h2 class="text-sm md:text-lg font-semibold">Guest Profile</h2>
            <div class="flex items-center gap-1">
              <button title="Close" onclick="closeGuestProfileModal()" class="h-8 w-8 inline-flex items-center justify-center rounded-md hover:bg-accent/10 text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="h-4 w-4"></i>
              </button>
            </div>
          </div>
          <div id="guestProfileContent" class="grid lg:grid-cols-3 gap-0">
            <div class="p-4 lg:col-span-2" id="guestProfileBody"></div>
            <div class="p-4 border-t lg:border-t-0 lg:border-l">
              <h3 class="text-sm font-semibold mb-2">Metrics</h3>
              <div id="guestProfileMetrics" class="space-y-2 text-sm"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons && window.lucide.createIcons();</script>
    
    <!-- Add Guest Modal JavaScript -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const addGuestBtn = document.getElementById('addGuestBtn');
        const addGuestModal = document.getElementById('addGuestModal');
        const closeAddGuestModalBtn = document.getElementById('closeAddGuestModalBtn');
        const cancelAddGuestBtn = document.getElementById('cancelAddGuestBtn');
        const addGuestForm = document.getElementById('addGuestForm');

        // Open modal
        if (addGuestBtn) {
          addGuestBtn.addEventListener('click', function() {
            addGuestModal.classList.remove('hidden');
          });
        }

        // Close modal functions
        function closeAddGuestModal() {
          addGuestModal.classList.add('hidden');
          addGuestForm.reset();
        }

        if (closeAddGuestModalBtn) {
          closeAddGuestModalBtn.addEventListener('click', closeAddGuestModal);
        }

        if (cancelAddGuestBtn) {
          cancelAddGuestBtn.addEventListener('click', closeAddGuestModal);
        }

        // Close modal when clicking outside
        addGuestModal.addEventListener('click', function(e) {
          if (e.target === addGuestModal) {
            closeAddGuestModal();
          }
        });

        // Form submission
        if (addGuestForm) {
          addGuestForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitAddGuestBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';

            try {
              const formData = new FormData(addGuestForm);
              const guestData = Object.fromEntries(formData);

              const response = await fetch('<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/api/guests', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify(guestData)
              });

              const result = await response.json();

              if (!result.ok) {
                throw new Error(result.message || 'Failed to create guest');
              }

              alert('Guest created successfully!');
              closeAddGuestModal();
              location.reload(); // Refresh to show new guest

            } catch (error) {
              console.error('Error creating guest:', error);
              alert('Error: ' + error.message);
            } finally {
              submitBtn.disabled = false;
              submitBtn.textContent = 'Create Guest';
            }
          });
        }
      });
    </script>

    <!-- Rewards Modal JavaScript -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const rewardsBtn = document.getElementById('rewardsBtn');
        const rewardsModal = document.getElementById('rewardsModal');
        const closeRewardsModalBtn = document.getElementById('closeRewardsModalBtn');
        const closeRewardsModalFooterBtn = document.getElementById('closeRewardsModalFooterBtn');

        // Open rewards modal
        if (rewardsBtn && rewardsModal) {
          rewardsBtn.addEventListener('click', function() {
            rewardsModal.classList.remove('hidden');
            // Initialize icons for the modal
            if (window.lucide && window.lucide.createIcons) {
              window.lucide.createIcons();
            }
          });
        }

        // Close rewards modal functions
        function closeRewardsModal() {
          if (rewardsModal) {
            rewardsModal.classList.add('hidden');
          }
        }

        if (closeRewardsModalBtn) {
          closeRewardsModalBtn.addEventListener('click', closeRewardsModal);
        }

        if (closeRewardsModalFooterBtn) {
          closeRewardsModalFooterBtn.addEventListener('click', closeRewardsModal);
        }

        // Close modal when clicking outside
        if (rewardsModal) {
          rewardsModal.addEventListener('click', function(e) {
            if (e.target === rewardsModal) {
              closeRewardsModal();
            }
          });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && rewardsModal && !rewardsModal.classList.contains('hidden')) {
            closeRewardsModal();
          }
        });
      });
    </script>
  </body>
  </html>


