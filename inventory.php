<?php
// Inventory Management - Main dashboard for inventory and stock management
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin', 'manager', 'staff']);

$tab = $_GET['tab'] ?? 'dashboard';

// Get dashboard statistics
$stats = [];
if ($pdo = getPdo()) {
    // Total items
    $stats['total_items'] = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1")->fetchColumn();

    // Low stock items
    $stats['low_stock'] = $pdo->query("
        SELECT COUNT(*) FROM inventory_items i
        JOIN inventory_stock s ON i.id = s.item_id
        WHERE i.is_active = 1 AND s.current_stock <= i.minimum_stock_level
    ")->fetchColumn();

    // Out of stock items
    $stats['out_of_stock'] = $pdo->query("
        SELECT COUNT(*) FROM inventory_items i
        JOIN inventory_stock s ON i.id = s.item_id
        WHERE i.is_active = 1 AND s.current_stock = 0
    ")->fetchColumn();

    // Total value
    $stats['total_value'] = $pdo->query("
        SELECT SUM(s.current_stock * i.unit_cost) FROM inventory_items i
        JOIN inventory_stock s ON i.id = s.item_id
        WHERE i.is_active = 1
    ")->fetchColumn();

    // Recent stock movements
    $stats['recent_movements'] = $pdo->query("
        SELECT COUNT(*) FROM inventory_stock_history
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetchColumn();

    // Active purchase orders
    $stats['active_po'] = $pdo->query("
        SELECT COUNT(*) FROM inventory_purchase_orders
        WHERE status IN ('sent', 'confirmed')
    ")->fetchColumn();
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
    <title>Inventory Management - Core 1 Hotel Management System</title>
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
                <h1 class="text-3xl font-bold">Inventory Management</h1>
                <p class="text-muted-foreground"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <button onclick="showAddItemModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors">
                    <i data-lucide="plus"></i>
                    Add Item
                </button>
                <button onclick="showAddCategoryModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 transition-colors">
                    <i data-lucide="folder-plus"></i>
                    Add Category
                </button>
                <button onclick="showAddSupplierModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 transition-colors">
                    <i data-lucide="user-plus"></i>
                    Add Supplier
                </button>
                <a href="stock-tracking.php" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 transition-colors">
                    <i data-lucide="activity"></i>
                    Stock Tracking
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b">
            <nav class="flex space-x-8">
                <button onclick="setTab('dashboard')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'dashboard' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Dashboard
                </button>
                <button onclick="setTab('items')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'items' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Items
                </button>
                <button onclick="setTab('categories')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'categories' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Categories
                </button>
                <button onclick="setTab('suppliers')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'suppliers' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Suppliers
                </button>
                <button onclick="setTab('purchase-orders')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'purchase-orders' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Purchase Orders
                </button>
                <!-- Reports tab hidden -->
                <!-- <button onclick="setTab('reports')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'reports' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'; ?>">
                    Reports
                </button> -->
            </nav>
        </div>

        <!-- Dashboard Tab -->
        <?php if ($tab === 'dashboard'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Total Items Card -->
            <div class="bg-card rounded-lg border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i data-lucide="package" class="h-6 w-6 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Total Items</p>
                        <p class="text-2xl font-bold"><?php echo number_format($stats['total_items'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert Card -->
            <div class="bg-card rounded-lg border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i data-lucide="alert-triangle" class="h-6 w-6 text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Low Stock Items</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['low_stock'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Card -->
            <div class="bg-card rounded-lg border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <i data-lucide="x-circle" class="h-6 w-6 text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Out of Stock</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo number_format($stats['out_of_stock'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Value Card -->
            <div class="bg-card rounded-lg border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i data-lucide="package" class="h-6 w-6 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Total Inventory Value</p>
                        <p class="text-2xl font-bold text-green-600">$<?php echo number_format($stats['total_value'] ?? 0, 2); ?></p>
                        <p class="text-xs text-muted-foreground mt-1">Based on unit costs</p>
                    </div>
                </div>
            </div>

            <!-- Profit Margin Card -->
            <div class="bg-card rounded-lg border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i data-lucide="trending-up" class="h-6 w-6 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-muted-foreground">Avg. Profit Margin</p>
                        <p class="text-2xl font-bold text-purple-600">50%</p>
                        <p class="text-xs text-muted-foreground mt-1">Selling vs unit cost</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Low Stock Items -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Low Stock Alerts</h2>
                </div>
                <div class="p-6">
                    <?php
                    $pdo = getPdo();
                    $low_stock_items = [];
                    if ($pdo) {
                        $low_stock_items = $pdo->query("
                            SELECT i.name, i.sku, s.current_stock, i.minimum_stock_level, c.name as category_name
                            FROM inventory_items i
                            JOIN inventory_stock s ON i.id = s.item_id
                            JOIN inventory_categories c ON i.category_id = c.id
                            WHERE i.is_active = 1 AND s.current_stock <= i.minimum_stock_level
                            ORDER BY s.current_stock ASC
                            LIMIT 10
                        ")->fetchAll();
                    }
                    ?>

                    <?php if (empty($low_stock_items)): ?>
                        <div class="text-center py-8">
                            <i data-lucide="check-circle" class="h-12 w-12 text-green-500 mx-auto mb-4"></i>
                            <p class="text-muted-foreground">All items are sufficiently stocked!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($low_stock_items as $item): ?>
                                <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($item['sku']); ?> • <?php echo htmlspecialchars($item['category_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                                                <?php echo $item['current_stock']; ?> / <?php echo $item['minimum_stock_level']; ?>
                                            </span>
                                            <span class="text-sm text-muted-foreground">min</span>
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">Reorder needed</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Stock Movements -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Recent Stock Movements</h2>
                </div>
                <div class="p-6">
                    <?php
                    $pdo = getPdo();
                    $recent_movements = [];
                    if ($pdo) {
                        $recent_movements = $pdo->query("
                            SELECT h.*, i.name, i.sku
                            FROM inventory_stock_history h
                            JOIN inventory_items i ON h.item_id = i.id
                            ORDER BY h.created_at DESC
                            LIMIT 10
                        ")->fetchAll();
                    }
                    ?>

                    <?php if (empty($recent_movements)): ?>
                        <div class="text-center py-8">
                            <i data-lucide="clock" class="h-12 w-12 text-muted-foreground mx-auto mb-4"></i>
                            <p class="text-muted-foreground">No recent stock movements</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_movements as $movement): ?>
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full <?php
                                            echo match($movement['operation_type']) {
                                                'stock_in' => 'bg-green-500',
                                                'stock_out' => 'bg-red-500',
                                                'adjustment' => 'bg-blue-500',
                                                'expiry' => 'bg-orange-500',
                                                'damage' => 'bg-red-600',
                                                default => 'bg-gray-500'
                                            };
                                        ?>"></div>
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($movement['name']); ?></p>
                                            <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($movement['sku']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium <?php
                                            echo match($movement['operation_type']) {
                                                'stock_in' => 'text-green-600',
                                                'stock_out' => 'text-red-600',
                                                'adjustment' => 'text-blue-600',
                                                'expiry' => 'text-orange-600',
                                                'damage' => 'text-red-800',
                                                default => ''
                                            };
                                        ?>">
                                            <?php echo $movement['operation_type'] === 'stock_in' ? '+' : ($movement['operation_type'] === 'stock_out' ? '-' : ''); ?>
                                            <?php echo number_format($movement['quantity']); ?>
                                        </p>
                                        <p class="text-sm text-muted-foreground">
                                            <?php echo date('M j', strtotime($movement['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Items Management Tab -->
        <?php if ($tab === 'items'): ?>
        <div class="bg-card rounded-lg border">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Inventory Items</h2>
                    <button onclick="showAddItemModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        Add Item
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4 flex gap-4">
                    <input type="text" id="items-search" placeholder="Search items..." class="flex-1 px-3 py-2 border rounded-md" onkeyup="filterItems()">
                    <select id="category-filter" class="px-3 py-2 border rounded-md" onchange="filterItems()">
                        <option value="">All Categories</option>
                        <?php
                        $pdo = getPdo();
                        $categories = [];
                        if ($pdo) {
                            $categories = $pdo->query("SELECT id, name FROM inventory_categories WHERE is_active = 1 ORDER BY name")->fetchAll();
                        }
                        foreach ($categories as $category):
                        ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-3">Item</th>
                                <th class="text-left p-3">Category</th>
                                <th class="text-right p-3">Current Stock</th>
                                <th class="text-right p-3">Min Level</th>
                                <th class="text-right p-3">Unit Cost</th>
                                <th class="text-right p-3">Selling Price</th>
                                <th class="text-center p-3">Status</th>
                                <th class="text-center p-3">Stock</th>
                                <th class="text-center p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="items-list">
                            <?php
                            $pdo = getPdo();
                            $items = [];
                            if ($pdo) {
                                $items = $pdo->query("
                                    SELECT i.*, c.name as category_name, s.current_stock, s.available_stock
                                    FROM inventory_items i
                                    JOIN inventory_categories c ON i.category_id = c.id
                                    LEFT JOIN inventory_stock s ON i.id = s.item_id
                                    WHERE i.is_active = 1
                                    ORDER BY i.name
                                ")->fetchAll();
                            }

                            foreach ($items as $item):
                            ?>
                                <tr class="border-b hover:bg-muted/50">
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($item['sku']); ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-medium">
                                        <?php echo number_format($item['current_stock'] ?? 0); ?>
                                    </td>
                                    <td class="p-3 text-right">
                                        <?php echo number_format($item['minimum_stock_level']); ?>
                                    </td>
                                    <td class="p-3 text-right">
                                        $<?php echo number_format($item['unit_cost'], 2); ?>
                                    </td>
                                    <td class="p-3 text-right">
                                        $<?php echo number_format($item['selling_price'] ?? 0, 2); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if (($item['current_stock'] ?? 0) == 0): ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Out of Stock</span>
                                        <?php elseif (($item['current_stock'] ?? 0) <= $item['minimum_stock_level']): ?>
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Low Stock</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button onclick="adjustStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['current_stock'] ?? 0; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                            Adjust
                                        </button>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="editItem(<?php echo $item['id']; ?>)" class="p-1 text-blue-600 hover:bg-blue-100 rounded">
                                                <i data-lucide="edit" class="h-4 w-4"></i>
                                            </button>
                                            <button onclick="deleteItem(<?php echo $item['id']; ?>)" class="p-1 text-red-600 hover:bg-red-100 rounded">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
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
        <?php endif; ?>

        <!-- Categories Tab -->
        <?php if ($tab === 'categories'): ?>
        <div class="bg-card rounded-lg border">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Inventory Categories</h2>
                    <button onclick="showAddCategoryModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        Add Category
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $pdo = getPdo();
                    $categories = [];
                    if ($pdo) {
                        $categories = $pdo->query("
                            SELECT c.*, COUNT(i.id) as item_count
                            FROM inventory_categories c
                            LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
                            WHERE c.is_active = 1
                            GROUP BY c.id, c.name
                            ORDER BY c.name
                        ")->fetchAll();
                    }

                    foreach ($categories as $category):
                    ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <div class="flex gap-1">
                                    <button onclick="editCategory(<?php echo $category['id']; ?>)" class="p-1 text-blue-600 hover:bg-blue-100 rounded">
                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                    </button>
                                    <button onclick="deleteCategory(<?php echo $category['id']; ?>)" class="p-1 text-red-600 hover:bg-red-100 rounded">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-muted-foreground mb-3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground"><?php echo $category['item_count']; ?> items</span>
                                <?php if ($category['parent_category_id']): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Subcategory</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Suppliers Tab -->
        <?php if ($tab === 'suppliers'): ?>
        <div class="bg-card rounded-lg border">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Suppliers</h2>
                    <button onclick="showAddSupplierModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        Add Supplier
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $pdo = getPdo();
                    $suppliers = [];
                    if ($pdo) {
                        $suppliers = $pdo->query("
                            SELECT s.*, COUNT(i.id) as item_count
                            FROM inventory_suppliers s
                            LEFT JOIN inventory_items i ON s.id = i.supplier_id AND i.is_active = 1
                            WHERE s.is_active = 1
                            GROUP BY s.id, s.name
                            ORDER BY s.name
                        ")->fetchAll();
                    }

                    foreach ($suppliers as $supplier):
                    ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium"><?php echo htmlspecialchars($supplier['name']); ?></h3>
                                <div class="flex gap-1">
                                    <button onclick="editSupplier(<?php echo $supplier['id']; ?>)" class="p-1 text-blue-600 hover:bg-blue-100 rounded">
                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                    </button>
                                    <button onclick="deleteSupplier(<?php echo $supplier['id']; ?>)" class="p-1 text-red-600 hover:bg-red-100 rounded">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1 text-sm">
                                <?php if ($supplier['contact_person']): ?>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($supplier['contact_person']); ?></p>
                                <?php endif; ?>
                                <?php if ($supplier['email']): ?>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($supplier['email']); ?></p>
                                <?php endif; ?>
                                <?php if ($supplier['phone']): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($supplier['phone']); ?></p>
                                <?php endif; ?>
                                <p><strong>Items:</strong> <?php echo $supplier['item_count']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Purchase Orders Tab -->
        <?php if ($tab === 'purchase-orders'): ?>
        <div class="bg-card rounded-lg border">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Purchase Orders</h2>
                    <button onclick="showAddPurchaseOrderModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        New Purchase Order
                    </button>
                </div>
            </div>
            <div class="p-6">
                <?php
                $pdo = getPdo();
                $purchase_orders = [];
                if ($pdo) {
                    $purchase_orders = $pdo->query("
                        SELECT po.*, s.name as supplier_name, COUNT(poi.id) as item_count,
                               SUM(poi.quantity * poi.unit_cost) as total_value
                        FROM inventory_purchase_orders po
                        JOIN inventory_suppliers s ON po.supplier_id = s.id
                        LEFT JOIN inventory_purchase_order_items poi ON po.id = poi.purchase_order_id
                        GROUP BY po.id, po.po_number
                        ORDER BY po.created_at DESC
                        LIMIT 20
                    ")->fetchAll();
                }
                ?>

                <?php if (empty($purchase_orders)): ?>
                    <div class="text-center py-8">
                        <i data-lucide="shopping-cart" class="h-12 w-12 text-muted-foreground mx-auto mb-4"></i>
                        <p class="text-muted-foreground">No purchase orders found</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($purchase_orders as $po): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h3 class="font-medium"><?php echo htmlspecialchars($po['po_number']); ?></h3>
                                        <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($po['supplier_name']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-1 rounded text-xs <?php
                                            echo match($po['status']) {
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'sent' => 'bg-blue-100 text-blue-800',
                                                'confirmed' => 'bg-yellow-100 text-yellow-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php echo ucwords($po['status']); ?>
                                        </span>
                                        <p class="text-sm font-medium mt-1">$<?php echo number_format($po['total_value'] ?? 0, 2); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <span>Ordered: <?php echo date('M j, Y', strtotime($po['order_date'])); ?></span>
                                    <span><?php echo $po['item_count']; ?> items</span>
                                    <?php if ($po['expected_delivery_date']): ?>
                                        <span>Expected: <?php echo date('M j, Y', strtotime($po['expected_delivery_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reports Tab Hidden -->
        <!-- <?php if ($tab === 'reports'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Inventory Reports</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button onclick="generateLowStockReport()" class="w-full text-left p-3 border rounded-lg hover:bg-muted/50">
                            <div class="flex items-center gap-3">
                                <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-600"></i>
                                <div>
                                    <p class="font-medium">Low Stock Report</p>
                                    <p class="text-sm text-muted-foreground">Items below minimum stock levels</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="generateInventoryValueReport()" class="w-full text-left p-3 border rounded-lg hover:bg-muted/50">
                            <div class="flex items-center gap-3">
                                <i data-lucide="file-text" class="h-5 w-5 text-green-600"></i>
                                <div>
                                    <p class="font-medium">Inventory Value Report</p>
                                    <p class="text-sm text-muted-foreground">Current inventory value by category</p>
                                </div>
                            </div>
                        </button>

                        <button onclick="generateStockMovementReport()" class="w-full text-left p-3 border rounded-lg hover:bg-muted/50">
                            <div class="flex items-center gap-3">
                                <i data-lucide="activity" class="h-5 w-5 text-blue-600"></i>
                                <div>
                                    <p class="font-medium">Stock Movement Report</p>
                                    <p class="text-sm text-muted-foreground">Recent stock movements and adjustments</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Quick Stats</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Active Purchase Orders</span>
                            <span class="font-medium"><?php echo number_format($stats['active_po'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Recent Movements (7 days)</span>
                            <span class="font-medium"><?php echo number_format($stats['recent_movements'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Total Categories</span>
                            <span class="font-medium"><?php echo count($categories ?? []); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Active Suppliers</span>
                            <span class="font-medium"><?php echo count($suppliers ?? []); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?> -->
    </main>

    <!-- Add/Edit Supplier Modal -->
    <div id="supplier-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-card rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 id="supplier-modal-title" class="text-xl font-semibold">Add New Supplier</h2>
                <button onclick="closeSupplierModal()" class="text-muted-foreground hover:text-foreground">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form id="supplier-form" method="POST" action="">
                <input type="hidden" id="supplier-id" name="supplier_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Supplier Name *</label>
                        <input type="text" id="supplier-name" name="name" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Contact Person</label>
                        <input type="text" id="supplier-contact" name="contact_person" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="supplier-email" name="email" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input type="tel" id="supplier-phone" name="phone" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea id="supplier-address" name="address" rows="2" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">City</label>
                        <input type="text" id="supplier-city" name="city" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">State</label>
                        <input type="text" id="supplier-state" name="state" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">ZIP Code</label>
                        <input type="text" id="supplier-zip" name="zip_code" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Country</label>
                        <input type="text" id="supplier-country" name="country" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Website</label>
                        <input type="url" id="supplier-website" name="website" placeholder="https://" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea id="supplier-notes" name="notes" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="supplier-active" name="is_active" value="1" checked class="mr-2">
                            <span class="text-sm font-medium">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border rounded-md hover:bg-muted">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Purchase Order Modal -->
    <div id="purchase-order-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-card rounded-lg p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 id="purchase-order-modal-title" class="text-xl font-semibold">New Purchase Order</h2>
                <button onclick="closePurchaseOrderModal()" class="text-muted-foreground hover:text-foreground">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form id="purchase-order-form" method="POST" action="">
                <input type="hidden" id="purchase-order-id" name="purchase_order_id" value="">

                <!-- Purchase Order Header -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">PO Number *</label>
                        <input type="text" id="po-number" name="po_number" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Supplier *</label>
                        <select id="po-supplier" name="supplier_id" required class="w-full px-3 py-2 border rounded-md">
                            <option value="">Select Supplier</option>
                            <?php
                            $pdo = getPdo();
                            $suppliers = [];
                            if ($pdo) {
                                $suppliers = $pdo->query("SELECT id, name FROM inventory_suppliers WHERE is_active = 1 ORDER BY name")->fetchAll();
                            }
                            foreach ($suppliers as $supplier):
                            ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Order Date *</label>
                        <input type="date" id="po-order-date" name="order_date" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Expected Delivery Date</label>
                        <input type="date" id="po-expected-date" name="expected_delivery_date" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select id="po-status" name="status" class="w-full px-3 py-2 border rounded-md">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea id="po-notes" name="notes" rows="2" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>
                </div>

                <!-- Purchase Order Items -->
                <div class="border rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium">Order Items</h3>
                        <button type="button" onclick="addPurchaseOrderItem()" class="px-3 py-1 bg-primary text-primary-foreground rounded text-sm hover:bg-primary/90">
                            Add Item
                        </button>
                    </div>

                    <div id="purchase-order-items" class="space-y-3">
                        <!-- Items will be added here dynamically -->
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <div class="flex justify-end">
                            <div class="text-right">
                                <p class="text-sm text-muted-foreground">Total Amount</p>
                                <p id="po-total-amount" class="text-xl font-bold">$0.00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closePurchaseOrderModal()" class="px-4 py-2 border rounded-md hover:bg-muted">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        Save Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase Order Item Template (hidden) -->
    <div id="po-item-template" class="hidden">
        <div class="po-item border rounded-lg p-3">
            <input type="hidden" class="po-item-id" name="items[0][id]" value="">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Item *</label>
                    <select class="po-item-select w-full px-3 py-1 border rounded" required>
                        <option value="">Select Item</option>
                        <?php
                        $pdo = getPdo();
                        $items = [];
                        if ($pdo) {
                            $items = $pdo->query("SELECT id, name, sku FROM inventory_items WHERE is_active = 1 ORDER BY name")->fetchAll();
                        }
                        foreach ($items as $item):
                        ?>
                            <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name'] . ' (' . $item['sku'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Quantity *</label>
                    <input type="number" class="po-item-quantity w-full px-3 py-1 border rounded" min="1" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Unit Cost *</label>
                    <input type="number" class="po-item-cost w-full px-3 py-1 border rounded" step="0.01" min="0" required>
                </div>

                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1">Total</label>
                        <p class="po-item-total text-sm font-medium">$0.00</p>
                    </div>
                    <button type="button" onclick="removePurchaseOrderItem(this)" class="px-2 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="category-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-card rounded-lg p-6 w-full max-w-lg">
            <div class="flex items-center justify-between mb-4">
                <h2 id="category-modal-title" class="text-xl font-semibold">Add New Category</h2>
                <button onclick="closeCategoryModal()" class="text-muted-foreground hover:text-foreground">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form id="category-form" method="POST" action="">
                <input type="hidden" id="category-id" name="category_id" value="">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Category Name *</label>
                        <input type="text" id="category-name" name="name" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Parent Category</label>
                        <select id="category-parent" name="parent_category_id" class="w-full px-3 py-2 border rounded-md">
                            <option value="">None (Top Level)</option>
                            <?php
                            $pdo = getPdo();
                            $categories = [];
                            if ($pdo) {
                                $categories = $pdo->query("SELECT id, name FROM inventory_categories WHERE is_active = 1 AND parent_category_id IS NULL ORDER BY name")->fetchAll();
                            }
                            foreach ($categories as $category):
                            ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea id="category-description" name="description" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="category-active" name="is_active" value="1" checked class="mr-2">
                            <span class="text-sm font-medium">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 border rounded-md hover:bg-muted">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="item-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-card rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 id="item-modal-title" class="text-xl font-semibold">Add New Item</h2>
                <button onclick="closeItemModal()" class="text-muted-foreground hover:text-foreground">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form id="item-form" method="POST" action="">
                <input type="hidden" id="item-id" name="item_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Item Name *</label>
                        <input type="text" id="item-name" name="name" required class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">SKU</label>
                        <input type="text" id="item-sku" name="sku" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Barcode</label>
                        <input type="text" id="item-barcode" name="barcode" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Category *</label>
                        <select id="item-category" name="category_id" required class="w-full px-3 py-2 border rounded-md">
                            <option value="">Select Category</option>
                            <?php
                            $pdo = getPdo();
                            $categories = [];
                            if ($pdo) {
                                $categories = $pdo->query("SELECT id, name FROM inventory_categories WHERE is_active = 1 ORDER BY name")->fetchAll();
                            }
                            foreach ($categories as $category):
                            ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Supplier</label>
                        <select id="item-supplier" name="supplier_id" class="w-full px-3 py-2 border rounded-md">
                            <option value="">Select Supplier</option>
                            <?php
                            $suppliers = [];
                            if ($pdo) {
                                $suppliers = $pdo->query("SELECT id, name FROM inventory_suppliers WHERE is_active = 1 ORDER BY name")->fetchAll();
                            }
                            foreach ($suppliers as $supplier):
                            ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Unit of Measure</label>
                        <select id="item-unit" name="unit_of_measure" class="w-full px-3 py-2 border rounded-md">
                            <option value="pieces">Pieces</option>
                            <option value="kg">Kilograms</option>
                            <option value="liters">Liters</option>
                            <option value="boxes">Boxes</option>
                            <option value="packets">Packets</option>
                            <option value="bottles">Bottles</option>
                            <option value="sets">Sets</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Unit Cost ($)</label>
                        <input type="number" id="item-cost" name="unit_cost" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Selling Price ($)</label>
                        <input type="number" id="item-price" name="selling_price" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Minimum Stock Level</label>
                        <input type="number" id="item-min-stock" name="minimum_stock_level" min="0" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Maximum Stock Level</label>
                        <input type="number" id="item-max-stock" name="maximum_stock_level" min="0" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Reorder Point</label>
                        <input type="number" id="item-reorder" name="reorder_point" min="0" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Location</label>
                        <input type="text" id="item-location" name="location" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Description</label>
                        <textarea id="item-description" name="description" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="item-perishable" name="is_perishable" value="1" class="mr-2">
                            <span class="text-sm font-medium">Perishable Item</span>
                        </label>
                    </div>

                    <div id="expiry-date-field" class="hidden">
                        <label class="block text-sm font-medium mb-1">Expiry Date</label>
                        <input type="date" id="item-expiry" name="expiry_date" class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="item-active" name="is_active" value="1" checked class="mr-2">
                            <span class="text-sm font-medium">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeItemModal()" class="px-4 py-2 border rounded-md hover:bg-muted">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-card rounded-lg p-6 w-full max-w-md">
            <div class="text-center">
                <i data-lucide="alert-triangle" class="h-12 w-12 text-red-500 mx-auto mb-4"></i>
                <h2 class="text-xl font-semibold mb-2">Confirm Deletion</h2>
                <p class="text-muted-foreground mb-6" id="delete-message">Are you sure you want to delete this item? This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 border rounded-md hover:bg-muted">
                        Cancel
                    </button>
                    <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        lucide.createIcons();
        function setTab(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }
        function showAddCategoryModal() {
            document.getElementById('category-modal-title').textContent = 'Add New Category';
            document.getElementById('category-form').action = 'inventory-actions.php?action=add_category';
            document.getElementById('category-id').value = '';
            document.getElementById('category-name').value = '';
            document.getElementById('category-parent').value = '';
            document.getElementById('category-description').value = '';
            document.getElementById('category-active').checked = true;

            document.getElementById('category-modal').classList.remove('hidden');
        }

        function editCategory(id) {
            // Fetch category data via AJAX
            fetch(`inventory-actions.php?action=get_category&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const category = data.category;
                        document.getElementById('category-modal-title').textContent = 'Edit Category';
                        document.getElementById('category-form').action = 'inventory-actions.php?action=edit_category';
                        document.getElementById('category-id').value = category.id;
                        document.getElementById('category-name').value = category.name;
                        document.getElementById('category-parent').value = category.parent_category_id || '';
                        document.getElementById('category-description').value = category.description || '';
                        document.getElementById('category-active').checked = category.is_active == 1;

                        document.getElementById('category-modal').classList.remove('hidden');
                    } else {
                        alert('Error loading category data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading category data');
                });
        }

        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category? Items in this category may be affected.')) {
                fetch(`inventory-actions.php?action=delete_category&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting category: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting category');
                });
            }
        }

        function showAddSupplierModal() {
            document.getElementById('supplier-modal-title').textContent = 'Add New Supplier';
            document.getElementById('supplier-form').action = 'inventory-actions.php?action=add_supplier';
            document.getElementById('supplier-id').value = '';
            document.getElementById('supplier-name').value = '';
            document.getElementById('supplier-contact').value = '';
            document.getElementById('supplier-email').value = '';
            document.getElementById('supplier-phone').value = '';
            document.getElementById('supplier-address').value = '';
            document.getElementById('supplier-city').value = '';
            document.getElementById('supplier-state').value = '';
            document.getElementById('supplier-zip').value = '';
            document.getElementById('supplier-country').value = '';
            document.getElementById('supplier-website').value = '';
            document.getElementById('supplier-notes').value = '';
            document.getElementById('supplier-active').checked = true;

            document.getElementById('supplier-modal').classList.remove('hidden');
        }

        function editSupplier(id) {
            // Fetch supplier data via AJAX
            fetch(`inventory-actions.php?action=get_supplier&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const supplier = data.supplier;
                        document.getElementById('supplier-modal-title').textContent = 'Edit Supplier';
                        document.getElementById('supplier-form').action = 'inventory-actions.php?action=edit_supplier';
                        document.getElementById('supplier-id').value = supplier.id;
                        document.getElementById('supplier-name').value = supplier.name;
                        document.getElementById('supplier-contact').value = supplier.contact_person || '';
                        document.getElementById('supplier-email').value = supplier.email || '';
                        document.getElementById('supplier-phone').value = supplier.phone || '';
                        document.getElementById('supplier-address').value = supplier.address || '';
                        document.getElementById('supplier-city').value = supplier.city || '';
                        document.getElementById('supplier-state').value = supplier.state || '';
                        document.getElementById('supplier-zip').value = supplier.zip_code || '';
                        document.getElementById('supplier-country').value = supplier.country || '';
                        document.getElementById('supplier-website').value = supplier.website || '';
                        document.getElementById('supplier-notes').value = supplier.notes || '';
                        document.getElementById('supplier-active').checked = supplier.is_active == 1;

                        document.getElementById('supplier-modal').classList.remove('hidden');
                    } else {
                        alert('Error loading supplier data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading supplier data');
                });
        }

        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier? Items associated with this supplier may be affected.')) {
                fetch(`inventory-actions.php?action=delete_supplier&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting supplier: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting supplier');
                });
            }
        }

        function showAddPurchaseOrderModal() {
            document.getElementById('purchase-order-modal-title').textContent = 'New Purchase Order';
            document.getElementById('purchase-order-form').action = 'inventory-actions.php?action=add_purchase_order';
            document.getElementById('purchase-order-id').value = '';
            document.getElementById('po-number').value = '';
            document.getElementById('po-supplier').value = '';
            document.getElementById('po-order-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('po-expected-date').value = '';
            document.getElementById('po-status').value = 'draft';
            document.getElementById('po-notes').value = '';

            // Clear existing items and add one empty item
            document.getElementById('purchase-order-items').innerHTML = '';
            addPurchaseOrderItem();

            document.getElementById('purchase-order-modal').classList.remove('hidden');
        }

        function editPurchaseOrder(id) {
            // Fetch purchase order data via AJAX
            fetch(`inventory-actions.php?action=get_purchase_order&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const po = data.purchase_order;
                        document.getElementById('purchase-order-modal-title').textContent = 'Edit Purchase Order';
                        document.getElementById('purchase-order-form').action = 'inventory-actions.php?action=edit_purchase_order';
                        document.getElementById('purchase-order-id').value = po.id;
                        document.getElementById('po-number').value = po.po_number;
                        document.getElementById('po-supplier').value = po.supplier_id;
                        document.getElementById('po-order-date').value = po.order_date;
                        document.getElementById('po-expected-date').value = po.expected_delivery_date || '';
                        document.getElementById('po-status').value = po.status;
                        document.getElementById('po-notes').value = po.notes || '';

                        // Load items
                        loadPurchaseOrderItems(po.id);

                        document.getElementById('purchase-order-modal').classList.remove('hidden');
                    } else {
                        alert('Error loading purchase order data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading purchase order data');
                });
        }

        function deletePurchaseOrder(id) {
            if (confirm('Are you sure you want to delete this purchase order? This action cannot be undone.')) {
                fetch(`inventory-actions.php?action=delete_purchase_order&id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting purchase order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting purchase order');
                });
            }
        }

        function closePurchaseOrderModal() {
            document.getElementById('purchase-order-modal').classList.add('hidden');
        }

        function addPurchaseOrderItem() {
            const template = document.getElementById('po-item-template');
            const itemsContainer = document.getElementById('purchase-order-items');
            const itemIndex = itemsContainer.children.length;

            // Clone the template
            const newItem = template.firstElementChild.cloneNode(true);

            // Update the name attributes to include the correct index
            const inputs = newItem.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.name) {
                    input.name = input.name.replace('[0]', `[${itemIndex}]`);
                }
            });

            // Add event listeners for real-time calculation
            const quantityInput = newItem.querySelector('.po-item-quantity');
            const costInput = newItem.querySelector('.po-item-cost');

            quantityInput.addEventListener('input', () => updatePurchaseOrderItemTotal(newItem));
            costInput.addEventListener('input', () => updatePurchaseOrderItemTotal(newItem));

            itemsContainer.appendChild(newItem);
        }

        function removePurchaseOrderItem(button) {
            const item = button.closest('.po-item');
            if (document.querySelectorAll('.po-item').length > 1) {
                item.remove();
                updatePurchaseOrderTotal();
            } else {
                alert('Purchase order must have at least one item');
            }
        }

        function updatePurchaseOrderItemTotal(itemElement) {
            const quantity = parseFloat(itemElement.querySelector('.po-item-quantity').value) || 0;
            const cost = parseFloat(itemElement.querySelector('.po-item-cost').value) || 0;
            const total = quantity * cost;

            itemElement.querySelector('.po-item-total').textContent = `$${total.toFixed(2)}`;
            updatePurchaseOrderTotal();
        }

        function updatePurchaseOrderTotal() {
            const items = document.querySelectorAll('.po-item');
            let total = 0;

            items.forEach(item => {
                const itemTotal = parseFloat(item.querySelector('.po-item-total').textContent.replace('$', '')) || 0;
                total += itemTotal;
            });

            document.getElementById('po-total-amount').textContent = `$${total.toFixed(2)}`;
        }

        function loadPurchaseOrderItems(purchaseOrderId) {
            fetch(`inventory-actions.php?action=get_purchase_order_items&id=${purchaseOrderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const itemsContainer = document.getElementById('purchase-order-items');
                        itemsContainer.innerHTML = '';

                        data.items.forEach((item, index) => {
                            const template = document.getElementById('po-item-template');
                            const newItem = template.firstElementChild.cloneNode(true);

                            // Update name attributes
                            const inputs = newItem.querySelectorAll('input, select');
                            inputs.forEach(input => {
                                if (input.name) {
                                    input.name = input.name.replace('[0]', `[${index}]`);
                                }
                            });

                            // Fill in the data
                            newItem.querySelector('.po-item-id').value = item.id || '';
                            newItem.querySelector('.po-item-select').value = item.item_id;
                            newItem.querySelector('.po-item-quantity').value = item.quantity;
                            newItem.querySelector('.po-item-cost').value = item.unit_cost;

                            // Update total
                            const quantity = parseFloat(item.quantity) || 0;
                            const cost = parseFloat(item.unit_cost) || 0;
                            newItem.querySelector('.po-item-total').textContent = `$${(quantity * cost).toFixed(2)}`;

                            // Add event listeners
                            const quantityInput = newItem.querySelector('.po-item-quantity');
                            const costInput = newItem.querySelector('.po-item-cost');

                            quantityInput.addEventListener('input', () => updatePurchaseOrderItemTotal(newItem));
                            costInput.addEventListener('input', () => updatePurchaseOrderItemTotal(newItem));

                            itemsContainer.appendChild(newItem);
                        });

                        updatePurchaseOrderTotal();
                    }
                })
                .catch(error => {
                    console.error('Error loading purchase order items:', error);
                });
        }

        function showAddItemModal() {
            document.getElementById('item-modal-title').textContent = 'Add New Item';
            document.getElementById('item-form').action = 'inventory-actions.php?action=add_item';
            document.getElementById('item-id').value = '';
            document.getElementById('item-name').value = '';
            document.getElementById('item-sku').value = '';
            document.getElementById('item-barcode').value = '';
            document.getElementById('item-category').value = '';
            document.getElementById('item-supplier').value = '';
            document.getElementById('item-unit').value = 'pieces';
            document.getElementById('item-cost').value = '';
            document.getElementById('item-price').value = '';
            document.getElementById('item-min-stock').value = '';
            document.getElementById('item-max-stock').value = '';
            document.getElementById('item-reorder').value = '';
            document.getElementById('item-location').value = '';
            document.getElementById('item-description').value = '';
            document.getElementById('item-perishable').checked = false;
            document.getElementById('expiry-date-field').classList.add('hidden');
            document.getElementById('item-expiry').value = '';
            document.getElementById('item-active').checked = true;

            document.getElementById('item-modal').classList.remove('hidden');
        }
        function editItem(id) {
            // Fetch item data via AJAX
            fetch(`inventory-actions.php?action=get_item&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.item;
                        document.getElementById('item-modal-title').textContent = 'Edit Item';
                        document.getElementById('item-form').action = 'inventory-actions.php?action=edit_item';
                        document.getElementById('item-id').value = item.id;
                        document.getElementById('item-name').value = item.name;
                        document.getElementById('item-sku').value = item.sku || '';
                        document.getElementById('item-barcode').value = item.barcode || '';
                        document.getElementById('item-category').value = item.category_id;
                        document.getElementById('item-supplier').value = item.supplier_id || '';
                        document.getElementById('item-unit').value = item.unit_of_measure;
                        document.getElementById('item-cost').value = item.unit_cost;
                        document.getElementById('item-price').value = item.selling_price || '';
                        document.getElementById('item-min-stock').value = item.minimum_stock_level;
                        document.getElementById('item-max-stock').value = item.maximum_stock_level || '';
                        document.getElementById('item-reorder').value = item.reorder_point;
                        document.getElementById('item-location').value = item.location || '';
                        document.getElementById('item-description').value = item.description || '';
                        document.getElementById('item-perishable').checked = item.is_perishable == 1;
                        document.getElementById('item-expiry').value = item.expiry_date || '';
                        document.getElementById('item-active').checked = item.is_active == 1;

                        if (item.is_perishable == 1) {
                            document.getElementById('expiry-date-field').classList.remove('hidden');
                        } else {
                            document.getElementById('expiry-date-field').classList.add('hidden');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading item data');
                });
        }

        function editItem(id) {
            // Fetch item data via AJAX
            fetch(`inventory-actions.php?action=get_item&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.item;
                        document.getElementById('item-modal-title').textContent = 'Edit Item';
                        document.getElementById('item-form').action = 'inventory-actions.php?action=edit_item';
                        document.getElementById('item-id').value = item.id;
                        document.getElementById('item-name').value = item.name;
                        document.getElementById('item-sku').value = item.sku || '';
                        document.getElementById('item-barcode').value = item.barcode || '';
                        document.getElementById('item-category').value = item.category_id;
                        document.getElementById('item-supplier').value = item.supplier_id || '';
                        document.getElementById('item-unit').value = item.unit_of_measure;
                        document.getElementById('item-cost').value = item.unit_cost;
                        document.getElementById('item-price').value = item.selling_price || '';
                        document.getElementById('item-min-stock').value = item.minimum_stock_level;
                        document.getElementById('item-max-stock').value = item.maximum_stock_level || '';
                        document.getElementById('item-reorder').value = item.reorder_point;
                        document.getElementById('item-location').value = item.location || '';
                        document.getElementById('item-description').value = item.description || '';
                        document.getElementById('item-perishable').checked = item.is_perishable == 1;
                        document.getElementById('item-expiry').value = item.expiry_date || '';
                        document.getElementById('item-active').checked = item.is_active == 1;

                        if (item.is_perishable == 1) {
                            document.getElementById('expiry-date-field').classList.remove('hidden');
                        } else {
                            document.getElementById('expiry-date-field').classList.add('hidden');
                        }

                        document.getElementById('item-modal').classList.remove('hidden');
                    } else {
                        alert('Error loading item data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading item data');
                });
        }

        function closeItemModal() {
            document.getElementById('item-modal').classList.add('hidden');
        }

        function deleteItem(id) {
            deleteItemId = id;
            document.getElementById('delete-message').textContent = 'Are you sure you want to delete this item? This action cannot be undone.';
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
        }

        function confirmDelete() {
            if (typeof deleteItemId !== 'undefined') {
                fetch(`inventory-actions.php?action=delete_item&id=${deleteItemId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting item: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting item');
                });
            }
            closeDeleteModal();
        }

        function filterItems() {
            const searchTerm = document.getElementById('items-search').value.toLowerCase();
            const categoryFilter = document.getElementById('category-filter').value;
            const rows = document.querySelectorAll('#items-list tr');

            rows.forEach(row => {
                const itemName = row.cells[0].textContent.toLowerCase();
                const itemSku = row.cells[0].querySelector('.text-sm').textContent.toLowerCase();
                const categoryName = row.cells[1].textContent.toLowerCase();

                let showRow = true;

                if (searchTerm && !itemName.includes(searchTerm) && !itemSku.includes(searchTerm)) {
                    showRow = false;
                }

                if (categoryFilter && !categoryName.includes(categoryFilter.toLowerCase())) {
                    showRow = false;
                }

                row.style.display = showRow ? '' : 'none';
            });
        }

        function generateLowStockReport() {
            // Show loading indicator
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Generating...';
            button.disabled = true;

            fetch('inventory-actions.php?action=generate_low_stock_report', {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'low-stock-report.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error generating low stock report:', error);
                alert('Error generating low stock report: ' + error.message);
            })
            .finally(() => {
                // Restore button
                button.innerHTML = originalText;
                button.disabled = false;
                lucide.createIcons();
            });
        }

        function generateInventoryValueReport() {
            // Show loading indicator
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Generating...';
            button.disabled = true;

            fetch('inventory-actions.php?action=generate_inventory_value_report', {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'inventory-value-report.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error generating inventory value report:', error);
                alert('Error generating inventory value report: ' + error.message);
            })
            .finally(() => {
                // Restore button
                button.innerHTML = originalText;
                button.disabled = false;
                lucide.createIcons();
            });
        }

        function generateStockMovementReport() {
            // Show loading indicator
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Generating...';
            button.disabled = true;

            fetch('inventory-actions.php?action=generate_stock_movement_report', {
                method: 'POST'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'stock-movement-report.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error generating stock movement report:', error);
                alert('Error generating stock movement report: ' + error.message);
            })
            .finally(() => {
                // Restore button
                button.innerHTML = originalText;
                button.disabled = false;
                lucide.createIcons();
            });
        }


        // Search functionality
        document.getElementById('stock-search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#stock-table tr');

            rows.forEach(row => {
                if (row.cells && row.cells[0]) {
                    const text = row.cells[0].textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        });

        document.getElementById('supplier-search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#suppliers-table tr');

            rows.forEach(row => {
                if (row.cells && row.cells[0]) {
                    const text = row.cells[0].textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        });

        // Close modal when clicking outside
        document.getElementById('adjustment-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideAdjustmentModal();
            }
        });

        document.getElementById('add-supplier-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideAddSupplierModal();
            }
        });
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
