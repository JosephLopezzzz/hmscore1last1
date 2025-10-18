<?php
// Inventory Actions Handler
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';

// Check authentication
requireAuth(['admin', 'manager', 'staff']);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
            $pdo = getPdo();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    switch ($action) {
        case 'add_category':
            handleAddCategory($pdo);
            break;
        case 'edit_category':
            handleEditCategory($pdo);
            break;
        case 'delete_category':
            handleDeleteCategory($pdo);
            break;
        case 'get_category':
            handleGetCategory($pdo);
            break;
        case 'add_supplier':
            handleAddSupplier($pdo);
            break;
        case 'edit_supplier':
            handleEditSupplier($pdo);
            break;
        case 'delete_supplier':
            handleDeleteSupplier($pdo);
            break;
        case 'get_supplier':
            handleGetSupplier($pdo);
            break;
    case 'add_item':
            handleAddItem($pdo);
            break;
        case 'edit_item':
            handleEditItem($pdo);
            break;
        case 'delete_item':
            handleDeleteItem($pdo);
            break;
        case 'get_item':
            handleGetItem($pdo);
            break;
        case 'adjust_stock':
            handleAdjustStock($pdo);
                    break;
        case 'add_purchase_order':
            handleAddPurchaseOrder($pdo);
                    break;
        case 'edit_purchase_order':
            handleEditPurchaseOrder($pdo);
                    break;
        case 'delete_purchase_order':
            handleDeletePurchaseOrder($pdo);
                    break;
        case 'get_purchase_order':
            handleGetPurchaseOrder($pdo);
            break;
        case 'get_purchase_order_items':
            handleGetPurchaseOrderItems($pdo);
            break;
        case 'update_po_status':
            handleUpdatePOStatus($pdo);
            break;
                default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Category Functions
function handleAddCategory($pdo) {
    $rules = [
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 100],
        'description' => ['type' => 'string', 'max_length' => 500],
        'parent_category_id' => ['type' => 'int'],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $stmt = $pdo->prepare("
        INSERT INTO inventory_categories (name, description, parent_category_id, is_active, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['parent_category_id'] ?? null,
        $data['is_active'] ?? 1,
        $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
}

function handleEditCategory($pdo) {
    $rules = [
        'category_id' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 100],
        'description' => ['type' => 'string', 'max_length' => 500],
        'parent_category_id' => ['type' => 'int'],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $stmt = $pdo->prepare("
        UPDATE inventory_categories 
        SET name = ?, description = ?, parent_category_id = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['parent_category_id'] ?? null,
        $data['is_active'] ?? 1,
        $data['category_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
}

function handleDeleteCategory($pdo) {
    $category_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$category_id) {
        echo json_encode(['success' => false, 'message' => 'Category ID required']);
        return;
            }

            // Check if category has items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE category_id = ? AND is_active = 1");
    $stmt->execute([$category_id]);
    $itemCount = $stmt->fetchColumn();
    
    if ($itemCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete category with active items']);
        return;
    }
    
            $stmt = $pdo->prepare("UPDATE inventory_categories SET is_active = 0 WHERE id = ?");
    $stmt->execute([$category_id]);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
}

function handleGetCategory($pdo) {
    $category_id = $_GET['id'] ?? null;
    
    if (!$category_id) {
        echo json_encode(['success' => false, 'message' => 'Category ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM inventory_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'category' => $category]);
}

// Supplier Functions
function handleAddSupplier($pdo) {
    $rules = [
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 255],
        'contact_person' => ['type' => 'string', 'max_length' => 255],
        'email' => ['type' => 'email'],
        'phone' => ['type' => 'string', 'max_length' => 50],
        'address' => ['type' => 'string', 'max_length' => 500],
        'city' => ['type' => 'string', 'max_length' => 100],
        'state' => ['type' => 'string', 'max_length' => 100],
        'zip_code' => ['type' => 'string', 'max_length' => 20],
        'country' => ['type' => 'string', 'max_length' => 100],
        'website' => ['type' => 'string', 'max_length' => 255],
        'notes' => ['type' => 'string', 'max_length' => 1000],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $stmt = $pdo->prepare("
        INSERT INTO inventory_suppliers (name, contact_person, email, phone, address, city, state, zip_code, country, website, notes, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['contact_person'] ?? null,
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $data['address'] ?? null,
        $data['city'] ?? null,
        $data['state'] ?? null,
        $data['zip_code'] ?? null,
        $data['country'] ?? null,
        $data['website'] ?? null,
        $data['notes'] ?? null,
        $data['is_active'] ?? 1,
        $_SESSION['user_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
}

function handleEditSupplier($pdo) {
    $rules = [
        'supplier_id' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 255],
        'contact_person' => ['type' => 'string', 'max_length' => 255],
        'email' => ['type' => 'email'],
        'phone' => ['type' => 'string', 'max_length' => 50],
        'address' => ['type' => 'string', 'max_length' => 500],
        'city' => ['type' => 'string', 'max_length' => 100],
        'state' => ['type' => 'string', 'max_length' => 100],
        'zip_code' => ['type' => 'string', 'max_length' => 20],
        'country' => ['type' => 'string', 'max_length' => 100],
        'website' => ['type' => 'string', 'max_length' => 255],
        'notes' => ['type' => 'string', 'max_length' => 1000],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $stmt = $pdo->prepare("
        UPDATE inventory_suppliers 
        SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, country = ?, website = ?, notes = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['contact_person'] ?? null,
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $data['address'] ?? null,
        $data['city'] ?? null,
        $data['state'] ?? null,
        $data['zip_code'] ?? null,
        $data['country'] ?? null,
        $data['website'] ?? null,
        $data['notes'] ?? null,
        $data['is_active'] ?? 1,
        $data['supplier_id']
    ]);

            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
}

function handleDeleteSupplier($pdo) {
    $supplier_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$supplier_id) {
        echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
        return;
            }

            // Check if supplier has items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE supplier_id = ? AND is_active = 1");
    $stmt->execute([$supplier_id]);
    $itemCount = $stmt->fetchColumn();
    
    if ($itemCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete supplier with active items']);
        return;
    }
    
            $stmt = $pdo->prepare("UPDATE inventory_suppliers SET is_active = 0 WHERE id = ?");
    $stmt->execute([$supplier_id]);

            echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
}

function handleGetSupplier($pdo) {
    $supplier_id = $_GET['id'] ?? null;
    
    if (!$supplier_id) {
        echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM inventory_suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$supplier) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'supplier' => $supplier]);
}

// Item Functions
function handleAddItem($pdo) {
    $rules = [
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 255],
        'description' => ['type' => 'string', 'max_length' => 1000],
        'category_id' => ['type' => 'int', 'required' => true],
        'supplier_id' => ['type' => 'int'],
        'sku' => ['type' => 'string', 'max_length' => 100],
        'barcode' => ['type' => 'string', 'max_length' => 100],
        'unit_of_measure' => ['type' => 'string', 'required' => true],
        'minimum_stock_level' => ['type' => 'int', 'min' => 0],
        'maximum_stock_level' => ['type' => 'int', 'min' => 0],
        'reorder_point' => ['type' => 'int', 'min' => 0],
        'unit_cost' => ['type' => 'numeric', 'min' => 0],
        'selling_price' => ['type' => 'numeric', 'min' => 0],
        'location' => ['type' => 'string', 'max_length' => 255],
        'is_perishable' => ['type' => 'int'],
        'expiry_date' => ['type' => 'date'],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, barcode, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, selling_price, location, is_perishable, expiry_date, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'],
            $data['supplier_id'] ?? null,
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['unit_of_measure'],
            $data['minimum_stock_level'] ?? 0,
            $data['maximum_stock_level'] ?? null,
            $data['reorder_point'] ?? 0,
            $data['unit_cost'] ?? 0,
            $data['selling_price'] ?? null,
            $data['location'] ?? null,
            $data['is_perishable'] ?? 0,
            $data['expiry_date'] ?? null,
            $data['is_active'] ?? 1,
            $_SESSION['user_id']
        ]);
        
        $item_id = $pdo->lastInsertId();
        
        // Create initial stock record
        $stmt = $pdo->prepare("
            INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock, updated_by)
            VALUES (?, 0, 0, 0, ?)
        ");
        $stmt->execute([$item_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Item added successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleEditItem($pdo) {
    $rules = [
        'item_id' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'string', 'required' => true, 'max_length' => 255],
        'description' => ['type' => 'string', 'max_length' => 1000],
        'category_id' => ['type' => 'int', 'required' => true],
        'supplier_id' => ['type' => 'int'],
        'sku' => ['type' => 'string', 'max_length' => 100],
        'barcode' => ['type' => 'string', 'max_length' => 100],
        'unit_of_measure' => ['type' => 'string', 'required' => true],
        'minimum_stock_level' => ['type' => 'int', 'min' => 0],
        'maximum_stock_level' => ['type' => 'int', 'min' => 0],
        'reorder_point' => ['type' => 'int', 'min' => 0],
        'unit_cost' => ['type' => 'numeric', 'min' => 0],
        'selling_price' => ['type' => 'numeric', 'min' => 0],
        'location' => ['type' => 'string', 'max_length' => 255],
        'is_perishable' => ['type' => 'int'],
        'expiry_date' => ['type' => 'date'],
        'is_active' => ['type' => 'int']
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $stmt = $pdo->prepare("
        UPDATE inventory_items 
        SET name = ?, description = ?, category_id = ?, supplier_id = ?, sku = ?, barcode = ?, unit_of_measure = ?, minimum_stock_level = ?, maximum_stock_level = ?, reorder_point = ?, unit_cost = ?, selling_price = ?, location = ?, is_perishable = ?, expiry_date = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['category_id'],
        $data['supplier_id'] ?? null,
        $data['sku'] ?? null,
        $data['barcode'] ?? null,
        $data['unit_of_measure'],
        $data['minimum_stock_level'] ?? 0,
        $data['maximum_stock_level'] ?? null,
        $data['reorder_point'] ?? 0,
        $data['unit_cost'] ?? 0,
        $data['selling_price'] ?? null,
        $data['location'] ?? null,
        $data['is_perishable'] ?? 0,
        $data['expiry_date'] ?? null,
        $data['is_active'] ?? 1,
        $data['item_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
}

function handleDeleteItem($pdo) {
    $item_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Item ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
    $stmt->execute([$item_id]);
    
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
}

function handleGetItem($pdo) {
    $item_id = $_GET['id'] ?? null;
    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Item ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'item' => $item]);
}

function handleAdjustStock($pdo) {
    $rules = [
        'item_id' => ['type' => 'int', 'required' => true],
        'adjustment_type' => ['type' => 'string', 'required' => true],
        'quantity' => ['type' => 'int', 'required' => true],
        'notes' => ['type' => 'string', 'max_length' => 500]
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    $pdo->beginTransaction();
    
    try {
        // Get current stock
        $stmt = $pdo->prepare("SELECT current_stock FROM inventory_stock WHERE item_id = ?");
        $stmt->execute([$data['item_id']]);
        $currentStock = $stmt->fetchColumn();
        
        if ($currentStock === false) {
            throw new Exception('Item stock record not found');
        }
        
        // Calculate new stock
        $adjustment = $data['adjustment_type'] === 'add' ? $data['quantity'] : -$data['quantity'];
        $newStock = max(0, $currentStock + $adjustment);
        
        // Update stock
        $stmt = $pdo->prepare("
            UPDATE inventory_stock 
            SET current_stock = ?, available_stock = current_stock - reserved_stock, last_updated = NOW(), updated_by = ?
            WHERE item_id = ?
        ");
        $stmt->execute([$newStock, $_SESSION['user_id'], $data['item_id']]);
        
        // Record in history
        $operationType = $data['adjustment_type'] === 'add' ? 'stock_in' : 'stock_out';
        $stmt = $pdo->prepare("
            INSERT INTO inventory_stock_history (item_id, operation_type, quantity, previous_stock, new_stock, reference_type, notes, performed_by)
            VALUES (?, ?, ?, ?, ?, 'adjustment', ?, ?)
        ");
        $stmt->execute([
            $data['item_id'],
            $operationType,
            $data['quantity'],
            $currentStock,
            $newStock,
            $data['notes'] ?? null,
            $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Stock adjusted successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Purchase Order Functions
function handleAddPurchaseOrder($pdo) {
    $rules = [
        'po_number' => ['type' => 'string', 'required' => true, 'max_length' => 50],
        'supplier_id' => ['type' => 'int', 'required' => true],
        'order_date' => ['type' => 'date', 'required' => true],
        'expected_delivery_date' => ['type' => 'date'],
        'status' => ['type' => 'string', 'required' => true],
        'notes' => ['type' => 'string', 'max_length' => 1000],
        'items' => ['type' => 'string', 'required' => true] // JSON string
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    $items = json_decode($data['items'], true);
    
    if (!$items || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'Invalid items data']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create purchase order
        $stmt = $pdo->prepare("
            INSERT INTO inventory_purchase_orders (po_number, supplier_id, order_date, expected_delivery_date, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['po_number'],
            $data['supplier_id'],
            $data['order_date'],
            $data['expected_delivery_date'] ?? null,
            $data['status'],
            $data['notes'] ?? null,
            $_SESSION['user_id']
        ]);
        
        $po_id = $pdo->lastInsertId();
        $totalAmount = 0;
        
        // Add items
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['unit_cost'];
            $totalAmount += $itemTotal;
            
            $stmt = $pdo->prepare("
                INSERT INTO inventory_purchase_order_items (purchase_order_id, item_id, quantity, unit_cost, total_cost)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $po_id,
                $item['item_id'],
                $item['quantity'],
                $item['unit_cost'],
                $itemTotal
            ]);
        }
        
        // Update total amount
        $stmt = $pdo->prepare("UPDATE inventory_purchase_orders SET total_amount = ? WHERE id = ?");
        $stmt->execute([$totalAmount, $po_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase order created successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleEditPurchaseOrder($pdo) {
    // Similar to add but with UPDATE
    echo json_encode(['success' => false, 'message' => 'Edit purchase order not implemented yet']);
}

function handleDeletePurchaseOrder($pdo) {
    $po_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$po_id) {
        echo json_encode(['success' => false, 'message' => 'Purchase order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE inventory_purchase_orders SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$po_id]);
    
    echo json_encode(['success' => true, 'message' => 'Purchase order cancelled successfully']);
}

function handleGetPurchaseOrder($pdo) {
    $po_id = $_GET['id'] ?? null;
    
    if (!$po_id) {
        echo json_encode(['success' => false, 'message' => 'Purchase order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM inventory_purchase_orders WHERE id = ?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        echo json_encode(['success' => false, 'message' => 'Purchase order not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'purchase_order' => $po]);
}

function handleGetPurchaseOrderItems($pdo) {
    $po_id = $_GET['id'] ?? null;
    
    if (!$po_id) {
        echo json_encode(['success' => false, 'message' => 'Purchase order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT poi.*, i.name as item_name, i.sku
        FROM inventory_purchase_order_items poi
        JOIN inventory_items i ON poi.item_id = i.id
        WHERE poi.purchase_order_id = ?
        ORDER BY poi.id
    ");
    $stmt->execute([$po_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'items' => $items]);
}

function handleUpdatePOStatus($pdo) {
    $rules = [
        'po_id' => ['type' => 'int', 'required' => true],
        'status' => ['type' => 'string', 'required' => true]
    ];
    
    $validation = sanitizeFormData($_POST, $rules);
    
    if (!$validation['is_valid']) {
        echo json_encode(['success' => false, 'message' => implode(' ', $validation['errors'])]);
        return;
    }
    
    $data = $validation['data'];
    
    // Validate status
    $validStatuses = ['draft', 'sent', 'confirmed', 'delivered', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE inventory_purchase_orders 
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$data['status'], $data['po_id']]);
    
    $statusMessages = [
        'sent' => 'Purchase order sent successfully',
        'confirmed' => 'Purchase order confirmed successfully',
        'delivered' => 'Purchase order marked as delivered',
        'cancelled' => 'Purchase order cancelled successfully'
    ];
    
    $message = $statusMessages[$data['status']] ?? 'Status updated successfully';
    echo json_encode(['success' => true, 'message' => $message]);
}
?>