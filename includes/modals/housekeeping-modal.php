<?php
// Housekeeping Modal Component
// Converted from TypeScript to PHP

// Sample tasks data (in a real application, this would come from database)
$tasks = [
    [
        'id' => '1',
        'room' => '201',
        'type' => 'cleaning',
        'status' => 'pending',
        'priority' => 'high',
        'assignedTo' => 'Staff 1',
        'dueDate' => '2023-06-15T10:00:00',
        'notes' => 'Guest requested extra towels',
        'createdAt' => '2023-06-14T09:00:00'
    ],
    [
        'id' => '2',
        'room' => '305',
        'type' => 'maintenance',
        'status' => 'in-progress',
        'priority' => 'medium',
        'assignedTo' => 'Staff 2',
        'dueDate' => '2023-06-15T14:00:00',
        'notes' => 'AC not working properly',
        'createdAt' => '2023-06-14T11:30:00'
    ],
    [
        'id' => '3',
        'room' => '102',
        'type' => 'inspection',
        'status' => 'completed',
        'priority' => 'low',
        'assignedTo' => 'Staff 3',
        'dueDate' => '2023-06-14T16:00:00',
        'notes' => 'Routine safety check',
        'createdAt' => '2023-06-14T08:00:00'
    ]
];

// Helper functions
function getPriorityBadge($priority) {
    $styles = [
        'low' => 'bg-green-100 text-green-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        'high' => 'bg-red-100 text-red-800'
    ];
    
    $icons = [
        'low' => '↓',
        'medium' => '→',
        'high' => '↑'
    ];
    
    $style = $styles[$priority] ?? 'bg-gray-100 text-gray-800';
    $icon = $icons[$priority] ?? '?';
    
    return "<span class=\"inline-flex items-center text-xs px-2 py-1 rounded-full {$style}\">
                <span class=\"mr-1\">{$icon}</span>
                {$priority}
            </span>";
}

function getStatusBadge($status) {
    $styles = [
        'pending' => 'bg-orange-100 text-orange-800',
        'in-progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'issue' => 'bg-red-100 text-red-800'
    ];
    
    $icons = [
        'pending' => 'clock',
        'in-progress' => 'clock',
        'completed' => 'check-circle',
        'issue' => 'alert-circle'
    ];
    
    $style = $styles[$status] ?? 'bg-gray-100 text-gray-800';
    $icon = $icons[$status] ?? 'help-circle';
    $displayStatus = ucfirst(str_replace('-', ' ', $status));
    
    return "<span class=\"inline-flex items-center text-xs px-2 py-1 rounded-full {$style}\">
                <i data-lucide=\"{$icon}\" class=\"w-3 h-3 mr-1\"></i>
                {$displayStatus}
            </span>";
}

function formatDateTime($dateTime) {
    return date('M j, Y g:i A', strtotime($dateTime));
}

function getTaskTypeDisplay($type) {
    return ucfirst($type);
}
?>

