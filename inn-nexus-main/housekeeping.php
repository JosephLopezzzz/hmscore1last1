<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Housekeeping - Core 1 PMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; requireAuth(['admin','receptionist']); ?>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php
      $tasks = [
        [ 'room' => '101', 'status' => 'pending', 'priority' => 'high', 'guest' => 'J. Smith', 'notes' => 'Extra towels requested' ],
        [ 'room' => '102', 'status' => 'in-progress', 'priority' => 'normal', 'guest' => 'E. Williams', 'notes' => '' ],
        [ 'room' => '103', 'status' => 'completed', 'priority' => 'normal', 'guest' => null, 'notes' => '' ],
        [ 'room' => '104', 'status' => 'pending', 'priority' => 'high', 'guest' => null, 'notes' => 'Deep clean required' ],
        [ 'room' => '201', 'status' => 'pending', 'priority' => 'urgent', 'guest' => 'S. Johnson', 'notes' => 'VIP guest' ],
        [ 'room' => '202', 'status' => 'completed', 'priority' => 'normal', 'guest' => null, 'notes' => '' ],
        [ 'room' => '203', 'status' => 'maintenance', 'priority' => 'urgent', 'guest' => null, 'notes' => 'AC not working' ],
        [ 'room' => '204', 'status' => 'in-progress', 'priority' => 'high', 'guest' => null, 'notes' => '' ],
      ];
      $statusConfig = [
        'pending' => [ 'icon' => 'circle', 'label' => 'Pending', 'color' => 'text-muted-foreground' ],
        'in-progress' => [ 'icon' => 'alert-circle', 'label' => 'In Progress', 'color' => 'text-warning' ],
        'completed' => [ 'icon' => 'check-circle-2', 'label' => 'Completed', 'color' => 'text-success' ],
        'maintenance' => [ 'icon' => 'wrench', 'label' => 'Maintenance', 'color' => 'text-destructive' ],
      ];
      $priorityColors = [
        'normal' => 'bg-muted text-muted-foreground',
        'high' => 'bg-warning/10 text-warning border border-warning/20',
        'urgent' => 'bg-destructive/10 text-destructive border border-destructive/20',
      ];
    ?>
    <main class="container mx-auto px-4 py-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-3xl font-bold">Housekeeping</h1>
          <p class="text-muted-foreground">Room cleaning and maintenance tasks</p>
        </div>
      </div>

      <?php
        $pendingCount = count(array_filter($tasks, fn($t) => $t['status'] === 'pending'));
        $inProgressCount = count(array_filter($tasks, fn($t) => $t['status'] === 'in-progress'));
        $completedCount = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
      ?>
      <div class="grid gap-6 mb-6 md:grid-cols-4">
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Pending Tasks</p>
          <p class="text-2xl font-bold"><?php echo $pendingCount; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">In Progress</p>
          <p class="text-2xl font-bold"><?php echo $inProgressCount; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Completed Today</p>
          <p class="text-2xl font-bold"><?php echo $completedCount; ?></p>
        </div>
        <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-4">
          <p class="text-sm text-muted-foreground mb-1">Efficiency Rate</p>
          <p class="text-2xl font-bold">94%</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <?php foreach ($statusConfig as $status => $config): ?>
          <?php $statusTasks = array_values(array_filter($tasks, fn($t) => $t['status'] === $status)); ?>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
              <i data-lucide="<?php echo $config['icon']; ?>" class="h-5 w-5 <?php echo $config['color']; ?>"></i>
              <h3 class="text-lg font-semibold"><?php echo $config['label']; ?></h3>
              <span class="ml-auto inline-flex items-center rounded-md border px-2 py-0.5 text-xs"><?php echo count($statusTasks); ?> rooms</span>
            </div>
            <div class="space-y-3">
              <?php foreach ($statusTasks as $task): ?>
                <div class="p-4 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
                  <div class="flex items-start justify-between mb-2">
                    <div>
                      <p class="font-bold text-lg">Room <?php echo $task['room']; ?></p>
                      <?php if (!empty($task['guest'])): ?>
                        <p class="text-sm text-muted-foreground">Guest: <?php echo $task['guest']; ?></p>
                      <?php endif; ?>
                    </div>
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs <?php echo $priorityColors[$task['priority']]; ?>"><?php echo $task['priority']; ?></span>
                  </div>
                  <?php if (!empty($task['notes'])): ?>
                    <p class="text-sm text-muted-foreground mb-3"><?php echo $task['notes']; ?></p>
                  <?php endif; ?>
                  <?php if ($status === 'pending'): ?>
                    <button class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm w-full">Start Task</button>
                  <?php elseif ($status === 'in-progress'): ?>
                    <button class="h-8 px-3 rounded-md border text-sm w-full">Mark Complete</button>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
              <?php if (count($statusTasks) === 0): ?>
                <p class="text-center text-muted-foreground py-8">No tasks</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>
  </body>
  </html>


