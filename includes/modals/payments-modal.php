<?php
// Payments Modal Component
// Converted from TypeScript to PHP

// Sample payments data (in a real application, this would come from database)
$payments = [
    [
        'id' => 'PAY-1001',
        'invoiceNumber' => 'INV-2023-001',
        'guestName' => 'John Smith',
        'roomNumber' => '420',
        'amount' => 1250.75,
        'date' => '2023-06-15T14:30:00Z',
        'status' => 'completed',
        'method' => 'credit_card',
        'processedBy' => 'Sarah K.',
        'notes' => 'Paid in full'
    ],
    [
        'id' => 'PAY-1002',
        'invoiceNumber' => 'INV-2023-002',
        'guestName' => 'Emma Wilson',
        'roomNumber' => '315',
        'amount' => 980.50,
        'date' => '2023-06-16T09:15:00Z',
        'status' => 'pending',
        'method' => 'bank_transfer',
        'processedBy' => 'Mike R.',
        'notes' => 'Awaiting confirmation'
    ],
    [
        'id' => 'PAY-1003',
        'invoiceNumber' => 'INV-2023-003',
        'guestName' => 'David Brown',
        'roomNumber' => '201',
        'amount' => 750.00,
        'date' => '2023-06-16T16:45:00Z',
        'status' => 'failed',
        'method' => 'credit_card',
        'processedBy' => 'Sarah K.',
        'notes' => 'Card declined'
    ]
];

// Helper functions
function getPaymentStatusBadge($status) {
    $styles = [
        'pending' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-gray-100 text-gray-800'
    ];
    
    $icons = [
        'pending' => 'clock',
        'completed' => 'check-circle',
        'failed' => 'x-circle',
        'refunded' => 'rotate-ccw'
    ];
    
    $style = $styles[$status] ?? 'bg-gray-100 text-gray-800';
    $icon = $icons[$status] ?? 'help-circle';
    $displayStatus = ucfirst($status);
    
    return "<span class=\"inline-flex items-center text-xs px-2 py-1 rounded-full {$style}\">
                <i data-lucide=\"{$icon}\" class=\"w-3 h-3 mr-1\"></i>
                {$displayStatus}
            </span>";
}

function getPaymentMethodIcon($method) {
    $icons = [
        'credit_card' => 'credit-card',
        'debit_card' => 'credit-card',
        'cash' => 'banknote',
        'bank_transfer' => 'building',
        'other' => 'more-horizontal'
    ];
    
    return $icons[$method] ?? 'help-circle';
}

