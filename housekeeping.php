<?php 
  // Ensure session/auth is initialized before any output
  require_once __DIR__ . '/includes/db.php'; 
  require_once __DIR__ . '/includes/housekeeping.php';
  requireAuth(['admin','receptionist']); 
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
    <title>Housekeeping - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Housekeeping - Inn Nexus Hotel Management System" />
    <meta name="description" content="Housekeeping task management for Inn Nexus. Track cleaning tasks, maintenance issues, and completion rates in real-time." />
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    
    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <script src="./public/js/hotel-sync.js"></script>
    
    <style>
      .task-card {
        transition: all 0.2s ease-in-out;
      }
      .task-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      }
      .priority-urgent {
        border-left: 4px solid #ef4444;
      }
      .priority-high {
        border-left: 4px solid #f59e0b;
      }
      .priority-normal {
        border-left: 4px solid #6b7280;
      }
      /* Force cleaning cards to use orange left border like maintenance */
      .cleaning-left-border { border-left: 4px solid #f59e0b !important; }
      .maintenance-left-border { border-left: 4px solid #ef4444 !important; }
      
    </style>
  </head>
  <body class="min-h-screen bg-background hk-page">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Housekeeping Dashboard</h1>
          <p class="text-muted-foreground">Track cleaning and maintenance tasks in real-time</p>
        </div>
        <button id="refreshBtn" class="h-9 px-4 rounded-md bg-primary text-primary-foreground hover:bg-primary/90">
          <i data-lucide="refresh-cw" class="h-4 w-4 inline mr-2"></i>
          Refresh
        </button>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-muted-foreground">Pending Tasks</p>
            <i data-lucide="circle" class="h-4 w-4 text-muted-foreground"></i>
          </div>
          <p class="text-2xl font-bold" id="statPending">0</p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-muted-foreground">In Progress</p>
            <i data-lucide="loader" class="h-4 w-4 text-warning"></i>
          </div>
          <p class="text-2xl font-bold" id="statInProgress">0</p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-muted-foreground">Completed Today</p>
            <i data-lucide="check-circle" class="h-4 w-4 text-success"></i>
          </div>
          <p class="text-2xl font-bold" id="statCompleted">0</p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-muted-foreground">Efficiency Rate</p>
            <i data-lucide="trending-up" class="h-4 w-4 text-success"></i>
          </div>
          <p class="text-2xl font-bold" id="statEfficiency">0%</p>
        </div>
      </div>

      <!-- Task Sections -->
      <div class="housekeeping-columns">
        <div class="status-column pending">
          <h2>🧹 Pending Tasks</h2>
          <div class="status-content" id="pendingTasks">
            <p class="text-center text-muted-foreground py-6 text-sm">Loading...</p>
          </div>
        </div>

        <div class="status-column in-progress">
          <h2>⚙️ In Progress</h2>
          <div class="status-content" id="inProgressTasks">
            <p class="text-center text-muted-foreground py-6 text-sm">Loading...</p>
          </div>
        </div>

        <div class="status-column completed">
          <h2>✅ Completed</h2>
          <div class="status-content" id="completedTasks">
            <p class="text-center text-muted-foreground py-6 text-sm">Loading...</p>
          </div>
        </div>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();

      (function() {
        'use strict';

        let tasks = [];
        let stats = {};

        // Priority colors
        const priorityClasses = {
          'normal': 'priority-normal bg-muted text-muted-foreground',
          'high': 'priority-high bg-warning/10 text-warning border border-warning/20',
          'urgent': 'priority-urgent bg-destructive/10 text-destructive border border-destructive/20'
        };

        // Initialize
        async function init() {
          await hotelSync.init();
          
          // Listen for updates
          hotelSync.onHousekeepingUpdate(handleTasksUpdate);
          hotelSync.onStatsUpdate(handleStatsUpdate);
          
          // Setup refresh button
          document.getElementById('refreshBtn')?.addEventListener('click', () => {
            hotelSync.init();
          });

          // Initial render
          tasks = hotelSync.getTasks();
          stats = hotelSync.getStats();
          renderAll();
        }

        // Handle tasks update
        function handleTasksUpdate(updatedTasks) {
          tasks = updatedTasks;
          renderTasks();
        }

        // Handle stats update
        function handleStatsUpdate(updatedStats) {
          stats = updatedStats;
          renderStats();
        }

        // Render everything
        function renderAll() {
          renderStats();
          renderTasks();
        }

        // Render statistics
        function renderStats() {
          document.getElementById('statPending').textContent = stats.pending || 0;
          document.getElementById('statInProgress').textContent = stats.in_progress || 0;
          document.getElementById('statCompleted').textContent = stats.completed || 0;
          document.getElementById('statEfficiency').textContent = (stats.efficiency || 0) + '%';
        }

        // Render tasks
        function renderTasks() {
          // Merge all tasks by workflow status only
          // Some legacy tasks may have status 'maintenance' – treat them as pending
          const groupedTasks = {
            pending: tasks.filter(t => t.status === 'pending' || t.status === 'maintenance'),
            'in-progress': tasks.filter(t => t.status === 'in-progress'),
            completed: tasks.filter(t => t.status === 'completed')
          };

          // Counts removed from headers per design update

          // Render each section (maintenance tasks are part of lists above already)
          renderTaskSection('pendingTasks', groupedTasks.pending, 'pending');
          renderTaskSection('inProgressTasks', groupedTasks['in-progress'], 'in-progress');
          renderTaskSection('completedTasks', groupedTasks.completed, 'completed');
        }

        // Render task section
        function renderTaskSection(containerId, tasks, status) {
          const container = document.getElementById(containerId);
          if (!container) return;

          if (tasks.length === 0) {
            container.innerHTML = '<p class="text-center text-muted-foreground py-8">No tasks</p>';
            return;
          }

          container.innerHTML = '';
          const grid = document.createElement('div');
          grid.className = 'grid gap-2';
          tasks.forEach(task => {
            const card = createTaskCard(task, status);
            grid.appendChild(card);
          });
          container.appendChild(grid);
          // Re-render icons inside newly created elements
          if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
          }
        }

        // Create task card
        function createTaskCard(task, status) {
          const card = document.createElement('div');
          const typeLabel = ((task.task_type || '').toLowerCase() === 'maintenance' || task.status === 'maintenance') ? 'maintenance' : 'cleaning';
          card.className = 'room-card';

          const typeClasses = {
            maintenance: 'bg-red-500/10 text-red-400 border border-red-500/20',
            cleaning: 'bg-orange-500/10 text-orange-400 border border-orange-500/20'
          };
          const typeBadge = `
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs ${typeClasses[typeLabel] || ''}">
              ${typeLabel}
            </span>
          `;

          const guestInfo = task.guest_name ? `
            <p class="text-sm text-muted-foreground">Guest: ${task.guest_name}</p>
          ` : '';

          // Notes removed from card per design request

          const buttons = status === 'pending' ? `
            <button class=\"icon-btn btn-start-task\" title=\"Start Task\" aria-label=\"Start Task\" data-task-id=\"${task.id}\">\n              <i data-lucide=\"play\"></i>\n            </button>
          ` : status === 'in-progress' ? `
            <button class=\"icon-btn success btn-complete-task\" title=\"Mark Complete\" aria-label=\"Mark Complete\" data-task-id=\"${task.id}\">\n              <i data-lucide=\"check\"></i>\n            </button>
          ` : '';

          card.innerHTML = `
            <div class="flex items-start justify-between mb-2">
              <div>
                <h3>Room ${task.room_number}</h3>
                <p>${task.room_type || 'Standard'} • Floor ${task.floor_number || '—'}</p>
                ${guestInfo}
              </div>
              ${typeBadge}
            </div>
            <div class=\"card-actions\">${buttons}</div>
          `;

          const startBtn = card.querySelector('.btn-start-task');
          const completeBtn = card.querySelector('.btn-complete-task');

          if (startBtn) {
            startBtn.addEventListener('click', () => handleStartTask(task.id));
          }

          if (completeBtn) {
            completeBtn.addEventListener('click', () => handleCompleteTask(task.id));
          }

          return card;
        }

        // Handle start task
        async function handleStartTask(taskId) {
          const task = tasks.find(t => t.id === taskId);
          if (!task) {
            console.error('Task not found:', taskId);
            return;
          }

          // Disable the button to prevent double-clicks
          const button = document.querySelector(`[data-task-id="${taskId}"].btn-start-task`);
          if (button) {
            button.disabled = true;
            button.textContent = 'Starting...';
          }

          console.log('Starting task:', taskId, task);
          const success = await hotelSync.updateTask(taskId, 'in-progress');
          
          if (success) {
            console.log('Task started successfully');
            // Force immediate refresh
            await new Promise(resolve => setTimeout(resolve, 500));
            await hotelSync.init();
          } else {
            console.error('Failed to start task');
            // Re-enable button on failure
            if (button) {
              button.disabled = false;
              button.textContent = 'Start Task';
            }
          }
        }

        // Handle complete task
        async function handleCompleteTask(taskId) {
          const task = tasks.find(t => t.id === taskId);
          if (!task) {
            console.error('Task not found:', taskId);
            return;
          }

          // Disable the button to prevent double-clicks
          const button = document.querySelector(`[data-task-id="${taskId}"].btn-complete-task`);
          if (button) {
            button.disabled = true;
            button.textContent = 'Completing...';
          }

          console.log('Completing task:', taskId, task);
          const success = await hotelSync.updateTask(taskId, 'completed');
          
          if (success) {
            console.log('Task completed successfully');
            // Force immediate refresh
            await new Promise(resolve => setTimeout(resolve, 500));
            await hotelSync.init();
          } else {
            console.error('Failed to complete task');
            // Re-enable button on failure
            if (button) {
              button.disabled = false;
              button.textContent = 'Mark Complete';
            }
          }
        }

        // Start when ready
        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', init);
        } else {
          init();
        }
      })();
    </script>
  </body>
</html>

