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
    $response = ['success' => false, 'message' => ''];
    
    try {
        $pdo = getPdo();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }

        $supplier_action = $_POST['supplier_action'];
        
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
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            throw new Exception("Supplier name is required.");
        }

        // Additional validation
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid website URL.");
        }

        if ($supplier_action === 'add') {
            // Check if supplier with same name already exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_suppliers WHERE LOWER(name) = LOWER(?) AND is_active = 1");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                throw new Exception("A supplier with this name already exists.");
            }

            $stmt = $pdo->prepare("INSERT INTO inventory_suppliers 
                (name, contact_person, email, phone, address, city, state, zip_code, country, website, notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $name, 
                $contact_person, 
                $email, 
                $phone, 
                $address, 
                $city, 
                $state, 
                $zip_code, 
                $country, 
                $website, 
                $notes, 
                $_SESSION['user_id']
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Supplier added successfully.',
                'supplier_id' => $pdo->lastInsertId()
            ];
        } else {
            // For edit, check if another supplier with the same name exists
            $supplier_id = (int)($_POST['supplier_id'] ?? 0);
            if (!$supplier_id) {
                throw new Exception("Invalid supplier ID.");
            }

            $stmt = $pdo->prepare("SELECT id FROM inventory_suppliers WHERE LOWER(name) = LOWER(?) AND id != ? AND is_active = 1");
            $stmt->execute([$name, $supplier_id]);
            if ($stmt->fetch()) {
                throw new Exception("Another supplier with this name already exists.");
            }

            $stmt = $pdo->prepare("UPDATE inventory_suppliers SET 
                name = ?, 
                contact_person = ?, 
                email = ?, 
                phone = ?, 
                address = ?, 
                city = ?, 
                state = ?, 
                zip_code = ?, 
                country = ?, 
                website = ?, 
                notes = ?, 
                updated_at = NOW(),
                updated_by = ?
                WHERE id = ?");
                
            $stmt->execute([
                $name, 
                $contact_person, 
                $email, 
                $phone, 
                $address, 
                $city, 
                $state, 
                $zip_code, 
                $country, 
                $website, 
                $notes, 
                $_SESSION['user_id'],
                $supplier_id
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Supplier updated successfully.',
                'supplier_id' => $supplier_id
            ];
        }
    } elseif ($supplier_action === 'delete') {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        if (!$supplier_id) {
            throw new Exception("Invalid supplier ID.");
        }

        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First, check if the supplier exists and is active
            $stmt = $pdo->prepare("SELECT id, name FROM inventory_suppliers WHERE id = ? AND is_active = 1");
            $stmt->execute([$supplier_id]);
            $supplier = $stmt->fetch();
            
            if (!$supplier) {
                throw new Exception("Supplier not found or already deleted.");
            }
            
            // Check if there are any items linked to this supplier
            $stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM inventory_items WHERE supplier_id = ?");
            $stmt->execute([$supplier_id]);
            $result = $stmt->fetch();
            $item_count = $result ? (int)$result['item_count'] : 0;
            
            // Soft delete the supplier
            $stmt = $pdo->prepare("UPDATE inventory_suppliers SET is_active = 0, updated_at = NOW(), created_by = ? WHERE id = ? AND is_active = 1");
            $stmt->execute([$_SESSION['user_id'], $supplier_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Failed to delete supplier. Please try again.");
            }
            
            // Commit the transaction
            $pdo->commit();
            
            $response = [
                'success' => true,
                'message' => 'Supplier deleted successfully.' . ($item_count > 0 ? ' Note: ' . $item_count . ' items were unlinked from this supplier.' : '')
            ];
        } catch (Exception $e) {
            // Rollback the transaction on error
            $pdo->rollBack();
            throw $e;
        }
    } else {
        throw new Exception("Invalid action.");
    }

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }

    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // For regular form submission, set flash messages
    if ($response['success']) {
        $success = $response['message'];
    } else {
        $error = $response['message'];
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
                                <div class="flex space-x-1">
                                    <button onclick="showAddSupplierModal()" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">
                                        Add
                                    </button>
                                    <a href="?tab=suppliers" class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 flex items-center">
                                        View All
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <input type="text" id="supplier-search" placeholder="Search suppliers..." 
                                       class="w-full px-2 py-1 border rounded text-sm"
                                       onkeyup="filterSuppliers(this.value)">
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
                                        <div class="p-2 border rounded text-sm group hover:bg-gray-50 supplier-item" 
                                             data-name="<?php echo strtolower(htmlspecialchars($supplier_item['name'])); ?>"
                                             data-contact="<?php echo strtolower(htmlspecialchars($supplier_item['contact_person'] ?? '')); ?>"
                                             data-email="<?php echo strtolower(htmlspecialchars($supplier_item['email'] ?? '')); ?>"
                                             data-phone="<?php echo strtolower(htmlspecialchars($supplier_item['phone'] ?? '')); ?>">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium truncate"><?php echo htmlspecialchars($supplier_item['name']); ?></div>
                                                    <?php if ($supplier_item['contact_person']): ?>
                                                        <div class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($supplier_item['contact_person']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier_item['email']): ?>
                                                        <div class="text-xs text-blue-600 truncate"><?php echo htmlspecialchars($supplier_item['email']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($supplier_item['phone']): ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($supplier_item['phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button onclick="event.stopPropagation(); showEditSupplierModal(<?php echo $supplier_item['id']; ?>)" 
                                                            class="p-1 text-blue-600 hover:text-blue-800">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button onclick="event.stopPropagation(); showDeleteSupplierModal(<?php echo $supplier_item['id']; ?>, '<?php echo addslashes(htmlspecialchars($supplier_item['name'])); ?>')" 
                                                            class="p-1 text-red-600 hover:text-red-800">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php if ($supplier_item['item_count'] > 0): ?>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    <?php echo $supplier_item['item_count']; ?> item<?php echo $supplier_item['item_count'] !== 1 ? 's' : ''; ?>
                                                </div>
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
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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

    <!-- Supplier Management Modal -->
    <div id="supplier-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="supplier-modal-title">Add New Supplier</h3>
                <button onclick="closeSupplierModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="supplier-form" method="POST" class="space-y-4">
                <input type="hidden" name="supplier_id" id="supplier-id">
                <input type="hidden" name="supplier_action" id="supplier-action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Supplier Name *</label>
                        <input type="text" id="name" name="name" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="2"
                                 class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" id="city" name="city"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700">State/Province</label>
                        <input type="text" id="state" name="state"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="zip_code" class="block text-sm font-medium text-gray-700">ZIP/Postal Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input type="text" id="country" name="country"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="col-span-2">
                        <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                        <input type="url" id="website" name="website"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                 class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-supplier-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Confirm Deletion</h3>
                <button onclick="closeDeleteSupplierModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="mb-6">Are you sure you want to delete this supplier? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteSupplierModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="button" onclick="confirmDeleteSupplier()" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        // Supplier Management Functions
        function showAddSupplierModal() {
            document.getElementById('supplier-modal-title').textContent = 'Add New Supplier';
            document.getElementById('supplier-form').reset();
            document.getElementById('supplier-id').value = '';
            document.getElementById('supplier-action').value = 'add';
            document.getElementById('supplier-modal').classList.remove('hidden');
        }

        function showEditSupplierModal(supplierId) {
            // Fetch supplier data via AJAX
            fetch(`api/get-supplier.php?id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const supplier = data.data;
                        document.getElementById('supplier-modal-title').textContent = 'Edit Supplier';
                        document.getElementById('supplier-id').value = supplier.id;
                        document.getElementById('supplier-action').value = 'edit';
                        document.getElementById('name').value = supplier.name || '';
                        document.getElementById('contact_person').value = supplier.contact_person || '';
                        document.getElementById('email').value = supplier.email || '';
                        document.getElementById('phone').value = supplier.phone || '';
                        document.getElementById('address').value = supplier.address || '';
                        document.getElementById('city').value = supplier.city || '';
                        document.getElementById('state').value = supplier.state || '';
                        document.getElementById('zip_code').value = supplier.zip_code || '';
                        document.getElementById('country').value = supplier.country || '';
                        document.getElementById('website').value = supplier.website || '';
                        document.getElementById('notes').value = supplier.notes || '';
                        
                        document.getElementById('supplier-modal').classList.remove('hidden');
                    } else {
                        alert('Failed to load supplier data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load supplier data. Please try again.');
                });
        }

        function closeSupplierModal() {
            document.getElementById('supplier-modal').classList.add('hidden');
        }

        function showDeleteSupplierModal(supplierId, supplierName) {
            window.currentSupplierId = supplierId;
            document.getElementById('delete-supplier-modal').classList.remove('hidden');
            // Update the confirmation message with the supplier name
            const messageElement = document.querySelector('#delete-supplier-modal p');
            messageElement.textContent = `Are you sure you want to delete "${supplierName}"? This action cannot be undone.`;
        }

        function closeDeleteSupplierModal() {
            document.getElementById('delete-supplier-modal').classList.add('hidden');
            window.currentSupplierId = null;
        }

        function confirmDeleteSupplier() {
            const supplierId = window.currentSupplierId;
            if (!supplierId) return;

            const formData = new FormData();
            formData.append('supplier_id', supplierId);
            formData.append('supplier_action', 'delete');

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeleteSupplierModal();
                    window.location.reload(); // Reload to see changes
                } else {
                    alert('Failed to delete supplier: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete supplier. Please try again.');
            });
        }

        // Handle form submission with AJAX
        document.getElementById('supplier-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeSupplierModal();
                    window.location.reload(); // Reload to see changes
                } else {
                    alert('Error: ' + (data.message || 'Failed to save supplier'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving. Please try again.');
            });
        });

        // Supplier search functionality
        function filterSuppliers(searchTerm) {
            const searchTermLower = searchTerm.toLowerCase().trim();
            const supplierItems = document.querySelectorAll('.supplier-item');
            
            if (!searchTermLower) {
                supplierItems.forEach(item => item.style.display = '');
                return;
            }
            
            supplierItems.forEach(item => {
                const name = item.dataset.name || '';
                const contact = item.dataset.contact || '';
                const email = item.dataset.email || '';
                const phone = item.dataset.phone || '';
                
                if (name.includes(searchTermLower) || 
                    contact.includes(searchTermLower) || 
                    email.includes(searchTermLower) || 
                    phone.includes(searchTermLower)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
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
