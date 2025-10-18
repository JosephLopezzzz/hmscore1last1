<?php
// Stock Tracking - Monitor stock levels, alerts, and history
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin', 'manager', 'staff']);

$tab = $_GET['tab'] ?? 'overview';

// Handle stock adjustment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $item_id = (int)$_POST['item_id'];
    $adjustment_type = $_POST['adjustment_type'];
    $quantity = (int)$_POST['quantity'];
    $notes = trim($_POST['notes']);
    $user_id = $_SESSION['user_id'];

    if (empty($item_id) || empty($adjustment_type) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Get database connection
            $pdo = getPdo();
            if (!$pdo) {
                $error = "Database connection failed.";
            } else {
                // Get current stock
                $stmt = $pdo->prepare("SELECT current_stock FROM inventory_stock WHERE item_id = ?");
                $stmt->execute([$item_id]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock === false) {
                    $error = "Item not found.";
                } else {
                    $new_stock = $current_stock;
                    $operation_type = '';

                    switch ($adjustment_type) {
                        case 'stock_in':
                            $new_stock += $quantity;
                            $operation_type = 'stock_in';
                            break;
                        case 'stock_out':
                            if ($current_stock < $quantity) {
                                $error = "Insufficient stock for this adjustment.";
                            } else {
                                $new_stock -= $quantity;
                                $operation_type = 'stock_out';
                            }
                            break;
                        case 'adjustment':
                            $new_stock = $quantity;
                            $operation_type = 'adjustment';
                            break;
                        case 'expiry':
                            if ($current_stock < $quantity) {
                                $error = "Insufficient stock for expiry adjustment.";
                            } else {
                                $new_stock -= $quantity;
                                $operation_type = 'expiry';
                            }
                            break;
                        case 'damage':
                            if ($current_stock < $quantity) {
                                $error = "Insufficient stock for damage adjustment.";
                            } else {
                                $new_stock -= $quantity;
                                $operation_type = 'damage';
                            }
                            break;
                    }

                    if (!isset($error)) {
                        // Update stock
                        $stmt = $pdo->prepare("UPDATE inventory_stock SET current_stock = ?, updated_by = ?, last_updated = NOW() WHERE item_id = ?");
                        $stmt->execute([$new_stock, $user_id, $item_id]);

                        // Record history
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_stock_history (item_id, operation_type, quantity, previous_stock, new_stock, notes, performed_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$item_id, $operation_type, abs($quantity), $current_stock, $new_stock, $notes, $user_id]);

                        $success = "Stock adjustment completed successfully.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Error updating stock: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en" class="">
<head>
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stock Tracking - Core 1 Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
</head>
<body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Stock Tracking</h1>
                <p class="text-muted-foreground"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <a href="inventory.php" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90">
                    <i data-lucide="arrow-left"></i>
                    Back to Dashboard
                </a>
                <button onclick="showAdjustmentModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                    <i data-lucide="edit"></i>
                    Adjust Stock
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="border-b">
            <nav class="flex space-x-8">
                <button onclick="setTab('overview')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'overview' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Overview
                </button>
                <button onclick="setTab('alerts')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'alerts' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Stock Alerts
                </button>
                <button onclick="setTab('history')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'history' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Stock History
                </button>
                <button onclick="setTab('adjustments')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'adjustments' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Quick Adjustments
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            <?php if ($tab === 'overview'): ?>
                <!-- Stock Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-card rounded-lg border">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold">Current Stock Levels</h2>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <input type="text" id="stock-search" placeholder="Search items..." class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <table class="w-full">
                                    <thead class="sticky top-0 bg-card">
                                        <tr class="border-b">
                                            <th class="text-left p-2">Item</th>
                                            <th class="text-right p-2">Current</th>
                                            <th class="text-right p-2">Available</th>
                                            <th class="text-right p-2">Min Level</th>
                                            <th class="text-center p-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stock-overview">
                                        <?php
                                        $pdo = getPdo();
                                        $stock_items = [];
                                        if ($pdo) {
                                            $stock_items = $pdo->query("
                                                SELECT i.name, i.sku, s.current_stock, s.available_stock, i.minimum_stock_level, c.name as category_name
                                                FROM inventory_items i
                                                JOIN inventory_stock s ON i.id = s.item_id
                                                JOIN inventory_categories c ON i.category_id = c.id
                                                WHERE i.is_active = 1
                                                ORDER BY i.name
                                            ")->fetchAll();
                                        }

                                        foreach ($stock_items as $item):
                                            $status = 'normal';
                                            if ($item['current_stock'] == 0) $status = 'out';
                                            elseif ($item['current_stock'] <= $item['minimum_stock_level']) $status = 'low';
                                        ?>
                                            <tr class="border-b hover:bg-muted/50">
                                                <td class="p-2">
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                                        <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($item['sku']); ?></div>
                                                    </div>
                                                </td>
                                                <td class="p-2 text-right font-medium"><?php echo number_format($item['current_stock']); ?></td>
                                                <td class="p-2 text-right"><?php echo number_format($item['available_stock']); ?></td>
                                                <td class="p-2 text-right"><?php echo number_format($item['minimum_stock_level']); ?></td>
                                                <td class="p-2 text-center">
                                                    <?php if ($status === 'out'): ?>
                                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">OUT OF STOCK</span>
                                                    <?php elseif ($status === 'low'): ?>
                                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">LOW STOCK</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">IN STOCK</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card rounded-lg border">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold">Stock Summary by Category</h2>
                        </div>
                        <div class="p-6">
                            <?php
                            $pdo = getPdo();
                            $category_summary = [];
                            if ($pdo) {
                                $category_summary = $pdo->query("
                                    SELECT c.name, COUNT(i.id) as item_count, SUM(s.current_stock) as total_stock, SUM(s.current_stock * i.unit_cost) as total_value
                                    FROM inventory_categories c
                                    LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
                                    LEFT JOIN inventory_stock s ON i.id = s.item_id
                                    WHERE c.is_active = 1
                                    GROUP BY c.id, c.name
                                    ORDER BY total_stock DESC
                                ")->fetchAll();
                            }
                            ?>
                            <div class="space-y-4">
                                <?php foreach ($category_summary as $category): ?>
                                    <div class="flex items-center justify-between p-4 border rounded-lg">
                                        <div>
                                            <h3 class="font-medium"><?php echo htmlspecialchars($category['name']); ?></h3>
                                            <p class="text-sm text-muted-foreground"><?php echo $category['item_count']; ?> items</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium"><?php echo number_format($category['total_stock'] ?? 0); ?> units</p>
                                            <p class="text-sm text-muted-foreground">$<?php echo number_format($category['total_value'] ?? 0, 2); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'alerts'): ?>
                <!-- Stock Alerts -->
                <div class="bg-card rounded-lg border">
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-semibold">Stock Alerts</h2>
                    </div>
                    <div class="p-6">
                        <?php
                        $pdo = getPdo();
                        $alerts = [];
                        if ($pdo) {
                            $alerts = $pdo->query("
                                SELECT i.name, i.sku, s.current_stock, s.available_stock, i.minimum_stock_level, i.maximum_stock_level, c.name as category_name,
                                       CASE
                                           WHEN s.current_stock = 0 THEN 'out_of_stock'
                                           WHEN s.current_stock <= i.minimum_stock_level THEN 'low_stock'
                                           WHEN i.maximum_stock_level IS NOT NULL AND s.current_stock >= i.maximum_stock_level THEN 'overstock'
                                           ELSE 'normal'
                                       END as alert_type
                                FROM inventory_items i
                                JOIN inventory_stock s ON i.id = s.item_id
                                JOIN inventory_categories c ON i.category_id = c.id
                                WHERE i.is_active = 1 AND (
                                    s.current_stock = 0 OR
                                    s.current_stock <= i.minimum_stock_level OR
                                    (i.maximum_stock_level IS NOT NULL AND s.current_stock >= i.maximum_stock_level)
                                )
                                ORDER BY
                                    CASE
                                        WHEN s.current_stock = 0 THEN 1
                                        WHEN s.current_stock <= i.minimum_stock_level THEN 2
                                        ELSE 3
                                    END, s.current_stock ASC
                            ")->fetchAll();
                        }
                        ?>

                        <?php if (empty($alerts)): ?>
                            <div class="text-center py-8">
                                <i data-lucide="check-circle" class="h-12 w-12 text-green-500 mx-auto mb-4"></i>
                                <p class="text-muted-foreground">No stock alerts at this time. All items are within acceptable levels.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($alerts as $alert): ?>
                                    <div class="flex items-center justify-between p-4 border rounded-lg <?php
                                        echo match($alert['alert_type']) {
                                            'out_of_stock' => 'border-red-200 bg-red-50',
                                            'low_stock' => 'border-yellow-200 bg-yellow-50',
                                            'overstock' => 'border-blue-200 bg-blue-50',
                                            default => ''
                                        };
                                    ?>">
                                        <div class="flex items-center gap-3">
                                            <div class="<?php
                                                echo match($alert['alert_type']) {
                                                    'out_of_stock' => 'w-3 h-3 bg-red-500 rounded-full',
                                                    'low_stock' => 'w-3 h-3 bg-yellow-500 rounded-full',
                                                    'overstock' => 'w-3 h-3 bg-blue-500 rounded-full',
                                                    default => 'w-3 h-3 bg-gray-500 rounded-full'
                                                };
                                            ?>"></div>
                                            <div>
                                                <h3 class="font-medium"><?php echo htmlspecialchars($alert['name']); ?></h3>
                                                <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($alert['sku']); ?> • <?php echo htmlspecialchars($alert['category_name']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <?php if ($alert['alert_type'] === 'out_of_stock'): ?>
                                                <p class="font-medium text-red-600">Out of Stock</p>
                                                <p class="text-sm text-muted-foreground">0 units available</p>
                                            <?php elseif ($alert['alert_type'] === 'low_stock'): ?>
                                                <p class="font-medium text-yellow-600">Low Stock</p>
                                                <p class="text-sm text-muted-foreground"><?php echo $alert['current_stock']; ?> / <?php echo $alert['minimum_stock_level']; ?> min</p>
                                            <?php elseif ($alert['alert_type'] === 'overstock'): ?>
                                                <p class="font-medium text-blue-600">Overstock</p>
                                                <p class="text-sm text-muted-foreground"><?php echo $alert['current_stock']; ?> / <?php echo $alert['maximum_stock_level']; ?> max</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'history'): ?>
                <!-- Stock History -->
                <div class="bg-card rounded-lg border">
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-semibold">Stock Movement History</h2>
                    </div>
                    <div class="p-6">
                        <div class="mb-4 flex gap-4">
                            <input type="text" id="history-search" placeholder="Search items..." class="flex-1 px-3 py-2 border rounded-md">
                            <select id="operation-filter" class="px-3 py-2 border rounded-md">
                                <option value="">All Operations</option>
                                <option value="stock_in">Stock In</option>
                                <option value="stock_out">Stock Out</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="expiry">Expiry</option>
                                <option value="damage">Damage</option>
                            </select>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="sticky top-0 bg-card">
                                    <tr class="border-b">
                                        <th class="text-left p-2">Date</th>
                                        <th class="text-left p-2">Item</th>
                                        <th class="text-center p-2">Operation</th>
                                        <th class="text-right p-2">Quantity</th>
                                        <th class="text-right p-2">Previous</th>
                                        <th class="text-right p-2">New</th>
                                        <th class="text-left p-2">Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="stock-history">
                                    <?php
                                    $pdo = getPdo();
                                    $history = [];
                                    if ($pdo) {
                                        $history = $pdo->query("
                                            SELECT h.*, i.name, i.sku, u.email as user_email
                                            FROM inventory_stock_history h
                                            JOIN inventory_items i ON h.item_id = i.id
                                            LEFT JOIN users u ON h.performed_by = u.id
                                            ORDER BY h.created_at DESC
                                            LIMIT 100
                                        ")->fetchAll();
                                    }

                                    foreach ($history as $record):
                                    ?>
                                        <tr class="border-b hover:bg-muted/50">
                                            <td class="p-2 text-sm">
                                                <?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?>
                                            </td>
                                            <td class="p-2">
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($record['name']); ?></div>
                                                    <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($record['sku']); ?></div>
                                                </div>
                                            </td>
                                            <td class="p-2 text-center">
                                                <span class="px-2 py-1 rounded text-xs <?php
                                                    echo match($record['operation_type']) {
                                                        'stock_in' => 'bg-green-100 text-green-800',
                                                        'stock_out' => 'bg-red-100 text-red-800',
                                                        'adjustment' => 'bg-blue-100 text-blue-800',
                                                        'expiry' => 'bg-orange-100 text-orange-800',
                                                        'damage' => 'bg-red-200 text-red-900',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $record['operation_type'])); ?>
                                                </span>
                                            </td>
                                            <td class="p-2 text-right font-medium <?php
                                                echo match($record['operation_type']) {
                                                    'stock_in' => 'text-green-600',
                                                    'stock_out' => 'text-red-600',
                                                    'adjustment' => 'text-blue-600',
                                                    'expiry' => 'text-orange-600',
                                                    'damage' => 'text-red-800',
                                                    default => ''
                                                };
                                            ?>">
                                                <?php echo $record['operation_type'] === 'stock_in' ? '+' : ($record['operation_type'] === 'stock_out' ? '-' : ''); ?>
                                                <?php echo number_format($record['quantity']); ?>
                                            </td>
                                            <td class="p-2 text-right"><?php echo number_format($record['previous_stock']); ?></td>
                                            <td class="p-2 text-right font-medium"><?php echo number_format($record['new_stock']); ?></td>
                                            <td class="p-2 text-sm">
                                                <?php if ($record['notes']): ?>
                                                    <?php echo htmlspecialchars($record['notes']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted-foreground">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'adjustments'): ?>
                <!-- Quick Adjustments -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-card rounded-lg border">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold">Quick Stock Adjustment</h2>
                        </div>
                        <div class="p-6">
                            <form method="POST">
                                <input type="hidden" name="action" value="adjust">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-1">Item *</label>
                                    <select name="item_id" id="adjustment-item" required class="w-full px-3 py-2 border rounded-md">
                                        <option value="">Select an item</option>
                                        <?php
                                        $pdo = getPdo();
                                        $items = [];
                                        if ($pdo) {
                                            $items = $pdo->query("
                                                SELECT i.id, i.name, i.sku, s.current_stock
                                                FROM inventory_items i
                                                JOIN inventory_stock s ON i.id = s.item_id
                                                WHERE i.is_active = 1
                                                ORDER BY i.name
                                            ")->fetchAll();
                                        }

                                        foreach ($items as $item):
                                        ?>
                                            <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['sku']; ?>) - Current: <?php echo $item['current_stock']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-1">Adjustment Type *</label>
                                    <select name="adjustment_type" id="adjustment-type" required class="w-full px-3 py-2 border rounded-md">
                                        <option value="">Select adjustment type</option>
                                        <option value="stock_in">Stock In (+)</option>
                                        <option value="stock_out">Stock Out (-)</option>
                                        <option value="adjustment">Set Stock (=)</option>
                                        <option value="expiry">Expiry (-)</option>
                                        <option value="damage">Damage (-)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-1">Quantity *</label>
                                    <input type="number" name="quantity" id="adjustment-quantity" min="1" required class="w-full px-3 py-2 border rounded-md">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-1">Notes</label>
                                    <textarea name="notes" rows="3" placeholder="Reason for adjustment..." class="w-full px-3 py-2 border rounded-md"></textarea>
                                </div>

                                <button type="submit" class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                                    Apply Adjustment
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-card rounded-lg border">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold">Recent Adjustments</h2>
                        </div>
                        <div class="p-6">
                            <?php
                            $pdo = getPdo();
                            $recent_adjustments = [];
                            if ($pdo) {
                                $recent_adjustments = $pdo->query("
                                    SELECT h.*, i.name, i.sku
                                    FROM inventory_stock_history h
                                    JOIN inventory_items i ON h.item_id = i.id
                                    WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                    ORDER BY h.created_at DESC
                                    LIMIT 10
                                ")->fetchAll();
                            }
                            ?>

                            <?php if (empty($recent_adjustments)): ?>
                                <p class="text-muted-foreground text-center py-4">No recent adjustments</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($recent_adjustments as $adjustment): ?>
                                        <div class="flex items-center justify-between p-3 border rounded">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full <?php
                                                    echo match($adjustment['operation_type']) {
                                                        'stock_in' => 'bg-green-500',
                                                        'stock_out' => 'bg-red-500',
                                                        'adjustment' => 'bg-blue-500',
                                                        'expiry' => 'bg-orange-500',
                                                        'damage' => 'bg-red-600',
                                                        default => 'bg-gray-500'
                                                    };
                                                ?>"></div>
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($adjustment['name']); ?></p>
                                                    <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($adjustment['sku']); ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-medium <?php
                                                    echo match($adjustment['operation_type']) {
                                                        'stock_in' => 'text-green-600',
                                                        'stock_out' => 'text-red-600',
                                                        'adjustment' => 'text-blue-600',
                                                        'expiry' => 'text-orange-600',
                                                        'damage' => 'text-red-800',
                                                        default => ''
                                                    };
                                                ?>">
                                                    <?php echo $adjustment['operation_type'] === 'stock_in' ? '+' : ($adjustment['operation_type'] === 'stock_out' ? '-' : ''); ?>
                                                    <?php echo $adjustment['quantity']; ?>
                                                </p>
                                                <p class="text-sm text-muted-foreground"><?php echo date('H:i', strtotime($adjustment['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stock Adjustment Modal (for quick adjustments) -->
        <div id="adjustment-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-card p-6 rounded-lg w-full max-w-md">
                <?php
                // Get items for the modal dropdown
                $pdo = getPdo();
                $items = [];
                if ($pdo) {
                    $items = $pdo->query("
                        SELECT i.id, i.name, i.sku, s.current_stock
                        FROM inventory_items i
                        JOIN inventory_stock s ON i.id = s.item_id
                        WHERE i.is_active = 1
                        ORDER BY i.name
                    ")->fetchAll();
                }
                ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="adjust">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Quick Stock Adjustment</h2>
                        <button type="button" onclick="hideAdjustmentModal()" class="text-muted-foreground hover:text-foreground">
                            <i data-lucide="x" class="h-6 w-6"></i>
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Item *</label>
                        <select name="item_id" required class="w-full px-3 py-2 border rounded-md">
                            <option value="">Select an item</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo $item['current_stock']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Adjustment Type *</label>
                        <select name="adjustment_type" required class="w-full px-3 py-2 border rounded-md">
                            <option value="">Select type</option>
                            <option value="stock_in">Stock In (+)</option>
                            <option value="stock_out">Stock Out (-)</option>
                            <option value="adjustment">Set Stock (=)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Quantity *</label>
                        <input type="number" name="quantity" min="1" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="hideAdjustmentModal()" class="px-4 py-2 border rounded-md hover:bg-muted">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        window.lucide && window.lucide.createIcons();

        function setTab(tab) {
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);

            // Reload page to show new tab content
            window.location.reload();
        }

        function showAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.remove('hidden');
        }

        function hideAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.add('hidden');
        }

        // Search functionality for stock overview
        document.getElementById('stock-search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#stock-overview tr');

            rows.forEach(row => {
                if (row.cells && row.cells[0]) {
                    const text = row.cells[0].textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        });

        // Search functionality for history
        document.getElementById('history-search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#stock-history tr');

            rows.forEach(row => {
                if (row.cells && row.cells[1]) {
                    const text = row.cells[1].textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        });

        // Filter functionality for history
        document.getElementById('operation-filter')?.addEventListener('change', function(e) {
            const filterValue = e.target.value;
            const rows = document.querySelectorAll('#stock-history tr');

            rows.forEach(row => {
                if (row.cells && row.cells[2]) {
                    const operation = row.cells[2].textContent.toLowerCase().replace(/\s+/g, '_');
                    if (!filterValue || operation.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });

        // Update quantity input based on adjustment type
        document.getElementById('adjustment-type')?.addEventListener('change', function(e) {
            const quantityInput = document.getElementById('adjustment-quantity');
            if (e.target.value === 'adjustment') {
                quantityInput.placeholder = 'Enter new stock level';
            } else {
                quantityInput.placeholder = 'Enter quantity';
            }
        });
    </script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
