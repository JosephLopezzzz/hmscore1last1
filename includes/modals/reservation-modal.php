<?php
// Reservation Modal Component
// Converted from TypeScript to PHP

// Sample data (in a real application, this would come from database)
$roomTypes = [
    ['id' => 'single', 'name' => 'Single Room', 'baseRate' => 1500, 'maxOccupancy' => 1],
    ['id' => 'double', 'name' => 'Double Room', 'baseRate' => 2000, 'maxOccupancy' => 2],
    ['id' => 'suite', 'name' => 'Suite', 'baseRate' => 3500, 'maxOccupancy' => 4],
    ['id' => 'deluxe', 'name' => 'Deluxe Room', 'baseRate' => 2500, 'maxOccupancy' => 3]
];

$availableRooms = [
    ['id' => '1', 'number' => '101', 'type' => 'single', 'rate' => 1500, 'status' => 'available'],
    ['id' => '2', 'number' => '102', 'type' => 'single', 'rate' => 1500, 'status' => 'available'],
    ['id' => '3', 'number' => '201', 'type' => 'double', 'rate' => 2000, 'status' => 'available'],
    ['id' => '4', 'number' => '202', 'type' => 'double', 'rate' => 2000, 'status' => 'available'],
    ['id' => '5', 'number' => '301', 'type' => 'suite', 'rate' => 3500, 'status' => 'available']
];

$services = [
    ['id' => '1', 'name' => 'Breakfast', 'price' => 250, 'type' => 'meal'],
    ['id' => '2', 'name' => 'Airport Transfer', 'price' => 500, 'type' => 'transport'],
    ['id' => '3', 'name' => 'Spa Service', 'price' => 800, 'type' => 'wellness'],
    ['id' => '4', 'name' => 'Laundry Service', 'price' => 150, 'type' => 'housekeeping']
];

// Helper functions
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getRoomTypeDisplay($type) {
    $displays = [
        'single' => 'Single Room',
        'double' => 'Double Room',
        'suite' => 'Suite',
        'deluxe' => 'Deluxe Room'
    ];
    
    return $displays[$type] ?? ucfirst($type);
}

function getServiceIcon($type) {
    $icons = [
        'meal' => 'utensils',
        'transport' => 'car',
        'wellness' => 'heart',
        'housekeeping' => 'home',
        'other' => 'plus'
    ];
    
    return $icons[$type] ?? 'plus';
}
?>

