<?php
// Complete Inventory Management System - Combined
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin', 'manager', 'staff']);

$tab = $_GET['tab'] ?? 'stock';
$supplier_action = $_GET['supplier_action'] ?? 'list';
$supplier_id = $_GET['supplier_id'] ?? null;

// Handle stock adjustment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_action'])) {
    $item_id = (int)$_POST['item_id'];
    $adjustment_type = $_POST['adjustment_type'];
    $quantity = (int)$_POST['quantity'];
    $notes = trim($_POST['notes']);
    $user_id = $_SESSION['user_id'];

    if (empty($item_id) || empty($adjustment_type) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
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
                }

                if (!isset($error)) {
                    // Update stock
                    $stmt = $pdo->prepare("UPDATE inventory_stock SET current_stock = ?, updated_by = ?, last_updated = NOW() WHERE item_id = ?");
                    $stmt->execute([$new_stock, $user_id, $item_id]);

                    // Record history
                    $stmt = $pdo->prepare("INSERT INTO inventory_stock_history (item_id, operation_type, quantity, previous_stock, new_stock, notes, performed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$item_id, $operation_type, abs($quantity), $current_stock, $new_stock, $notes, $user_id]);

                    $success = "Stock adjustment completed successfully.";
                }
            }
        }
    }
}

