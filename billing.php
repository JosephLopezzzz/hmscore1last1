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
    <title>Billing & Payments - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Billing & Payments - Inn Nexus Hotel Management System" />
    <meta name="description" content="Manage hotel billing, payments, and guest folios with Inn Nexus billing management system. Process payments and track revenue efficiently." />
    <meta name="keywords" content="hotel billing, payment processing, guest folios, hotel accounting, revenue management, payment systems" />
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
    <?php
      // Get PDO connection
      $pdo = getPdo();

      // Fetch only pending reservations for billing (exclude fully paid)
      $foliosQuery = "
        SELECT
          r.id,
          CASE 
            WHEN r.id LIKE 'EVT-%' THEN CONCAT('Event: ', e.title, ' - ', e.organizer_name)
            ELSE CONCAT(g.first_name, ' ', g.last_name)
          END as guest_name,
          r.room_id,
          rm.room_number,
          r.check_in_date,
          r.check_out_date,
          CASE 
            WHEN r.id LIKE 'EVT-%' THEN e.price_estimate
            ELSE rm.rate
          END as amount,
          r.payment_status
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN event_reservations er ON r.id = er.reservation_id
        LEFT JOIN events e ON er.event_id = e.id
        WHERE r.payment_status IN ('PENDING', 'DOWNPAYMENT')
        ORDER BY r.created_at DESC
        LIMIT 10
      ";

      $stmt = $pdo->prepare($foliosQuery);
      $stmt->execute();
      $folios = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Fetch recent transactions
      $recentQuery = "
        SELECT
          bt.id,
          bt.transaction_type as type,
          bt.amount,
          bt.payment_amount,
          bt.balance,
          bt.change,
          bt.payment_method as method,
          bt.status,
          bt.notes,
          bt.transaction_date
        FROM billing_transactions bt
        WHERE bt.transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY bt.transaction_date DESC
        LIMIT 5
      ";

      $stmt = $pdo->prepare($recentQuery);
      $stmt->execute();
      $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $statusColors = [
        'Paid' => 'bg-success/10 text-success border border-success/20',
        'Pending' => 'bg-warning/10 text      $totalOutstanding = (float)$rawOutstanding;
      error_log("After float conversion: " . gettype($totalOutstanding) . ", value: {$totalOutstanding}");
rning/10 text-warning border border-warning/20',
      ];

      $totalRevenue = 0;
      $totalPaid = 0;
      $totalOutstanding = 0;
      // Fetch billing data for JavaScript export functionality
      $billingDataQuery = "
        SELECT
          bt.id,
          bt.transaction_type,
          bt.amount,
          bt.payment_amount,
          bt.balance,
          bt.change,
          bt.payment_method as method,
          bt.status,
          bt.notes,
          bt.transaction_date
        FROM billing_transactions bt
        ORDER BY bt.transaction_date DESC
        LIMIT 100
      ";

      $stmt = $pdo->prepare($billingDataQuery);
      $stmt->execute();
      $billingData = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Fetch PAID transactions and fully paid reservations for the second section
      $paidTransactionsQuery = "
        SELECT
          bt.id,
          bt.transaction_type,
          bt.amount,
          bt.payment_amount,
          bt.balance,
          bt.change,
          bt.payment_method,
          bt.status,
          bt.notes,
          bt.transaction_date,
          CASE 
            WHEN r.id LIKE 'EVT-%' THEN CONCAT('Event: ', e.title, ' - ', e.organizer_name)
            ELSE CONCAT(g.first_name, ' ', g.last_name)
          END as guest_name,
          rm.room_number,
          r.id as reservation_id
        FROM billing_transactions bt
        LEFT JOIN reservations r ON bt.reservation_id = r.id
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN event_reservations er ON r.id = er.reservation_id
        LEFT JOIN events e ON er.event_id = e.id
        WHERE bt.status = 'Paid' OR r.payment_status = 'FULLY PAID'
        ORDER BY bt.transaction_date DESC
        LIMIT 10
      ";

      $stmt = $pdo->prepare($paidTransactionsQuery);
      $stmt->execute();
      $paidTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Calculate billing totals
      $totalsQuery = "
        SELECT
          SUM(CASE WHEN bt.status = 'Paid' THEN bt.amount ELSE 0 END) as total_paid,
          SUM(CASE WHEN bt.status = 'Paid' THEN bt.payment_amount ELSE 0 END) as total_paid_amount,
          SUM(CASE WHEN bt.status = 'Pending' THEN bt.balance ELSE 0 END) as total_outstanding,
          SUM(bt.amount) as total_revenue
        FROM billing_transactions bt
      ";

      $stmt = $pdo->prepare($totalsQuery);
      $stmt->execute();
      $totals = $stmt->fetch(PDO::FETCH_ASSOC);

      $totalRevenue = (float)($totals['total_revenue'] ?? 0);
      $totalPaid = (float)($totals['total_paid_amount'] ?? 0);
      $totalOutstanding = (float)($totals['total_outstanding'] ?? 0);
    ?>
    <main class="container mx-auto px-4 py-3">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold">Billing & Payments</h1>
          <p class="text-muted-foreground">Manage guest folios and transactions</p>
        </div>
        <div class="flex gap-3">
        </div>
      </div>


      <div class="grid gap-4 mb-4 md:grid-cols-3">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3">
          <div class="flex items-center gap-2">
            <div class="p-1.5 rounded-lg bg-accent/10">
              <i data-lucide="dollar-sign" class="h-4 w-4 text-accent"></i>
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Total Revenue</p>
              <p class="text-xl font-bold"><?php echo formatCurrencyPhpPeso($totalRevenue, 2); ?></p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3">
          <div class="flex items-center gap-2">
            <div class="p-1.5 rounded-lg bg-success/10">
              <i data-lucide="credit-card" class="h-4 w-4 text-success"></i>
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Paid</p>
              <p class="text-xl font-bold"><?php echo formatCurrencyPhpPeso($totalPaid, 2); ?></p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-3">
          <div class="flex items-center gap-2">
            <div class="p-1.5 rounded-lg bg-warning/10">
              <i data-lucide="alert-circle" class="h-4 w-4 text-warning"></i>
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Outstanding</p>
              <p class="text-xl font-bold"><?php echo formatCurrencyPhpPeso($totalOutstanding, 2); ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="mb-3">
            <h3 class="text-base font-semibold">Pending Transactions</h3>
          </div>
          <div class="space-y-2">
            <?php foreach ($folios as $folio): ?>
              <div class="transaction-card pending bg-card border border-border rounded-lg p-3 hover:shadow-sm transition-all duration-200">
                <div class="flex items-start justify-between mb-2">
                  <div class="flex-1">
                    <h3 class="font-semibold text-card-foreground text-base">Reservation #<?php echo $folio['id']; ?></h3>
                    <p class="text-xs text-muted-foreground"><?php echo $folio['guest_name']; ?> • Room <?php echo $folio['room_number']; ?></p>
                  </div>
                  <?php if ($folio['payment_status'] !== 'FULLY PAID'): ?>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium <?php echo $statusColors[$folio['payment_status']] ?? $statusColors['Pending']; ?>"><?php echo ucfirst($folio['payment_status'] ?? 'Pending'); ?></span>
                  <?php endif; ?>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <div class="flex flex-col space-y-1">
                    <span class="text-muted-foreground">Amount: <span class="font-semibold text-card-foreground"><?php echo formatCurrencyPhpPeso($folio['amount'] ?? 0, 2); ?></span></span>
                    <span class="text-muted-foreground">Check-in: <?php echo date('M d, Y', strtotime($folio['check_in_date'])); ?></span>
                  </div>
                  <?php if ($folio['payment_status'] !== 'FULLY PAID'): ?>
                    <button class="process-payment-btn inline-flex items-center gap-2 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            data-guest="<?php echo htmlspecialchars($folio['guest_name']); ?>"
                            data-room="<?php echo htmlspecialchars($folio['room_number']); ?>"
                            data-balance="<?php echo $folio['amount'] ?? 0; ?>"
                            data-reservation-id="<?php echo $folio['id']; ?>">
                      <i data-lucide="credit-card" class="h-3 w-3"></i>
                      PAY
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Paid Transactions</h3>
          </div>
          <div class="space-y-3">
            <?php foreach ($paidTransactions as $txn): ?>
              <div class="transaction-card paid bg-card border border-border rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:transform hover:-translate-y-1">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <?php if (!empty($txn['guest_name'])): ?>
                      <h3 class="font-bold text-card-foreground text-lg"><?php echo $txn['guest_name']; ?> • Room <?php echo $txn['room_number']; ?></h3>
                      <p class="text-sm text-muted-foreground"><?php echo ucfirst($txn['transaction_type']); ?> • <?php echo $txn['payment_method']; ?></p>
                    <?php else: ?>
                      <h3 class="font-bold text-card-foreground text-lg"><?php echo ucfirst($txn['transaction_type']); ?> • <?php echo $txn['payment_method']; ?></h3>
                    <?php endif; ?>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-lg text-card-foreground"><?php echo formatCurrencyPhpPeso($txn['amount'], 2); ?></p>
                    <p class="text-xs text-muted-foreground"><?php echo date('M d, Y H:i', strtotime($txn['transaction_date'])); ?></p>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="h-4 w-4 text-success"></i>
                    <span class="text-sm text-success font-medium">Payment Completed</span>
                  </div>
                  <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-success/10 text-success">PAID</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </main>

    <!-- Payment Processing Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden">
      <div class="bg-card text-card-foreground border border-border rounded-lg p-3 sm:p-4 w-full max-w-lg mx-2 sm:mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
          <h2 class="text-base sm:text-lg font-bold">Process Payment</h2>
          <button id="closePaymentModal" class="text-muted-foreground hover:text-foreground">
            <i data-lucide="x" class="h-4 w-4 sm:h-5 sm:w-5"></i>
          </button>
        </div>
        
        <!-- Guest Information - Responsive Layout -->
        <div class="mb-2 sm:mb-3 p-2 sm:p-3 bg-muted rounded-md">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 sm:gap-2 text-xs sm:text-sm">
            <p><strong>Guest:</strong> <span id="modalGuestName"></span></p>
            <p><strong>Room:</strong> <span id="modalRoomNo"></span></p>
            <p class="col-span-1 sm:col-span-2"><strong>Balance:</strong> <span id="modalBalance"></span></p>
          </div>
          <div id="guestBillingInfo" class="mt-1 sm:mt-2 pt-1 sm:pt-2 border-t border-border text-xs">
            <!-- Billing information will be populated by JavaScript -->
          </div>
        </div>

        <!-- Payment Form - Responsive Layout -->
        <form id="paymentForm" class="space-y-2 sm:space-y-2">
          <div>
            <label class="text-xs text-muted-foreground">Payment Method</label>
            <div id="paymentMethodGroup" class="mt-1 grid grid-cols-2 gap-1">
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="paymentMethod" value="Cash" />
                <span class="truncate">Cash</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="paymentMethod" value="Card" />
                <span class="truncate">Card</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="paymentMethod" value="GCash" />
                <span class="truncate">GCash</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="paymentMethod" value="Bank Transfer" />
                <span class="truncate">Bank Transfer</span>
              </label>
            </div>
          </div>

          <div class="mt-1 sm:mt-2">
            <label class="text-xs text-muted-foreground">Amount Received (₱)</label>
            <input type="number" id="amountReceived" step="0.01" min="0" required
                   class="h-7 sm:h-8 w-full rounded-md border bg-background px-2 text-sm"
                   placeholder="Enter amount received...">
            <p id="amountError" class="mt-1 text-xs text-danger hidden"></p>
          </div>

          <div id="changeSection" class="p-1.5 sm:p-2 bg-primary/10 rounded-md hidden opacity-0 transform scale-95 transition-all duration-200">
            <p class="text-xs"><strong>Change:</strong> <span id="changeAmount" class="text-primary text-sm">₱0.00</span></p>
          </div>

          <div id="bankOptions" class="mt-1 sm:mt-2 hidden opacity-0 transform scale-95 transition-all duration-200">
            <label class="text-xs text-muted-foreground">Bank Service</label>
            <div class="mt-2 grid grid-cols-2 gap-1">
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="bankService" value="BPI" />
                <span class="truncate">BPI</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="bankService" value="BDO" />
                <span class="truncate">BDO</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="bankService" value="Metrobank" />
                <span class="truncate">Metrobank</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs">
                <input type="radio" name="bankService" value="UnionBank" />
                <span class="truncate">UnionBank</span>
              </label>
              <label class="flex items-center gap-1 rounded-md border border-border bg-background px-1.5 sm:px-2 py-1 sm:py-1.5 cursor-pointer hover:bg-muted transition-colors text-xs col-span-2">
                <input type="radio" name="bankService" value="Others" />
                <span class="truncate">Others</span>
              </label>
            </div>
            <p class="mt-1 text-xs text-muted-foreground">Ensure the transfer reference number is added in the notes section.</p>
            <p id="bankError" class="mt-1 text-xs text-danger hidden">Please select a bank service.</p>
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Payment Reference/Notes</label>
            <textarea id="paymentNotes" rows="2" class="h-7 sm:h-8 w-full rounded-md border bg-background px-2 text-sm"
                      placeholder="Transaction reference, notes..."></textarea>
          </div>

          <div class="flex gap-1 sm:gap-2 pt-2 sm:pt-3">
            <button type="submit" class="flex-1 h-7 sm:h-8 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 text-sm">
              Confirm Payment
            </button>
            <button type="button" id="cancelPayment" class="flex-1 h-7 sm:h-8 rounded-md border hover:bg-muted text-sm">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 rounded-lg bg-green-500 px-4 py-2 text-white shadow-lg opacity-0 transition-opacity duration-300 z-50">
      <span id="toastMessage">Operation completed successfully!</span>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
    
    <script>
      // Billing data (fetched from database)
      const billingData = <?php echo json_encode($billingData); ?>;

      // Helper functions
      function formatCurrency(val) {
        return '₱' + Number(val).toLocaleString();
      }

      function showToast(message) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');
        setTimeout(() => {
          toast.classList.remove('opacity-100');
          toast.classList.add('opacity-0');
        }, 3000);
      }

      // Filter functionality
      function applyFilters() {
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;
        const status = document.getElementById('statusFilter').value;
        const method = document.getElementById('methodFilter').value;
        const search = document.getElementById('searchGuest').value.trim().toLowerCase();

        // In a real app, this would make an AJAX request to filter server-side data
        // For now, we'll just show a toast notification
        showToast('Filters applied successfully!');
      }

      function clearFilters() {
        document.getElementById('fromDate').value = '';
        document.getElementById('toDate').value = '';
        document.getElementById('statusFilter').value = 'All';
        document.getElementById('methodFilter').value = 'All';
        document.getElementById('searchGuest').value = '';
        showToast('Filters cleared!');
      }


      // Payment processing functionality
      let currentPaymentData = {};

      function openPaymentModal(guest, room, balance, reservationId) {
        currentPaymentData = { guest, room, balance, reservationId };

        document.getElementById('modalGuestName').textContent = guest;
        document.getElementById('modalRoomNo').textContent = room;
        document.getElementById('modalBalance').textContent = formatCurrency(balance);

        // Fetch and display guest billing information
        fetchGuestBillingInfo(guest, reservationId);

        // Reset form
        document.getElementById('paymentForm').reset();
        document.getElementById('changeAmount').textContent = '₱0.00';
        document.getElementById('amountError').classList.add('hidden');
        document.getElementById('bankError').classList.add('hidden');
        toggleChangeSection(false);
        toggleBankOptions(false);

        // Show modal
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
      }

      function fetchGuestBillingInfo(guestName, reservationId) {
        // Extract guest ID from reservation ID (assuming guest name contains ID or we need to fetch it)
        // For now, we'll make a request to get guest billing info by reservation
        fetch(`<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/api/billing/guest-info?reservation_id=${reservationId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.billing_info) {
              displayGuestBillingInfo(data.billing_info);
            } else {
              // Show default info if no data available
              displayGuestBillingInfo(null);
            }
          })
          .catch(error => {
            console.error('Error fetching guest billing info:', error);
            displayGuestBillingInfo(null);
          });
      }

      function displayGuestBillingInfo(billingInfo) {
        const billingContainer = document.getElementById('guestBillingInfo');
        if (!billingContainer) return;

        if (billingInfo && billingInfo.paid_count > 0) {
          billingContainer.innerHTML = `
            <p class="text-xs"><strong>Total:</strong> <span class="text-success font-semibold">${formatCurrency(billingInfo.total_paid)}</span></p>
            <p class="text-xs"><strong>Payments:</strong> <span class="font-semibold">${billingInfo.paid_count}</span></p>
            ${billingInfo.tier ? `<p class="text-xs"><strong>Tier:</strong> <span class="inline-flex items-center px-1 py-0.5 rounded text-xs font-medium ${getTierBadgeClass(billingInfo.tier)}">${billingInfo.tier}</span></p>` : ''}
          `;
        } else {
          billingContainer.innerHTML = `
            <p class="text-xs text-muted-foreground">No payment history</p>
          `;
        }
      }

      function getTierBadgeClass(tier) {
        return {
          'PLATINUM': 'bg-purple-100 text-purple-800',
          'GOLD': 'bg-yellow-100 text-yellow-800',
          'SILVER': 'bg-gray-100 text-gray-800',
          'NORMAL': 'bg-blue-100 text-blue-800'
        }[tier] || 'bg-blue-100 text-blue-800';
      }

      function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('flex');
        currentPaymentData = {};
      }

      function calculateChange() {
        const balance = currentPaymentData.balance || 0;
        const received = parseFloat(document.getElementById('amountReceived').value) || 0;
        const change = received - balance;
        
        document.getElementById('changeAmount').textContent = formatCurrency(Math.max(0, change));
        
        // Change color based on amount (theme-friendly semantic classes)
        const changeElement = document.getElementById('changeAmount');
        if (change < 0) {
          changeElement.className = 'text-danger';
        } else if (change > 0) {
          changeElement.className = 'text-success';
        } else {
          changeElement.className = 'text-primary';
        }
      }

      function toggleChangeSection(show) {
        const el = document.getElementById('changeSection');
        if (show) {
          el.classList.remove('hidden', 'opacity-0', 'scale-95');
          el.classList.add('opacity-100', 'scale-100');
        } else {
          el.classList.add('hidden');
          el.classList.remove('opacity-100', 'scale-100');
          el.classList.add('opacity-0', 'scale-95');
        }
      }

      function toggleBankOptions(show) {
        const el = document.getElementById('bankOptions');
        if (show) {
          el.classList.remove('hidden', 'opacity-0', 'scale-95');
          el.classList.add('opacity-100', 'scale-100');
        } else {
          el.classList.add('hidden');
          el.classList.remove('opacity-100', 'scale-100');
          el.classList.add('opacity-0', 'scale-95');
          // clear selected bank
          document.querySelectorAll('input[name="bankService"]').forEach(r => r.checked = false);
        }
      }

      function getSelectedPaymentMethod() {
        const checked = document.querySelector('input[name="paymentMethod"]:checked');
        return checked ? checked.value : '';
      }

      function validateAmountAndOptions() {
        const method = getSelectedPaymentMethod();
        const balance = Number(currentPaymentData.balance || 0);
        const amountInput = document.getElementById('amountReceived');
        const amount = parseFloat(amountInput.value || '0');
        const amountError = document.getElementById('amountError');
        const bankError = document.getElementById('bankError');

        amountError.classList.add('hidden');
        bankError.classList.add('hidden');

        // Default allowances
        let valid = true;

        if (method === 'Cash') {
          // Any amount >= balance; show change section
          toggleChangeSection(true);
          toggleBankOptions(false);
          amountInput.min = '0';
          if (amount < balance) {
            // allow but show negative change via calculateChange; still valid submission allowed if they intend partial? Requirement says >= balance
            if (amount < balance) {
              amountError.textContent = 'For cash payments, amount must be at least the outstanding balance.';
              amountError.classList.remove('hidden');
              valid = false;
            }
          }
        } else if (method === 'Card' || method === 'GCash') {
          toggleChangeSection(false);
          toggleBankOptions(false);
          // Only exact amount allowed
          if (!isNaN(amount) && amount !== balance) {
            amountError.textContent = 'For card or GCash payments, please enter the exact amount.';
            amountError.classList.remove('hidden');
            valid = false;
          }
        } else if (method === 'Bank Transfer') {
          toggleChangeSection(false);
          toggleBankOptions(true);
          // Require bank selection
          const selectedBank = document.querySelector('input[name="bankService"]:checked');
          if (!selectedBank) {
            bankError.classList.remove('hidden');
            valid = false;
          }
          if (!isNaN(amount) && amount !== balance) {
            amountError.textContent = 'For bank transfers, please enter the exact amount.';
            amountError.classList.remove('hidden');
            valid = false;
          }
        } else {
          toggleChangeSection(false);
          toggleBankOptions(false);
          valid = false; // no method selected
        }

        return valid;
      }

      // Event listeners
      document.addEventListener('DOMContentLoaded', function() {
        // Process Payment buttons
        document.querySelectorAll('.process-payment-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const guest = this.getAttribute('data-guest');
            const room = this.getAttribute('data-room');
            const balance = parseFloat(this.getAttribute('data-balance'));
            const reservationId = this.getAttribute('data-reservation-id');

            openPaymentModal(guest, room, balance, reservationId);
          });
        });

        // Payment modal controls
        document.getElementById('closePaymentModal').addEventListener('click', closePaymentModal);
        document.getElementById('cancelPayment').addEventListener('click', closePaymentModal);

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
          if (e.target === this) {
            closePaymentModal();
          }
        });

        // Calculate change / validate when amount received changes
        document.getElementById('amountReceived').addEventListener('input', function() {
          const method = getSelectedPaymentMethod();
          if (method === 'Cash') calculateChange();
          validateAmountAndOptions();
        });

        // Handle method selection changes
        document.getElementById('paymentMethodGroup').addEventListener('change', function() {
          validateAmountAndOptions();
        });

        // Handle bank option changes
        document.getElementById('bankOptions').addEventListener('change', function() {
          validateAmountAndOptions();
        });

        // Handle payment form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
          e.preventDefault();
          // Validate method, amount, and bank options
          const valid = validateAmountAndOptions();
          if (!valid) return;

          const method = getSelectedPaymentMethod();
          const bankSelected = document.querySelector('input[name="bankService"]:checked');
          const bankService = bankSelected ? bankSelected.value : '';

          const formData = new FormData();
          formData.append('folio_id', currentPaymentData.reservationId);
          formData.append('method', method);
          formData.append('amount', document.getElementById('amountReceived').value);
          formData.append('notes', document.getElementById('paymentNotes').value);
          if (method === 'Bank Transfer' && bankService) {
            formData.append('bank_service', bankService);
          }

          fetch('process_payment.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              closePaymentModal();
              showToast('✅ Payment processed successfully!');
              setTimeout(() => {
                window.location.reload();
              }, 1500);
            } else {
              showToast('❌ Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showToast('❌ Error processing payment');
          });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && !document.getElementById('paymentModal').classList.contains('hidden')) {
            closePaymentModal();
          }
        });
      });
    </script>
  </body>
  </html>


