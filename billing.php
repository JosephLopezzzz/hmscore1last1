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
          CONCAT(g.first_name, ' ', g.last_name) as guest_name,
          r.room_id,
          rm.room_number,
          r.check_in_date,
          r.check_out_date,
          rm.rate as amount,
          r.payment_status
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        JOIN guests g ON r.guest_id = g.id
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
        'Pending' => 'bg-warning/10 text-warning border border-warning/20',
        'Failed' => 'bg-danger/10 text-danger border border-danger/20',
        'Refunded' => 'bg-accent/10 text-accent border border-accent/20',
        'Open' => 'bg-warning/10 text-warning border border-warning/20',
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
          CONCAT(g.first_name, ' ', g.last_name) as guest_name,
          rm.room_number,
          r.id as reservation_id
        FROM billing_transactions bt
        LEFT JOIN reservations r ON bt.reservation_id = r.id
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        WHERE bt.status = 'Paid' OR r.payment_status = 'FULLY PAID'
        ORDER BY bt.transaction_date DESC
        LIMIT 10
      ";

      $stmt = $pdo->prepare($paidTransactionsQuery);
      $stmt->execute();
      $paidTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Billing & Payments</h1>
          <p class="text-muted-foreground">Manage guest folios and transactions</p>
        </div>
        <div class="flex gap-3">
        </div>
      </div>


      <div class="grid gap-6 mb-6 md:grid-cols-3">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-accent/10">
              <i data-lucide="dollar-sign" class="h-5 w-5 text-accent"></i>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Total Revenue</p>
              <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso($totalRevenue, 2); ?></p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-success/10">
              <i data-lucide="credit-card" class="h-5 w-5 text-success"></i>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Paid</p>
              <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso($totalPaid, 2); ?></p>
            </div>
          </div>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-warning/10">
              <i data-lucide="file-text" class="h-5 w-5 text-warning"></i>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Outstanding</p>
              <p class="text-2xl font-bold"><?php echo formatCurrencyPhpPeso($totalOutstanding, 2); ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold">Pending Transactions</h3>
          </div>
          <div class="space-y-3">
            <?php foreach ($folios as $folio): ?>
              <div class="transaction-card pending bg-card border border-border rounded-lg p-4 hover:shadow-md transition-all duration-200 hover:transform hover:-translate-y-1">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <h3 class="font-bold text-card-foreground text-lg">Reservation #<?php echo $folio['id']; ?></h3>
                    <p class="text-sm text-muted-foreground"><?php echo $folio['guest_name']; ?> • Room <?php echo $folio['room_number']; ?></p>
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
                    <button class="process-payment-btn inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            data-guest="<?php echo htmlspecialchars($folio['guest_name']); ?>"
                            data-room="<?php echo htmlspecialchars($folio['room_number']); ?>"
                            data-balance="<?php echo $folio['amount'] ?? 0; ?>"
                            data-reservation-id="<?php echo $folio['id']; ?>">
                      <i data-lucide="credit-card" class="h-4 w-4"></i>
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
      <div class="bg-card text-card-foreground border border-border rounded-lg p-6 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Process Payment</h2>
          <button id="closePaymentModal" class="text-muted-foreground hover:text-foreground">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>
        
        <!-- Guest Information -->
        <div class="mb-4 p-3 bg-muted rounded-md">
          <p><strong>Guest:</strong> <span id="modalGuestName"></span></p>
          <p><strong>Room No:</strong> <span id="modalRoomNo"></span></p>
          <p><strong>Outstanding Balance:</strong> <span id="modalBalance"></span></p>
        </div>

        <!-- Payment Form -->
        <form id="paymentForm" class="space-y-3">
          <div>
            <label class="text-xs text-muted-foreground">Payment Method</label>
            <select id="paymentMethod" required class="h-10 w-full rounded-md border bg-background px-3 text-sm">
              <option value="">Select payment method...</option>
              <option value="Cash">Cash</option>
              <option value="Card">Card</option>
              <option value="GCash">GCash</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Amount Received (₱)</label>
            <input type="number" id="amountReceived" step="0.01" min="0" required
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Enter amount received...">
          </div>

          <div class="p-3 bg-primary/10 rounded-md">
            <p><strong>Change Due:</strong> <span id="changeAmount" class="text-primary">₱0.00</span></p>
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Payment Reference/Notes</label>
            <textarea id="paymentNotes" rows="2" class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                      placeholder="Transaction reference, notes..."></textarea>
          </div>

          <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90">
              Confirm Payment
            </button>
            <button type="button" id="cancelPayment" class="flex-1 h-10 rounded-md border hover:bg-muted">
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

        // Reset form
        document.getElementById('paymentForm').reset();
        document.getElementById('changeAmount').textContent = '₱0.00';

        // Show modal
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
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

        // Calculate change when amount received changes
        document.getElementById('amountReceived').addEventListener('input', calculateChange);

        // Handle payment form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
          e.preventDefault();

          const formData = new FormData();
          formData.append('folio_id', currentPaymentData.reservationId);
          formData.append('method', document.getElementById('paymentMethod').value);
          formData.append('amount', document.getElementById('amountReceived').value);
          formData.append('notes', document.getElementById('paymentNotes').value);

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