// Handle supplier form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_action'])) {
    if ($supplier_action === 'add' || $supplier_action === 'edit') {
        $name = trim($_POST['name']);
        $contact_person = trim($_POST['contact_person']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $zip_code = trim($_POST['zip_code']);
        $country = trim($_POST['country']);
        $website = trim($_POST['website']);
        $notes = trim($_POST['notes']);

        if (empty($name)) {
            $error = "Supplier name is required.";
        } else {
            $pdo = getPdo();
            if (!$pdo) {
                $error = "Database connection failed.";
            } else {
                try {
                    if ($supplier_action === 'add') {
                        $stmt = $pdo->prepare("INSERT INTO inventory_suppliers (name, contact_person, email, phone, address, city, state, zip_code, country, website, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$name, $contact_person, $email, $phone, $address, $city, $state, $zip_code, $country, $website, $notes, $_SESSION['user_id']]);
                        $success = "Supplier added successfully.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE inventory_suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, country = ?, website = ?, notes = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $contact_person, $email, $phone, $address, $city, $state, $zip_code, $country, $website, $notes, $supplier_id]);
                        $success = "Supplier updated successfully.";
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = "A supplier with this email already exists.";
                    } else {
                        $error = "Error saving supplier: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Get supplier data for editing
$supplier = null;
$pdo = getPdo();
if (($supplier_action === 'edit' || $supplier_action === 'view') && $supplier_id && $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM inventory_suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Inn Nexus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Inventory Management</h1>
            <p class="text-gray-600"><?php echo date('F j, Y'); ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-8">
                <button onclick="setTab('stock')" class="py-2 px-1 border-b-2 font-medium text-sm <?php echo $tab === 'stock' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                    Inventory Management
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <?php if ($tab === 'stock'): ?>
            <!-- Stock Levels Tab -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Stock Levels Table -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg border">
                        <div class="p-6 border-b">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-semibold">Current Stock Levels</h2>
                                <button onclick="showAdjustmentModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                                    Adjust Stock
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <input type="text" id="stock-search" placeholder="Search items..." class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left p-3">Item</th>
                                            <th class="text-left p-3">SKU</th>
                                            <th class="text-right p-3">Current</th>
                                            <th class="text-right p-3">Available</th>
                                            <th class="text-right p-3">Min Level</th>
                                            <th class="text-center p-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stock-table">
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
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-3">
                                                    <div class="font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                                </td>
                                                <td class="p-3">
                                                    <span class="font-mono text-sm text-gray-600"><?php echo htmlspecialchars($item['sku']); ?></span>
                                                </td>
                                                <td class="p-3 text-right font-medium"><?php echo number_format($item['current_stock']); ?></td>
                                                <td class="p-3 text-right"><?php echo number_format($item['available_stock']); ?></td>
                                                <td class="p-3 text-right"><?php echo number_format($item['minimum_stock_level']); ?></td>
                                                <td class="p-3 text-center">
                                                    <?php if ($status === 'out'): ?>
                                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">OUT</span>
                                                    <?php elseif ($status === 'low'): ?>
                                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">LOW</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">OK</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar with Recent Activity & Suppliers -->
                <div class="space-y-6">
                    <!-- Recent Adjustments -->
                    <div class="bg-white rounded-lg border">
                        <div class="p-4 border-b">
                            <h3 class="font-semibold text-gray-900">Recent Adjustments</h3>
                        </div>
                        <div class="p-4">
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
                                    LIMIT 6
                                ")->fetchAll();
                            }
                            ?>

                            <?php if (empty($recent_adjustments)): ?>
                                <p class="text-gray-600 text-center py-3 text-sm">No recent adjustments</p>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($recent_adjustments as $adjustment): ?>
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                                            <div>
                                                <p class="font-medium truncate"><?php echo htmlspecialchars($adjustment['name']); ?></p>
                                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($adjustment['sku']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-medium text-xs <?php echo $adjustment['operation_type'] === 'stock_in' ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $adjustment['operation_type'] === 'stock_in' ? '+' : '-'; ?><?php echo $adjustment['quantity']; ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Suppliers -->
                    <div class="bg-white rounded-lg border">
                        <div class="p-4 border-b">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-gray-900">Suppliers</h3>
                                <button onclick="showAddSupplierModal()" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                    Add
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <input type="text" id="supplier-search" placeholder="Search suppliers..." class="w-full px-2 py-1 border rounded text-sm">
                            </div>

                            <?php
                            $pdo = getPdo();
                            $suppliers_list = [];
                            if ($pdo) {
                                $suppliers_list = $pdo->query("
                                    SELECT s.*, COUNT(i.id) as item_count
                                    FROM inventory_suppliers s
                                    LEFT JOIN inventory_items i ON s.id = i.supplier_id AND i.is_active = 1
                                    WHERE s.is_active = 1
                                    GROUP BY s.id
                                    ORDER BY s.name
                                    LIMIT 5
                                ")->fetchAll();
                            }

                            if (empty($suppliers_list)): ?>
                                <p class="text-gray-600 text-center py-3 text-sm">No suppliers</p>
                            <?php else: ?>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <?php foreach ($suppliers_list as $supplier_item): ?>
                                        <div class="p-2 border rounded text-sm">
                                            <div class="font-medium truncate"><?php echo htmlspecialchars($supplier_item['name']); ?></div>
                                            <?php if ($supplier_item['contact_person']): ?>
                                                <div class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($supplier_item['contact_person']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($supplier_item['email']): ?>
                                                <div class="text-xs text-blue-600 truncate"><?php echo htmlspecialchars($supplier_item['email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Adjustment Modal -->
            <div id="adjustment-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg w-full max-w-md">
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
                        <input type="hidden" name="stock_action" value="adjust">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Stock Adjustment</h2>
                            <button type="button" onclick="hideAdjustmentModal()" class="text-gray-500 hover:text-gray-700">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
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
                            <button type="button" onclick="hideAdjustmentModal()" class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Apply</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function setTab(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }

        function showAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.remove('hidden');
        }

        function hideAdjustmentModal() {
            document.getElementById('adjustment-modal').classList.add('hidden');
        }

        function showAddSupplierModal() {
            document.getElementById('add-supplier-modal').classList.remove('hidden');
        }

        function hideAddSupplierModal() {
            document.getElementById('add-supplier-modal').classList.add('hidden');
        }

        function editSupplier(id) {
            const url = new URL(window.location);
            url.searchParams.set('tab', 'suppliers');
            url.searchParams.set('supplier_action', 'edit');
            url.searchParams.set('supplier_id', id);
            window.location.href = url.toString();
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
</body>
</html>