<!-- Housekeeping Modal -->
<div id="housekeepingModal" class="hidden fixed inset-0 bg-black/60 z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-card text-card-foreground rounded-lg shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col border">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-border flex-shrink-0">
                <h2 class="text-lg font-semibold text-card-foreground">Housekeeping Management</h2>
                <button id="closeHousekeepingModalBtn" class="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6 min-h-0">
                <!-- Tab Navigation -->
                <div class="flex space-x-1 mb-6 bg-muted p-1 rounded-lg">
                    <button id="tasksTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground transition-colors">
                        Tasks
                    </button>
                    <button id="scheduleTab" class="flex-1 py-2 px-4 text-sm font-medium rounded-md text-muted-foreground hover:text-foreground transition-colors">
                        Schedule
                    </button>
                </div>

                <!-- Tasks Tab Content -->
                <div id="tasksContent" class="space-y-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-card-foreground">Active Tasks</h3>
                        <button class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Add Task
                        </button>
                    </div>

                    <!-- Tasks List -->
                    <div class="space-y-3">
                        <?php foreach ($tasks as $task): ?>
                        <div class="task-item p-4 border border-border rounded-lg hover:bg-muted/50 cursor-pointer transition-colors" 
                             data-task-id="<?php echo $task['id']; ?>">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-medium text-card-foreground">Room <?php echo $task['room']; ?> - <?php echo getTaskTypeDisplay($task['type']); ?></h4>
                                    <p class="text-sm text-muted-foreground"><?php echo $task['assignedTo']; ?></p>
                                    <p class="text-xs text-muted-foreground mt-1">Due: <?php echo formatDateTime($task['dueDate']); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <?php echo getPriorityBadge($task['priority']); ?>
                                    <?php echo getStatusBadge($task['status']); ?>
                                </div>
                            </div>
                            <?php if (!empty($task['notes'])): ?>
                            <div class="mt-2 p-2 bg-muted/50 rounded text-sm text-muted-foreground">
                                <?php echo htmlspecialchars($task['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Schedule Tab Content -->
                <div id="scheduleContent" class="hidden">
                    <div class="text-center py-8">
                        <i data-lucide="calendar" class="w-12 h-12 text-muted-foreground mx-auto mb-4"></i>
                        <h3 class="text-lg font-semibold text-card-foreground mb-2">Schedule View</h3>
                        <p class="text-muted-foreground">Schedule functionality coming soon...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-border p-4 flex-shrink-0">
                <div class="flex justify-end">
                    <button id="closeHousekeepingModalFooterBtn" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const housekeepingModal = document.getElementById('housekeepingModal');
    const closeHousekeepingModalBtn = document.getElementById('closeHousekeepingModalBtn');
    const closeHousekeepingModalFooterBtn = document.getElementById('closeHousekeepingModalFooterBtn');
    const tasksTab = document.getElementById('tasksTab');
    const scheduleTab = document.getElementById('scheduleTab');
    const tasksContent = document.getElementById('tasksContent');
    const scheduleContent = document.getElementById('scheduleContent');

    // Tab switching functionality
    if (tasksTab && scheduleTab && tasksContent && scheduleContent) {
        tasksTab.addEventListener('click', function() {
            tasksTab.classList.add('bg-primary', 'text-primary-foreground');
            tasksTab.classList.remove('text-muted-foreground');
            scheduleTab.classList.remove('bg-primary', 'text-primary-foreground');
            scheduleTab.classList.add('text-muted-foreground');
            tasksContent.classList.remove('hidden');
            scheduleContent.classList.add('hidden');
        });

        scheduleTab.addEventListener('click', function() {
            scheduleTab.classList.add('bg-primary', 'text-primary-foreground');
            scheduleTab.classList.remove('text-muted-foreground');
            tasksTab.classList.remove('bg-primary', 'text-primary-foreground');
            tasksTab.classList.add('text-muted-foreground');
            scheduleContent.classList.remove('hidden');
            tasksContent.classList.add('hidden');
        });
    }

    // Task item click handlers
    const taskItems = document.querySelectorAll('.task-item');
    taskItems.forEach(item => {
        item.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            console.log('Task clicked:', taskId);
            // Add task detail functionality here
        });
    });

    // Close modal functions
    function closeHousekeepingModal() {
        if (housekeepingModal) {
            housekeepingModal.classList.add('hidden');
        }
    }

    if (closeHousekeepingModalBtn) {
        closeHousekeepingModalBtn.addEventListener('click', closeHousekeepingModal);
    }

    if (closeHousekeepingModalFooterBtn) {
        closeHousekeepingModalFooterBtn.addEventListener('click', closeHousekeepingModal);
    }

    // Close modal when clicking outside
    if (housekeepingModal) {
        housekeepingModal.addEventListener('click', function(e) {
            if (e.target === housekeepingModal) {
                closeHousekeepingModal();
            }
        });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && housekeepingModal && !housekeepingModal.classList.contains('hidden')) {
            closeHousekeepingModal();
        }
    });

    // Make modal functions globally available
    window.openHousekeepingModal = function() {
        if (housekeepingModal) {
            housekeepingModal.classList.remove('hidden');
            // Initialize icons for the modal
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        }
    };

    window.closeHousekeepingModal = closeHousekeepingModal;
});
</script>
