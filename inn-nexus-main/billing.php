<!doctype html>
<html lang="en">
  <head>
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
      $folios = [
        [ 'id' => 'FOL-001', 'guest' => 'Sarah Johnson', 'room' => '204', 'charges' => 555, 'paid' => 555, 'balance' => 0, 'status' => 'paid' ],
        [ 'id' => 'FOL-002', 'guest' => 'Michael Chen', 'room' => '315', 'charges' => 840, 'paid' => 0, 'balance' => 840, 'status' => 'open' ],
        [ 'id' => 'FOL-003', 'guest' => 'Emma Williams', 'room' => '102', 'charges' => 495, 'paid' => 250, 'balance' => 245, 'status' => 'partial' ],
        [ 'id' => 'FOL-004', 'guest' => 'David Brown', 'room' => '410', 'charges' => 900, 'paid' => 900, 'balance' => 0, 'status' => 'paid' ],
        [ 'id' => 'FOL-005', 'guest' => 'Lisa Anderson', 'room' => '208', 'charges' => 975, 'paid' => 0, 'balance' => 975, 'status' => 'open' ],
      ];
      $recent = [
        [ 'id' => 'TXN-101', 'guest' => 'Sarah Johnson', 'type' => 'Room Charge', 'amount' => 185, 'method' => 'Card', 'time' => '10:30 AM' ],
        [ 'id' => 'TXN-102', 'guest' => 'Emma Williams', 'type' => 'Mini Bar', 'amount' => 45, 'method' => 'Cash', 'time' => '11:15 AM' ],
        [ 'id' => 'TXN-103', 'guest' => 'David Brown', 'type' => 'Restaurant', 'amount' => 120, 'method' => 'Card', 'time' => '12:00 PM' ],
        [ 'id' => 'TXN-104', 'guest' => 'Michael Chen', 'type' => 'Spa Service', 'amount' => 250, 'method' => 'Card', 'time' => '2:30 PM' ],
      ];
      $statusColors = [
        'paid' => 'bg-success/10 text-success border border-success/20',
        'open' => 'bg-warning/10 text-warning border border-warning/20',
        'partial' => 'bg-accent/10 text-accent border border-accent/20',
      ];

      $totalRevenue = array_sum(array_map(fn($f) => $f['charges'], $folios));
      $totalPaid = array_sum(array_map(fn($f) => $f['paid'], $folios));
      $totalOutstanding = array_sum(array_map(fn($f) => $f['balance'], $folios));
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Billing & Payments</h1>
          <p class="text-muted-foreground">Manage guest folios and transactions</p>
        </div>
        <div class="flex gap-3">
          <button id="exportBtn" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm hover:bg-muted">
            <i data-lucide="download" class="h-4 w-4"></i>
            Export CSV
          </button>
        </div>
      </div>


      <div class="grid gap-6 mb-6 md:grid-cols-4">
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
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-primary/10">
              <i data-lucide="trending-up" class="h-5 w-5 text-primary"></i>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Collection Rate</p>
              <p class="text-2xl font-bold">86%</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Guest Folios</h3>
            <button class="inline-flex items-center rounded-md border px-3 py-2 text-sm">View All</button>
          </div>
          <div class="space-y-3">
            <?php foreach ($folios as $folio): ?>
              <div class="p-4 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
                <div class="flex items-start justify-between mb-2">
                  <div>
                    <p class="font-bold"><?php echo $folio['guest']; ?></p>
                    <p class="text-sm text-muted-foreground">Room <?php echo $folio['room']; ?> • <?php echo $folio['id']; ?></p>
                  </div>
                  <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs <?php echo $statusColors[$folio['status']]; ?>"><?php echo $folio['status']; ?></span>
                </div>
                <div class="flex justify-between text-sm mt-3">
                  <span class="text-muted-foreground">Charges: <?php echo formatCurrencyPhpPeso($folio['charges'], 2); ?></span>
                  <span class="text-muted-foreground">Paid: <?php echo formatCurrencyPhpPeso($folio['paid'], 2); ?></span>
                  <span class="font-medium">Balance: <?php echo formatCurrencyPhpPeso($folio['balance'], 2); ?></span>
                </div>
                <?php if ($folio['balance'] > 0): ?>
                  <button class="process-payment-btn h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm w-full mt-3" 
                          data-guest="<?php echo htmlspecialchars($folio['guest']); ?>"
                          data-room="<?php echo htmlspecialchars($folio['room']); ?>"
                          data-balance="<?php echo $folio['balance']; ?>"
                          data-folio-id="<?php echo htmlspecialchars($folio['id']); ?>">
                    Process Payment
                  </button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Recent Transactions</h3>
            <button class="inline-flex items-center rounded-md border px-3 py-2 text-sm">View All</button>
          </div>
          <div class="space-y-3">
            <?php foreach ($recent as $txn): ?>
              <div class="p-4 rounded-lg bg-muted/50">
                <div class="flex items-start justify-between mb-2">
                  <div class="flex-1">
                    <p class="font-medium"><?php echo $txn['guest']; ?></p>
                    <p class="text-sm text-muted-foreground"><?php echo $txn['type']; ?> • <?php echo $txn['method']; ?></p>
                  </div>
                  <div class="text-right">
                    <p class="font-bold"><?php echo formatCurrencyPhpPeso($txn['amount'], 2); ?></p>
                    <p class="text-xs text-muted-foreground"><?php echo $txn['time']; ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </main>

    <!-- Payment Processing Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Process Payment</h2>
          <button id="closePaymentModal" class="text-gray-500 hover:text-gray-700">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>
        
        <!-- Guest Information -->
        <div class="mb-4 p-3 bg-gray-50 rounded-md">
          <p><strong>Guest:</strong> <span id="modalGuestName"></span></p>
          <p><strong>Room No:</strong> <span id="modalRoomNo"></span></p>
          <p><strong>Outstanding Balance:</strong> <span id="modalBalance"></span></p>
        </div>

        <!-- Payment Form -->
        <form id="paymentForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Payment Method</label>
            <select id="paymentMethod" required class="w-full rounded-md border px-3 py-2 text-sm">
              <option value="">Select payment method...</option>
              <option value="Cash">Cash</option>
              <option value="Card">Card</option>
              <option value="GCash">GCash</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Amount Received (₱)</label>
            <input type="number" id="amountReceived" step="0.01" min="0" required 
                   class="w-full rounded-md border px-3 py-2 text-sm" 
                   placeholder="Enter amount received...">
          </div>
          
          <div class="p-3 bg-blue-50 rounded-md">
            <p><strong>Change Due:</strong> <span id="changeAmount" class="text-blue-600">₱0.00</span></p>
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-1">Payment Reference/Notes</label>
            <textarea id="paymentNotes" rows="2" class="w-full rounded-md border px-3 py-2 text-sm" 
                      placeholder="Transaction reference, notes..."></textarea>
          </div>
          
          <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">
              Confirm Payment
            </button>
            <button type="button" id="cancelPayment" class="flex-1 rounded-md border px-4 py-2 text-sm hover:bg-muted">
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
      // Billing data (in a real app, this would come from the server)
      const billingData = <?php echo json_encode([
        ['id' => 'FOL-001', 'guest' => 'Sarah Johnson', 'room' => '204', 'method' => 'Card', 'amount' => 555, 'date' => date('Y-m-d'), 'status' => 'paid'],
        ['id' => 'FOL-002', 'guest' => 'Michael Chen', 'room' => '315', 'method' => 'Cash', 'amount' => 840, 'date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'open'],
        ['id' => 'FOL-003', 'guest' => 'Emma Williams', 'room' => '102', 'method' => 'GCash', 'amount' => 495, 'date' => date('Y-m-d', strtotime('-2 days')), 'status' => 'partial'],
        ['id' => 'FOL-004', 'guest' => 'David Brown', 'room' => '410', 'method' => 'Bank Transfer', 'amount' => 900, 'date' => date('Y-m-d', strtotime('-3 days')), 'status' => 'paid'],
        ['id' => 'FOL-005', 'guest' => 'Lisa Anderson', 'room' => '208', 'method' => 'Card', 'amount' => 975, 'date' => date('Y-m-d', strtotime('-4 days')), 'status' => 'open']
      ]); ?>;

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

      // CSV Export functionality
      function exportCSV() {
        const from = document.getElementById('fromDate').value || 'all';
        const to = document.getElementById('toDate').value || 'all';
        const status = document.getElementById('statusFilter').value;
        const method = document.getElementById('methodFilter').value;
        const search = document.getElementById('searchGuest').value.trim().toLowerCase();

        // Filter data (in a real app, this would be server-side)
        const filtered = billingData.filter(item => {
          let matchDate = true;
          if (from !== 'all') matchDate = item.date >= from;
          if (to !== 'all') matchDate = matchDate && item.date <= to;
          const matchStatus = status === 'All' ? true : item.status === status;
          const matchMethod = method === 'All' ? true : item.method === method;
          const matchSearch = !search ? true : item.guest.toLowerCase().includes(search);
          return matchDate && matchStatus && matchMethod && matchSearch;
        });

        // Build CSV content
        const header = ['Transaction ID', 'Guest Name', 'Room', 'Payment Method', 'Amount', 'Date', 'Status'];
        const rows = filtered.map(r => [r.id, r.guest, r.room, r.method, r.amount, r.date, r.status]);

        // Summary
        const totalRevenue = filtered.reduce((sum, r) => sum + (r.status === 'paid' || r.status === 'partial' ? r.amount : 0), 0);
        const paid = filtered.filter(r => r.status === 'paid').length;
        const partial = filtered.filter(r => r.status === 'partial').length;
        const unpaid = filtered.filter(r => r.status === 'open').length;

        // CSV string
        let csv = header.join(',') + '\n';
        rows.forEach(r => {
          const safe = r.map(cell => `"${String(cell).replace(/"/g,'""')}"`);
          csv += safe.join(',') + '\n';
        });

        // Add summary
        csv += '\n';
        csv += `"Total Revenue","${totalRevenue}"\n`;
        csv += `"Paid Transactions","${paid}"\n`;
        csv += `"Partial Transactions","${partial}"\n`;
        csv += `"Unpaid Transactions","${unpaid}"\n`;

        // Download
        const filename = `BillingReport_${from}_to_${to}.csv`;
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        showToast('✅ Billing report exported successfully!');
      }

      // Payment processing functionality
      let currentPaymentData = {};

      function openPaymentModal(guest, room, balance, folioId) {
        currentPaymentData = { guest, room, balance, folioId };
        
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
        
        // Change color based on amount
        const changeElement = document.getElementById('changeAmount');
        if (change < 0) {
          changeElement.className = 'text-red-600';
        } else if (change > 0) {
          changeElement.className = 'text-green-600';
        } else {
          changeElement.className = 'text-blue-600';
        }
      }

      // Event listeners
      document.addEventListener('DOMContentLoaded', function() {
        // Export functionality
        document.getElementById('exportBtn').addEventListener('click', exportCSV);

        // Process Payment buttons
        document.querySelectorAll('.process-payment-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const guest = this.getAttribute('data-guest');
            const room = this.getAttribute('data-room');
            const balance = parseFloat(this.getAttribute('data-balance'));
            const folioId = this.getAttribute('data-folio-id');
            
            openPaymentModal(guest, room, balance, folioId);
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
          
          const formData = {
            guest: currentPaymentData.guest,
            room: currentPaymentData.room,
            folioId: currentPaymentData.folioId,
            balance: currentPaymentData.balance,
            paymentMethod: document.getElementById('paymentMethod').value,
            amountReceived: parseFloat(document.getElementById('amountReceived').value),
            notes: document.getElementById('paymentNotes').value
          };

          // In a real app, this would make an AJAX request to process the payment
          console.log('Processing payment:', formData);
          
          // Close modal and show success
          closePaymentModal();
          showToast('✅ Payment processed successfully!');
          
          // Reload page to show updated data
          setTimeout(() => {
            window.location.reload();
          }, 1500);
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


