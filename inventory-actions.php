<?php
// Inventory Actions - Backend operations for inventory management

use Dompdf\Dompdf;
use Dompdf\Options;
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth(['admin', 'manager']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_item':
        $itemId = (int)($_GET['id'] ?? 0);
        if (!$itemId) {
            throw new Exception('Item ID is required');
        }

            $pdo = getPdo();
            $stmt = $pdo->prepare("
                SELECT i.*, c.name as category_name, s.name as supplier_name
                FROM inventory_items i
                LEFT JOIN inventory_categories c ON i.category_id = c.id
                LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
                WHERE i.id = ?
            ");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception('Item not found');
            }

            echo json_encode(['success' => true, 'item' => $item]);
            break;

    case 'add_item':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'sku' => trim($_POST['sku'] ?? ''),
                'barcode' => trim($_POST['barcode'] ?? ''),
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
                'unit_of_measure' => $_POST['unit_of_measure'] ?? 'pieces',
                'unit_cost' => (float)($_POST['unit_cost'] ?? 0),
                'selling_price' => !empty($_POST['selling_price']) ? (float)$_POST['selling_price'] : null,
                'minimum_stock_level' => (int)($_POST['minimum_stock_level'] ?? 0),
                'maximum_stock_level' => !empty($_POST['maximum_stock_level']) ? (int)$_POST['maximum_stock_level'] : null,
                'reorder_point' => (int)($_POST['reorder_point'] ?? 0),
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'is_perishable' => isset($_POST['is_perishable']) ? 1 : 0,
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $_SESSION['user_id']
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Item name is required');
            }
            if (empty($data['category_id'])) {
                throw new Exception('Category is required');
            }

            $pdo = getPdo();

            // Check for duplicate SKU
            if (!empty($data['sku'])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE sku = ? AND is_active = 1");
                $stmt->execute([$data['sku']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('SKU already exists');
                }
            }

            // Check for duplicate barcode
            if (!empty($data['barcode'])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE barcode = ? AND is_active = 1");
                $stmt->execute([$data['barcode']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Barcode already exists');
                }
            }

            // Insert item
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO inventory_items (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $itemId = $pdo->lastInsertId();

            // Initialize stock
            $stmt = $pdo->prepare("INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock) VALUES (?, 0, 0, 0)");
            $stmt->execute([$itemId]);

            echo json_encode(['success' => true, 'message' => 'Item added successfully']);
            break;

        case 'edit_item':
            $itemId = (int)($_POST['item_id'] ?? 0);
            if (!$itemId) {
                throw new Exception('Item ID is required');
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'sku' => trim($_POST['sku'] ?? ''),
                'barcode' => trim($_POST['barcode'] ?? ''),
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
                'unit_of_measure' => $_POST['unit_of_measure'] ?? 'pieces',
                'unit_cost' => (float)($_POST['unit_cost'] ?? 0),
                'selling_price' => !empty($_POST['selling_price']) ? (float)$_POST['selling_price'] : null,
                'minimum_stock_level' => (int)($_POST['minimum_stock_level'] ?? 0),
                'maximum_stock_level' => !empty($_POST['maximum_stock_level']) ? (int)$_POST['maximum_stock_level'] : null,
                'reorder_point' => (int)($_POST['reorder_point'] ?? 0),
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'is_perishable' => isset($_POST['is_perishable']) ? 1 : 0,
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Item name is required');
            }
            if (empty($data['category_id'])) {
                throw new Exception('Category is required');
            }

            $pdo = getPdo();

            // Check if item exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_items WHERE id = ?");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                throw new Exception('Item not found');
            }

            // Check for duplicate SKU (excluding current item)
            if (!empty($data['sku'])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE sku = ? AND id != ? AND is_active = 1");
                $stmt->execute([$data['sku'], $itemId]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('SKU already exists');
                }
            }

            // Check for duplicate barcode (excluding current item)
            if (!empty($data['barcode'])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE barcode = ? AND id != ? AND is_active = 1");
                $stmt->execute([$data['barcode'], $itemId]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Barcode already exists');
                }
            }

            // Build update query
            $setParts = [];
            $values = [];
            foreach ($data as $field => $value) {
                $setParts[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $itemId; // For WHERE clause

            $sql = "UPDATE inventory_items SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
            break;

        case 'delete_item':
            $itemId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$itemId) {
                throw new Exception('Item ID is required');
            }

            $pdo = getPdo();

            // Check if item exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_items WHERE id = ?");
            $stmt->execute([$itemId]);
            if (!$stmt->fetch()) {
                throw new Exception('Item not found');
            }

            // Check if item has stock
            $stmt = $pdo->prepare("SELECT current_stock FROM inventory_stock WHERE item_id = ?");
            $stmt->execute([$itemId]);
            $stock = $stmt->fetchColumn();

            if ($stock > 0) {
                throw new Exception('Cannot delete item with existing stock. Please adjust stock to zero first.');
            }

            // Check if item is referenced in purchase orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_purchase_order_items WHERE item_id = ?");
            $stmt->execute([$itemId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete item that is referenced in purchase orders.');
            }

            // Soft delete by setting is_active to 0
            $stmt = $pdo->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
            $stmt->execute([$itemId]);

            // Also deactivate stock record
            $stmt = $pdo->prepare("UPDATE inventory_stock SET current_stock = 0, available_stock = 0 WHERE item_id = ?");
            $stmt->execute([$itemId]);

            echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
            break;

        case 'adjust_stock':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $adjustmentType = $_POST['adjustment_type'];
            $quantity = (int)($_POST['quantity'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $userId = $_SESSION['user_id'];

            if (!$itemId) {
                throw new Exception('Item ID is required');
            }
            if (!$adjustmentType) {
                throw new Exception('Adjustment type is required');
            }
            if (!$quantity) {
                throw new Exception('Quantity is required');
            }

            $pdo = getPdo();

            // Get current stock
            $stmt = $pdo->prepare("SELECT current_stock FROM inventory_stock WHERE item_id = ?");
            $stmt->execute([$itemId]);
            $currentStock = $stmt->fetchColumn();

            if ($currentStock === false) {
                throw new Exception('Item not found or no stock record exists');
            }

            $newStock = $currentStock;
            $operationType = '';

            switch ($adjustmentType) {
                case 'stock_in':
                    $newStock += $quantity;
                    $operationType = 'stock_in';
                    break;
                case 'stock_out':
                    if ($currentStock < $quantity) {
                        throw new Exception('Insufficient stock for this adjustment');
                    }
                    $newStock -= $quantity;
                    $operationType = 'stock_out';
                    break;
                case 'adjustment':
                    $newStock = $quantity;
                    $operationType = 'adjustment';
                    break;
                case 'expiry':
                    if ($currentStock < $quantity) {
                        throw new Exception('Insufficient stock for expiry adjustment');
                    }
                    $newStock -= $quantity;
                    $operationType = 'expiry';
                    break;
                case 'damage':
                    if ($currentStock < $quantity) {
                        throw new Exception('Insufficient stock for damage adjustment');
                    }
                    $newStock -= $quantity;
                    $operationType = 'damage';
                    break;
                default:
                    throw new Exception('Invalid adjustment type');
            }

            // Update stock
            $stmt = $pdo->prepare("UPDATE inventory_stock SET current_stock = ?, updated_by = ?, last_updated = NOW() WHERE item_id = ?");
            $stmt->execute([$newStock, $userId, $itemId]);

            // Record history
            $stmt = $pdo->prepare("
                INSERT INTO inventory_stock_history (item_id, operation_type, quantity, previous_stock, new_stock, notes, performed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$itemId, $operationType, abs($quantity), $currentStock, $newStock, $notes, $userId]);

            echo json_encode(['success' => true, 'message' => 'Stock adjusted successfully']);
            break;

        case 'get_category':
            $categoryId = (int)($_GET['id'] ?? 0);
            if (!$categoryId) {
                throw new Exception('Category ID is required');
            }

            $pdo = getPdo();
            $stmt = $pdo->prepare("SELECT * FROM inventory_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                throw new Exception('Category not found');
            }

            echo json_encode(['success' => true, 'category' => $category]);
            break;

        case 'add_category':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'parent_category_id' => !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $_SESSION['user_id']
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            $pdo = getPdo();

            // Check for duplicate category name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_categories WHERE name = ? AND is_active = 1");
            $stmt->execute([$data['name']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Category name already exists');
            }

            // Insert category
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO inventory_categories (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
            break;

        case 'edit_category':
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if (!$categoryId) {
                throw new Exception('Category ID is required');
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'parent_category_id' => !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            $pdo = getPdo();

            // Check if category exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            if (!$stmt->fetch()) {
                throw new Exception('Category not found');
            }

            // Check for duplicate category name (excluding current category)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_categories WHERE name = ? AND id != ? AND is_active = 1");
            $stmt->execute([$data['name'], $categoryId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Category name already exists');
            }

            // Build update query
            $setParts = [];
            $values = [];
            foreach ($data as $field => $value) {
                $setParts[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $categoryId; // For WHERE clause

            $sql = "UPDATE inventory_categories SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;

        case 'delete_category':
            $categoryId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$categoryId) {
                throw new Exception('Category ID is required');
            }

            $pdo = getPdo();

            // Check if category exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            if (!$stmt->fetch()) {
                throw new Exception('Category not found');
            }

            // Check if category has items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE category_id = ? AND is_active = 1");
            $stmt->execute([$categoryId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete category that contains items. Please move or delete items first.');
            }

            // Check if category has subcategories
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_categories WHERE parent_category_id = ? AND is_active = 1");
            $stmt->execute([$categoryId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete category that has subcategories. Please delete or move subcategories first.');
            }

            // Soft delete by setting is_active to 0
            $stmt = $pdo->prepare("UPDATE inventory_categories SET is_active = 0 WHERE id = ?");
            $stmt->execute([$categoryId]);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;

        case 'get_supplier':
            $supplierId = (int)($_GET['id'] ?? 0);
            if (!$supplierId) {
                throw new Exception('Supplier ID is required');
            }

            $pdo = getPdo();
            $stmt = $pdo->prepare("SELECT * FROM inventory_suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supplier) {
                throw new Exception('Supplier not found');
            }

            echo json_encode(['success' => true, 'supplier' => $supplier]);
            break;

        case 'add_supplier':
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'contact_person' => trim($_POST['contact_person'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $_SESSION['user_id']
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Supplier name is required');
            }

            $pdo = getPdo();

            // Check for duplicate supplier name
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_suppliers WHERE name = ? AND is_active = 1");
            $stmt->execute([$data['name']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Supplier name already exists');
            }

            // Insert supplier
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO inventory_suppliers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
            break;

        case 'edit_supplier':
            $supplierId = (int)($_POST['supplier_id'] ?? 0);
            if (!$supplierId) {
                throw new Exception('Supplier ID is required');
            }

            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'contact_person' => trim($_POST['contact_person'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'website' => trim($_POST['website'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            // Validation
            if (empty($data['name'])) {
                throw new Exception('Supplier name is required');
            }

            $pdo = getPdo();

            // Check if supplier exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            if (!$stmt->fetch()) {
                throw new Exception('Supplier not found');
            }

            // Check for duplicate supplier name (excluding current supplier)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_suppliers WHERE name = ? AND id != ? AND is_active = 1");
            $stmt->execute([$data['name'], $supplierId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Supplier name already exists');
            }

            // Build update query
            $setParts = [];
            $values = [];
            foreach ($data as $field => $value) {
                $setParts[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $supplierId; // For WHERE clause

            $sql = "UPDATE inventory_suppliers SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
            break;

        case 'delete_supplier':
            $supplierId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$supplierId) {
                throw new Exception('Supplier ID is required');
            }

            $pdo = getPdo();

            // Check if supplier exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_suppliers WHERE id = ?");
            $stmt->execute([$supplierId]);
            if (!$stmt->fetch()) {
                throw new Exception('Supplier not found');
            }

            // Check if supplier has items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE supplier_id = ? AND is_active = 1");
            $stmt->execute([$supplierId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete supplier that has associated items. Please reassign or delete items first.');
            }

            // Check if supplier has purchase orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_purchase_orders WHERE supplier_id = ?");
            $stmt->execute([$supplierId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete supplier that has associated purchase orders.');
            }

            // Soft delete by setting is_active to 0
            $stmt = $pdo->prepare("UPDATE inventory_suppliers SET is_active = 0 WHERE id = ?");
            $stmt->execute([$supplierId]);

            echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
            break;

        case 'get_purchase_order':
            $purchaseOrderId = (int)($_GET['id'] ?? 0);
            if (!$purchaseOrderId) {
                throw new Exception('Purchase Order ID is required');
            }

            $pdo = getPdo();
            $stmt = $pdo->prepare("SELECT * FROM inventory_purchase_orders WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);
            $purchaseOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseOrder) {
                throw new Exception('Purchase Order not found');
            }

            echo json_encode(['success' => true, 'purchase_order' => $purchaseOrder]);
            break;

        case 'get_purchase_order_items':
            $purchaseOrderId = (int)($_GET['id'] ?? 0);
            if (!$purchaseOrderId) {
                throw new Exception('Purchase Order ID is required');
            }

            $pdo = getPdo();
            $stmt = $pdo->prepare("SELECT * FROM inventory_purchase_order_items WHERE purchase_order_id = ?");
            $stmt->execute([$purchaseOrderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'items' => $items]);
            break;

        case 'add_purchase_order':
            $data = [
                'po_number' => trim($_POST['po_number'] ?? ''),
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'order_date' => $_POST['order_date'] ?? '',
                'expected_delivery_date' => !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null,
                'status' => $_POST['status'] ?? 'draft',
                'notes' => trim($_POST['notes'] ?? ''),
                'created_by' => $_SESSION['user_id']
            ];

            // Validation
            if (empty($data['po_number'])) {
                throw new Exception('PO Number is required');
            }
            if (empty($data['supplier_id'])) {
                throw new Exception('Supplier is required');
            }
            if (empty($data['order_date'])) {
                throw new Exception('Order Date is required');
            }

            $pdo = getPdo();

            // Check for duplicate PO number
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_purchase_orders WHERE po_number = ?");
            $stmt->execute([$data['po_number']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('PO Number already exists');
            }

            // Insert purchase order
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO inventory_purchase_orders (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $purchaseOrderId = $pdo->lastInsertId();

            // Insert purchase order items
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['item_id']) && !empty($item['quantity']) && !empty($item['unit_cost'])) {
                        $itemData = [
                            'purchase_order_id' => $purchaseOrderId,
                            'item_id' => (int)$item['item_id'],
                            'quantity' => (int)$item['quantity'],
                            'unit_cost' => (float)$item['unit_cost'],
                            'total_cost' => (int)$item['quantity'] * (float)$item['unit_cost']
                        ];

                        $itemFields = array_keys($itemData);
                        $itemValues = array_values($itemData);
                        $itemPlaceholders = array_fill(0, count($itemFields), '?');

                        $itemSql = "INSERT INTO inventory_purchase_order_items (" . implode(', ', $itemFields) . ") VALUES (" . implode(', ', $itemPlaceholders) . ")";
                        $itemStmt = $pdo->prepare($itemSql);
                        $itemStmt->execute($itemValues);
                    }
                }
            }

            // Update total amount
            $totalStmt = $pdo->prepare("
                UPDATE inventory_purchase_orders
                SET total_amount = (
                    SELECT COALESCE(SUM(total_cost), 0)
                    FROM inventory_purchase_order_items
                    WHERE purchase_order_id = ?
                )
                WHERE id = ?
            ");
            $totalStmt->execute([$purchaseOrderId, $purchaseOrderId]);

            echo json_encode(['success' => true, 'message' => 'Purchase Order added successfully']);
            break;

        case 'edit_purchase_order':
            $purchaseOrderId = (int)($_POST['purchase_order_id'] ?? 0);
            if (!$purchaseOrderId) {
                throw new Exception('Purchase Order ID is required');
            }

            $data = [
                'po_number' => trim($_POST['po_number'] ?? ''),
                'supplier_id' => (int)($_POST['supplier_id'] ?? 0),
                'order_date' => $_POST['order_date'] ?? '',
                'expected_delivery_date' => !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null,
                'status' => $_POST['status'] ?? 'draft',
                'notes' => trim($_POST['notes'] ?? '')
            ];

            // Validation
            if (empty($data['po_number'])) {
                throw new Exception('PO Number is required');
            }
            if (empty($data['supplier_id'])) {
                throw new Exception('Supplier is required');
            }
            if (empty($data['order_date'])) {
                throw new Exception('Order Date is required');
            }

            $pdo = getPdo();

            // Check if purchase order exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_purchase_orders WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);
            if (!$stmt->fetch()) {
                throw new Exception('Purchase Order not found');
            }

            // Check for duplicate PO number (excluding current PO)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_purchase_orders WHERE po_number = ? AND id != ?");
            $stmt->execute([$data['po_number'], $purchaseOrderId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('PO Number already exists');
            }

            // Build update query
            $setParts = [];
            $values = [];
            foreach ($data as $field => $value) {
                $setParts[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $purchaseOrderId; // For WHERE clause

            $sql = "UPDATE inventory_purchase_orders SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            // Delete existing items and insert new ones
            $deleteStmt = $pdo->prepare("DELETE FROM inventory_purchase_order_items WHERE purchase_order_id = ?");
            $deleteStmt->execute([$purchaseOrderId]);

            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['item_id']) && !empty($item['quantity']) && !empty($item['unit_cost'])) {
                        $itemData = [
                            'purchase_order_id' => $purchaseOrderId,
                            'item_id' => (int)$item['item_id'],
                            'quantity' => (int)$item['quantity'],
                            'unit_cost' => (float)$item['unit_cost'],
                            'total_cost' => (int)$item['quantity'] * (float)$item['unit_cost']
                        ];

                        $itemFields = array_keys($itemData);
                        $itemValues = array_values($itemData);
                        $itemPlaceholders = array_fill(0, count($itemFields), '?');

                        $itemSql = "INSERT INTO inventory_purchase_order_items (" . implode(', ', $itemFields) . ") VALUES (" . implode(', ', $itemPlaceholders) . ")";
                        $itemStmt = $pdo->prepare($itemSql);
                        $itemStmt->execute($itemValues);
                    }
                }
            }

            // Update total amount
            $totalStmt = $pdo->prepare("
                UPDATE inventory_purchase_orders
                SET total_amount = (
                    SELECT COALESCE(SUM(total_cost), 0)
                    FROM inventory_purchase_order_items
                    WHERE purchase_order_id = ?
                )
                WHERE id = ?
            ");
            $totalStmt->execute([$purchaseOrderId, $purchaseOrderId]);

            echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully']);
            break;

        case 'delete_purchase_order':
            $purchaseOrderId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if (!$purchaseOrderId) {
                throw new Exception('Purchase Order ID is required');
            }

            $pdo = getPdo();

            // Check if purchase order exists
            $stmt = $pdo->prepare("SELECT id FROM inventory_purchase_orders WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);
            if (!$stmt->fetch()) {
                throw new Exception('Purchase Order not found');
            }

            // Check if purchase order is delivered (cannot delete delivered orders)
            $stmt = $pdo->prepare("SELECT status FROM inventory_purchase_orders WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);
            $status = $stmt->fetchColumn();

            if ($status === 'delivered') {
                throw new Exception('Cannot delete delivered purchase orders');
            }

            // Delete purchase order items first
            $deleteItemsStmt = $pdo->prepare("DELETE FROM inventory_purchase_order_items WHERE purchase_order_id = ?");
            $deleteItemsStmt->execute([$purchaseOrderId]);

            // Delete purchase order
            $deleteStmt = $pdo->prepare("DELETE FROM inventory_purchase_orders WHERE id = ?");
            $deleteStmt->execute([$purchaseOrderId]);

            echo json_encode(['success' => true, 'message' => 'Purchase Order deleted successfully']);
            break;

        case 'generate_low_stock_report':
            require_once __DIR__ . '/vendor/autoload.php';

            $pdo = getPdo();
            $stmt = $pdo->query("
                SELECT i.name, i.sku, i.barcode, i.unit_of_measure, i.minimum_stock_level, i.reorder_point,
                       s.current_stock, s.available_stock, c.name as category_name, sup.name as supplier_name,
                       i.unit_cost, i.selling_price, i.location
                FROM inventory_items i
                JOIN inventory_stock s ON i.id = s.item_id
                LEFT JOIN inventory_categories c ON i.category_id = c.id
                LEFT JOIN inventory_suppliers sup ON i.supplier_id = sup.id
                WHERE i.is_active = 1 AND s.current_stock < i.minimum_stock_level
                ORDER BY (s.current_stock - i.minimum_stock_level) ASC, i.name
            ");
            $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate HTML content
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Low Stock Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .report-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                    .report-date { color: #666; font-size: 14px; }
                    .summary { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                    .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .critical { background-color: #ffebee; }
                    .warning { background-color: #fff3e0; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="report-title">Low Stock Report</div>
                    <div class="report-date">Generated on: ' . date('F j, Y \a\t g:i A') . '</div>
                </div>

                <div class="summary">
                    <h3>Summary</h3>
                    <div class="summary-grid">
                        <div>
                            <strong>Total Items Below Minimum Stock:</strong> ' . count($lowStockItems) . '
                        </div>
                        <div>
                            <strong>Report Generated By:</strong> ' . ($_SESSION['user_name'] ?? 'System') . '
                        </div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Reorder Point</th>
                            <th>Unit Cost</th>
                            <th>Total Value</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($lowStockItems as $item) {
                $shortage = $item['minimum_stock_level'] - $item['current_stock'];
                $totalValue = $item['current_stock'] * $item['unit_cost'];
                $status = $shortage > $item['reorder_point'] ? 'Critical' : 'Warning';
                $rowClass = $status === 'Critical' ? 'critical' : 'warning';

                $html .= '
                        <tr class="' . $rowClass . '">
                            <td>' . htmlspecialchars($item['name']) . '</td>
                            <td>' . htmlspecialchars($item['sku'] ?? '') . '</td>
                            <td>' . htmlspecialchars($item['category_name'] ?? 'N/A') . '</td>
                            <td>' . htmlspecialchars($item['supplier_name'] ?? 'N/A') . '</td>
                            <td class="text-center">' . number_format($item['current_stock']) . '</td>
                            <td class="text-center">' . number_format($item['minimum_stock_level']) . '</td>
                            <td class="text-center">' . number_format($item['reorder_point']) . '</td>
                            <td class="text-right">$' . number_format($item['unit_cost'], 2) . '</td>
                            <td class="text-right">$' . number_format($totalValue, 2) . '</td>
                            <td>' . htmlspecialchars($item['location'] ?? 'N/A') . '</td>
                            <td>' . $status . '</td>
                        </tr>';
            }

            $html .= '
                    </tbody>
                </table>

                <div class="footer">
                    <p>This report contains confidential inventory information. Handle appropriately.</p>
                </div>
            </body>
            </html>';

            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $dompdf->stream('low-stock-report.pdf', ['Attachment' => true]);
            break;

        case 'generate_inventory_value_report':
            require_once __DIR__ . '/vendor/autoload.php';

            $pdo = getPdo();

            // Get inventory value by category
            $stmt = $pdo->query("
                SELECT c.name as category_name, c.description as category_description,
                       COUNT(i.id) as item_count,
                       SUM(s.current_stock * i.unit_cost) as total_cost_value,
                       SUM(s.current_stock * i.selling_price) as total_selling_value,
                       AVG(i.unit_cost) as avg_unit_cost,
                       MAX(i.unit_cost) as max_unit_cost,
                       MIN(i.unit_cost) as min_unit_cost
                FROM inventory_categories c
                LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
                LEFT JOIN inventory_stock s ON i.id = s.item_id
                WHERE c.is_active = 1
                GROUP BY c.id, c.name, c.description
                ORDER BY total_cost_value DESC
            ");
            $categoryValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get overall totals
            $totalStmt = $pdo->query("
                SELECT SUM(s.current_stock * i.unit_cost) as total_cost_value,
                       SUM(s.current_stock * i.selling_price) as total_selling_value,
                       COUNT(i.id) as total_items
                FROM inventory_items i
                JOIN inventory_stock s ON i.id = s.item_id
                WHERE i.is_active = 1
            ");
            $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

            // Generate HTML content
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Inventory Value Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .report-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                    .report-date { color: #666; font-size: 14px; }
                    .summary { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                    .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                    .highlight { background-color: #e3f2fd; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="report-title">Inventory Value Report</div>
                    <div class="report-date">Generated on: ' . date('F j, Y \a\t g:i A') . '</div>
                </div>

                <div class="summary">
                    <h3>Overall Summary</h3>
                    <div class="summary-grid">
                        <div>
                            <strong>Total Inventory Cost Value:</strong> $' . number_format($totals['total_cost_value'] ?? 0, 2) . '
                        </div>
                        <div>
                            <strong>Total Inventory Selling Value:</strong> $' . number_format($totals['total_selling_value'] ?? 0, 2) . '
                        </div>
                        <div>
                            <strong>Total Items:</strong> ' . number_format($totals['total_items'] ?? 0) . '
                        </div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Item Count</th>
                            <th>Total Cost Value</th>
                            <th>Total Selling Value</th>
                            <th>Avg Unit Cost</th>
                            <th>Price Range</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($categoryValues as $category) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($category['category_name']) . '</td>
                            <td>' . htmlspecialchars($category['category_description'] ?? 'N/A') . '</td>
                            <td class="text-center">' . number_format($category['item_count']) . '</td>
                            <td class="text-right">$' . number_format($category['total_cost_value'] ?? 0, 2) . '</td>
                            <td class="text-right">$' . number_format($category['total_selling_value'] ?? 0, 2) . '</td>
                            <td class="text-right">$' . number_format($category['avg_unit_cost'] ?? 0, 2) . '</td>
                            <td class="text-right">$' . number_format($category['min_unit_cost'] ?? 0, 2) . ' - $' . number_format($category['max_unit_cost'] ?? 0, 2) . '</td>
                        </tr>';
            }

            $html .= '
                    </tbody>
                </table>

                <div class="footer">
                    <p>This report shows the current inventory value by category. Values are calculated based on current stock levels and unit costs.</p>
                    <p>Report generated by ' . ($_SESSION['user_name'] ?? 'System') . '</p>
                </div>
            </body>
            </html>';

            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $dompdf->stream('inventory-value-report.pdf', ['Attachment' => true]);
            break;

        case 'generate_stock_movement_report':
            require_once __DIR__ . '/vendor/autoload.php';

            $pdo = getPdo();

            // Get recent stock movements (last 30 days)
            $stmt = $pdo->query("
                SELECT h.created_at, i.name, i.sku, c.name as category_name,
                       h.operation_type, h.quantity, h.previous_stock, h.new_stock,
                       h.notes, u.name as performed_by
                FROM inventory_stock_history h
                JOIN inventory_items i ON h.item_id = i.id
                LEFT JOIN inventory_categories c ON i.category_id = c.id
                LEFT JOIN users u ON h.performed_by = u.id
                WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY h.created_at DESC
                LIMIT 100
            ");
            $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get movement summary
            $summaryStmt = $pdo->query("
                SELECT operation_type, COUNT(*) as count, SUM(quantity) as total_quantity
                FROM inventory_stock_history
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY operation_type
            ");
            $summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate HTML content
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Stock Movement Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .report-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                    .report-date { color: #666; font-size: 14px; }
                    .summary { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
                    .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                    .stock-in { background-color: #e8f5e8; }
                    .stock-out { background-color: #ffebee; }
                    .adjustment { background-color: #fff3e0; }
                    .expiry { background-color: #fce4ec; }
                    .damage { background-color: #f3e5f5; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="report-title">Stock Movement Report (Last 30 Days)</div>
                    <div class="report-date">Generated on: ' . date('F j, Y \a\t g:i A') . '</div>
                </div>

                <div class="summary">
                    <h3>Movement Summary</h3>
                    <div class="summary-grid">';

            foreach ($summary as $item) {
                $html .= '
                        <div>
                            <strong>' . ucwords(str_replace('_', ' ', $item['operation_type'])) . ':</strong> ' . number_format($item['total_quantity']) . ' units (' . $item['count'] . ' transactions)
                        </div>';
            }

            $html .= '
                    </div>
                    <p><strong>Total Transactions:</strong> ' . array_sum(array_column($summary, 'count')) . '</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Item</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Operation</th>
                            <th>Quantity</th>
                            <th>Previous Stock</th>
                            <th>New Stock</th>
                            <th>Performed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($movements as $movement) {
                $operationClass = strtolower(str_replace('_', '-', $movement['operation_type']));
                $operationLabel = ucwords(str_replace('_', ' ', $movement['operation_type']));

                $html .= '
                        <tr class="' . $operationClass . '">
                            <td>' . date('M j, Y g:i A', strtotime($movement['created_at'])) . '</td>
                            <td>' . htmlspecialchars($movement['name']) . '</td>
                            <td>' . htmlspecialchars($movement['sku'] ?? '') . '</td>
                            <td>' . htmlspecialchars($movement['category_name'] ?? 'N/A') . '</td>
                            <td>' . $operationLabel . '</td>
                            <td class="text-right">' . number_format($movement['quantity']) . '</td>
                            <td class="text-right">' . number_format($movement['previous_stock']) . '</td>
                            <td class="text-right">' . number_format($movement['new_stock']) . '</td>
                            <td>' . htmlspecialchars($movement['performed_by'] ?? 'System') . '</td>
                            <td>' . htmlspecialchars($movement['notes'] ?? '') . '</td>
                        </tr>';
            }

            $html .= '
                    </tbody>
                </table>

                <div class="footer">
                    <p>This report shows all stock movements in the last 30 days. Stock adjustments, receipts, and issues are tracked for audit purposes.</p>
                    <p>Report generated by ' . ($_SESSION['user_name'] ?? 'System') . '</p>
                </div>
            </body>
            </html>';

            // Generate PDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $dompdf->stream('stock-movement-report.pdf', ['Attachment' => true]);
            break;

        default:
            throw new Exception('Invalid action');
    }