<!-- Reservation Modal -->
<div id="reservationModal" class="hidden fixed inset-0 bg-black/60 z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-card text-card-foreground rounded-lg shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col border">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-border flex-shrink-0">
                <h2 class="text-lg font-semibold text-card-foreground">New Reservation</h2>
                <button id="closeReservationModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 min-h-0">
                <!-- Progress Steps -->
                <div class="flex items-center justify-center mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-medium">1</div>
                            <span class="ml-2 text-sm font-medium text-primary">Search</span>
                        </div>
                        <div class="w-8 h-0.5 bg-muted"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-muted text-muted-foreground flex items-center justify-center text-sm font-medium">2</div>
                            <span class="ml-2 text-sm font-medium text-muted-foreground">Customer</span>
                        </div>
                        <div class="w-8 h-0.5 bg-muted"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-muted text-muted-foreground flex items-center justify-center text-sm font-medium">3</div>
                            <span class="ml-2 text-sm font-medium text-muted-foreground">Services</span>
                        </div>
                        <div class="w-8 h-0.5 bg-muted"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-muted text-muted-foreground flex items-center justify-center text-sm font-medium">4</div>
                            <span class="ml-2 text-sm font-medium text-muted-foreground">Payment</span>
                        </div>
                        <div class="w-8 h-0.5 bg-muted"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-muted text-muted-foreground flex items-center justify-center text-sm font-medium">5</div>
                            <span class="ml-2 text-sm font-medium text-muted-foreground">Confirm</span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Search -->
                <div id="searchStep" class="space-y-6">
                    <h3 class="text-lg font-semibold text-card-foreground">Search Available Rooms</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Check-in Date</label>
                            <input type="date" id="checkInDate" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Check-out Date</label>
                            <input type="date" id="checkOutDate" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Number of Guests</label>
                            <input type="number" id="guestCount" min="1" max="10" value="1" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Room Type</label>
                            <select id="roomTypeFilter" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">All Types</option>
                                <?php foreach ($roomTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button id="searchRoomsBtn" class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                        <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                        Search Available Rooms
                    </button>
                </div>

                <!-- Step 2: Customer -->
                <div id="customerStep" class="hidden space-y-6">
                    <h3 class="text-lg font-semibold text-card-foreground">Customer Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">First Name</label>
                            <input type="text" id="firstName" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter first name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Last Name</label>
                            <input type="text" id="lastName" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter last name">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Email</label>
                            <input type="email" id="email" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter email">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-card-foreground mb-2">Phone</label>
                            <input type="tel" id="phone" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter phone number">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Special Requests</label>
                        <textarea id="specialRequests" rows="3" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Any special requests or notes..."></textarea>
                    </div>
                </div>

                <!-- Step 3: Services -->
                <div id="servicesStep" class="hidden space-y-6">
                    <h3 class="text-lg font-semibold text-card-foreground">Additional Services</h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($services as $service): ?>
                        <div class="flex items-center justify-between p-4 border border-border rounded-lg hover:bg-muted/50 transition-colors">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" id="service_<?php echo $service['id']; ?>" class="w-4 h-4 text-primary border-border rounded focus:ring-primary">
                                <i data-lucide="<?php echo getServiceIcon($service['type']); ?>" class="w-5 h-5 text-muted-foreground"></i>
                                <div>
                                    <div class="font-medium text-card-foreground"><?php echo $service['name']; ?></div>
                                    <div class="text-sm text-muted-foreground"><?php echo ucfirst($service['type']); ?> Service</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-card-foreground"><?php echo formatCurrency($service['price']); ?></div>
                                <div class="text-sm text-muted-foreground">per service</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 4: Payment -->
                <div id="paymentStep" class="hidden space-y-6">
                    <h3 class="text-lg font-semibold text-card-foreground">Payment Information</h3>
                    
                    <div class="bg-muted/50 p-4 rounded-lg">
                        <h4 class="font-medium text-card-foreground mb-4">Reservation Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Room Rate (3 nights)</span>
                                <span class="text-card-foreground">₱6,000.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Additional Services</span>
                                <span class="text-card-foreground">₱1,250.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Tax (12%)</span>
                                <span class="text-card-foreground">₱870.00</span>
                            </div>
                            <div class="border-t border-border pt-2 mt-2">
                                <div class="flex justify-between font-semibold">
                                    <span class="text-card-foreground">Total Amount</span>
                                    <span class="text-card-foreground">₱8,120.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Payment Method</label>
                        <select id="paymentMethod" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select payment method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-card-foreground mb-2">Payment Status</label>
                        <select id="paymentStatus" class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial Payment</option>
                        </select>
                    </div>
                </div>

                <!-- Step 5: Confirm -->
                <div id="confirmStep" class="hidden space-y-6">
                    <h3 class="text-lg font-semibold text-card-foreground">Confirm Reservation</h3>
                    
                    <div class="bg-muted/50 p-6 rounded-lg">
                        <h4 class="font-semibold text-card-foreground mb-4">Reservation Details</h4>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Guest Name:</span>
                                <span class="text-card-foreground">John Doe</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Room:</span>
                                <span class="text-card-foreground">Room 201 - Double Room</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Check-in:</span>
                                <span class="text-card-foreground">Dec 15, 2023</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Check-out:</span>
                                <span class="text-card-foreground">Dec 18, 2023</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Amount:</span>
                                <span class="text-card-foreground font-semibold">₱8,120.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="confirmTerms" class="w-4 h-4 text-primary border-border rounded focus:ring-primary">
                        <label for="confirmTerms" class="text-sm text-card-foreground">
                            I agree to the terms and conditions and confirm this reservation.
                        </label>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-8">
                    <button id="prevStepBtn" class="px-6 py-2 border border-border rounded-md text-card-foreground hover:bg-muted transition-colors hidden">
                        Previous
                    </button>
                    <button id="nextStepBtn" class="px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors ml-auto">
                        Next
                    </button>
                    <button id="confirmReservationBtn" class="px-6 py-2 bg-success text-success-foreground rounded-md hover:bg-success/90 transition-colors hidden">
                        Confirm Reservation
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-border p-4 flex-shrink-0">
                <div class="flex justify-end">
                    <button id="closeReservationModalFooterBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservationModal = document.getElementById('reservationModal');
    const closeReservationModalBtn = document.getElementById('closeReservationModalBtn');
    const closeReservationModalFooterBtn = document.getElementById('closeReservationModalFooterBtn');
    const prevStepBtn = document.getElementById('prevStepBtn');
    const nextStepBtn = document.getElementById('nextStepBtn');
    const confirmReservationBtn = document.getElementById('confirmReservationBtn');
    
    const steps = ['searchStep', 'customerStep', 'servicesStep', 'paymentStep', 'confirmStep'];
    let currentStep = 0;

    // Step navigation
    function showStep(stepIndex) {
        steps.forEach((stepId, index) => {
            const stepElement = document.getElementById(stepId);
            if (stepElement) {
                if (index === stepIndex) {
                    stepElement.classList.remove('hidden');
                } else {
                    stepElement.classList.add('hidden');
                }
            }
        });
        
        // Update progress indicators
        updateProgressIndicators(stepIndex);
        
        // Update navigation buttons
        updateNavigationButtons(stepIndex);
    }

    function updateProgressIndicators(stepIndex) {
        const progressSteps = document.querySelectorAll('.flex.items-center.justify-center.mb-8 .flex.items-center');
        progressSteps.forEach((step, index) => {
            const circle = step.querySelector('div');
            const text = step.querySelector('span');
            
            if (index <= stepIndex) {
                circle.classList.remove('bg-muted', 'text-muted-foreground');
                circle.classList.add('bg-primary', 'text-primary-foreground');
                text.classList.remove('text-muted-foreground');
                text.classList.add('text-primary');
            } else {
                circle.classList.remove('bg-primary', 'text-primary-foreground');
                circle.classList.add('bg-muted', 'text-muted-foreground');
                text.classList.add('text-muted-foreground');
                text.classList.remove('text-primary');
            }
        });
    }

    function updateNavigationButtons(stepIndex) {
        if (prevStepBtn) {
            if (stepIndex > 0) {
                prevStepBtn.classList.remove('hidden');
            } else {
                prevStepBtn.classList.add('hidden');
            }
        }
        
        if (nextStepBtn) {
            if (stepIndex < steps.length - 1) {
                nextStepBtn.classList.remove('hidden');
            } else {
                nextStepBtn.classList.add('hidden');
            }
        }
        
        if (confirmReservationBtn) {
            if (stepIndex === steps.length - 1) {
                confirmReservationBtn.classList.remove('hidden');
            } else {
                confirmReservationBtn.classList.add('hidden');
            }
        }
    }

    // Navigation event listeners
    if (prevStepBtn) {
        prevStepBtn.addEventListener('click', function() {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    }

    if (nextStepBtn) {
        nextStepBtn.addEventListener('click', function() {
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        });
    }

    if (confirmReservationBtn) {
        confirmReservationBtn.addEventListener('click', function() {
            // Handle reservation confirmation
            console.log('Confirming reservation...');
            alert('Reservation confirmed successfully!');
            closeReservationModal();
        });
    }

    // Close modal functions
    function closeReservationModal() {
        if (reservationModal) {
            reservationModal.classList.add('hidden');
            // Reset to first step
            currentStep = 0;
            showStep(currentStep);
        }
    }

    if (closeReservationModalBtn) {
        closeReservationModalBtn.addEventListener('click', closeReservationModal);
    }

    if (closeReservationModalFooterBtn) {
        closeReservationModalFooterBtn.addEventListener('click', closeReservationModal);
    }

    // Close modal when clicking outside
    if (reservationModal) {
        reservationModal.addEventListener('click', function(e) {
            if (e.target === reservationModal) {
                closeReservationModal();
            }
        });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reservationModal && !reservationModal.classList.contains('hidden')) {
            closeReservationModal();
        }
    });

    // Make modal functions globally available
    window.openReservationModal = function() {
        if (reservationModal) {
            reservationModal.classList.remove('hidden');
            // Initialize icons for the modal
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        }
    };

    window.openReservationModalForGuest = function(guestId, guestName, guestData) {
        if (reservationModal) {
            reservationModal.classList.remove('hidden');
            // Pre-fill guest information if provided
            if (guestName) {
                const nameParts = guestName.split(' ');
                const firstName = document.getElementById('firstName');
                const lastName = document.getElementById('lastName');
                if (firstName && lastName) {
                    firstName.value = nameParts[0] || '';
                    lastName.value = nameParts.slice(1).join(' ') || '';
                }
            }
            // Initialize icons for the modal
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        }
    };

    window.closeReservationModal = closeReservationModal;
});
</script>