function getPaymentMethodDisplay($method) {
    $displays = [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'other' => 'Other'
    ];
    
    return $displays[$method] ?? ucfirst($method);
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDateTime($dateTime) {
    return date('M j, Y g:i A', strtotime($dateTime));
}
?>

<!-- Payments Modal -->
<div id="paymentsModal" class="hidden fixed inset-0 bg-black/60 z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-card text-card-foreground rounded-lg shadow-2xl w-full max-w-6xl max-h-[95vh] flex flex-col border">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-border flex-shrink-0">
                <h2 class="text-lg font-semibold text-card-foreground">Payment Management</h2>
                <button id="closePaymentsModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 min-h-0">
                <!-- Tab Navigation -->
                <div class="flex space-x-1 mb-6 bg-muted p-1 rounded-lg">
                    <button id="transactionsTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground transition-colors">
                        Transactions
                    </button>
                    <button id="newPaymentTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md text-muted-foreground hover:text-foreground transition-colors">
                        New Payment
                    </button>
                </div>

                <!-- Transactions Tab Content -->
                <div id="transactionsContent" class="space-y-4">
                    <!-- Search and Filter Bar -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-6">
                        <div class="flex-1 relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground"></i>
                            <input type="text" id="paymentSearchInput" placeholder="Search payments..." 
                                   class="w-full pl-10 pr-4 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="flex gap-2">
                            <select id="statusFilter" class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            <button class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                                <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="bg-card border border-border rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Payment ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Guest</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <?php foreach ($payments as $payment): ?>
                                    <tr class="hover:bg-muted/50 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-card-foreground"><?php echo $payment['id']; ?></div>
                                            <div class="text-xs text-muted-foreground"><?php echo $payment['invoiceNumber']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-card-foreground"><?php echo $payment['guestName']; ?></div>
                                            <div class="text-xs text-muted-foreground">Room <?php echo $payment['roomNumber']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-card-foreground"><?php echo formatCurrency($payment['amount']); ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i data-lucide="<?php echo getPaymentMethodIcon($payment['method']); ?>" class="w-4 h-4 text-muted-foreground mr-2"></i>
                                                <span class="text-sm text-card-foreground"><?php echo getPaymentMethodDisplay($payment['method']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?php echo getPaymentStatusBadge($payment['status']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-card-foreground"><?php echo formatDateTime($payment['date']); ?></div>
                                            <div class="text-xs text-muted-foreground">by <?php echo $payment['processedBy']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <button onclick="viewPayment('<?php echo $payment['id']; ?>')" 
                                                        class="text-primary hover:text-primary/80 transition-colors" title="View">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="editPayment('<?php echo $payment['id']; ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="deletePayment('<?php echo $payment['id']; ?>')" 
                                                        class="text-red-600 hover:text-red-800 transition-colors" title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- New Payment Tab Content -->
                <div id="newPaymentContent" class="hidden">
                    <div class="max-w-2xl mx-auto">
                        <h3 class="text-lg font-semibold text-card-foreground mb-6">Process New Payment</h3>
                        
                        <form class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">Guest Name</label>
                                    <input type="text" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" 
                                           placeholder="Enter guest name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">Room Number</label>
                                    <input type="text" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" 
                                           placeholder="Room number">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">Amount</label>
                                    <input type="number" step="0.01" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" 
                                           placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-card-foreground mb-2">Payment Method</label>
                                    <select class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Select method</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-card-foreground mb-2">Notes</label>
                                <textarea rows="3" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" 
                                          placeholder="Additional notes..."></textarea>
                            </div>
                            
                            <div class="flex justify-end space-x-4">
                                <button type="button" class="px-6 py-2 border border-border rounded-md text-card-foreground hover:bg-muted transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" class="px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                                    Process Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-border p-4 flex-shrink-0">
                <div class="flex justify-end">
                    <button id="closePaymentsModalFooterBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentsModal = document.getElementById('paymentsModal');
    const closePaymentsModalBtn = document.getElementById('closePaymentsModalBtn');
    const closePaymentsModalFooterBtn = document.getElementById('closePaymentsModalFooterBtn');
    const transactionsTab = document.getElementById('transactionsTab');
    const newPaymentTab = document.getElementById('newPaymentTab');
    const transactionsContent = document.getElementById('transactionsContent');
    const newPaymentContent = document.getElementById('newPaymentContent');

    // Tab switching functionality
    if (transactionsTab && newPaymentTab && transactionsContent && newPaymentContent) {
        transactionsTab.addEventListener('click', function() {
            transactionsTab.classList.add('bg-primary', 'text-primary-foreground');
            transactionsTab.classList.remove('text-muted-foreground');
            newPaymentTab.classList.remove('bg-primary', 'text-primary-foreground');
            newPaymentTab.classList.add('text-muted-foreground');
            transactionsContent.classList.remove('hidden');
            newPaymentContent.classList.add('hidden');
        });

        newPaymentTab.addEventListener('click', function() {
            newPaymentTab.classList.add('bg-primary', 'text-primary-foreground');
            newPaymentTab.classList.remove('text-muted-foreground');
            transactionsTab.classList.remove('bg-primary', 'text-primary-foreground');
            transactionsTab.classList.add('text-muted-foreground');
            newPaymentContent.classList.remove('hidden');
            transactionsContent.classList.add('hidden');
        });
    }

    // Close modal functions
    function closePaymentsModal() {
        if (paymentsModal) {
            paymentsModal.classList.add('hidden');
        }
    }

    if (closePaymentsModalBtn) {
        closePaymentsModalBtn.addEventListener('click', closePaymentsModal);
    }

    if (closePaymentsModalFooterBtn) {
        closePaymentsModalFooterBtn.addEventListener('click', closePaymentsModal);
    }

    // Close modal when clicking outside
    if (paymentsModal) {
        paymentsModal.addEventListener('click', function(e) {
            if (e.target === paymentsModal) {
                closePaymentsModal();
            }
        });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && paymentsModal && !paymentsModal.classList.contains('hidden')) {
            closePaymentsModal();
        }
    });

    // Payment action functions
    window.viewPayment = function(paymentId) {
        console.log('View payment:', paymentId);
        // Add view payment functionality here
    };

    window.editPayment = function(paymentId) {
        console.log('Edit payment:', paymentId);
        // Add edit payment functionality here
    };

    window.deletePayment = function(paymentId) {
        if (confirm('Are you sure you want to delete this payment?')) {
            console.log('Delete payment:', paymentId);
            // Add delete payment functionality here
        }
    };

    // Make modal functions globally available
    window.openPaymentsModal = function() {
        if (paymentsModal) {
            paymentsModal.classList.remove('hidden');
            // Initialize icons for the modal
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        }
    };

    window.closePaymentsModal = closePaymentsModal;
});
</script>
