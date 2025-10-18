-- ================================================================
-- INVENTORY AND STOCK MANAGEMENT TABLES
-- ================================================================
-- This script creates tables for hotel inventory and stock management
-- Run this after the main database setup
-- ================================================================

USE inn_nexus;

-- ================================================================
-- INVENTORY CATEGORIES TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    parent_category_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_parent_category (parent_category_id),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),

    FOREIGN KEY (parent_category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SUPPLIERS TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    zip_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    website VARCHAR(255) NULL,
    notes TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INVENTORY ITEMS TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INT NOT NULL,
    supplier_id INT NULL,
    sku VARCHAR(100) NULL UNIQUE,
    barcode VARCHAR(100) NULL UNIQUE,
    unit_of_measure ENUM('pieces', 'kg', 'liters', 'boxes', 'packets', 'bottles', 'sets') DEFAULT 'pieces',
    minimum_stock_level INT DEFAULT 0,
    maximum_stock_level INT NULL,
    reorder_point INT DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) NULL,
    location VARCHAR(255) NULL,
    is_perishable TINYINT(1) DEFAULT 0,
    expiry_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_category (category_id),
    INDEX idx_supplier (supplier_id),
    INDEX idx_sku (sku),
    INDEX idx_barcode (barcode),
    INDEX idx_unit_of_measure (unit_of_measure),
    INDEX idx_location (location),
    INDEX idx_is_perishable (is_perishable),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),

    FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES inventory_suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INVENTORY STOCK TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    reserved_stock INT NOT NULL DEFAULT 0,
    available_stock INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,

    INDEX idx_item_id (item_id),
    INDEX idx_current_stock (current_stock),
    INDEX idx_available_stock (available_stock),
    INDEX idx_last_updated (last_updated),
    INDEX idx_updated_by (updated_by),

    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_item_stock (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INVENTORY STOCK HISTORY TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    operation_type ENUM('stock_in', 'stock_out', 'adjustment', 'reservation', 'release', 'expiry', 'damage') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reference_id VARCHAR(100) NULL,
    reference_type ENUM('purchase_order', 'sale', 'adjustment', 'reservation', 'transfer', 'expiry', 'damage') NULL,
    unit_cost DECIMAL(10,2) NULL,
    total_cost DECIMAL(10,2) NULL,
    notes TEXT NULL,
    performed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_item_id (item_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_reference_type (reference_type),
    INDEX idx_performed_by (performed_by),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INVENTORY PURCHASE ORDERS TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    status ENUM('draft', 'sent', 'confirmed', 'delivered', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_po_number (po_number),
    INDEX idx_supplier (supplier_id),
    INDEX idx_order_date (order_date),
    INDEX idx_expected_delivery (expected_delivery_date),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),

    FOREIGN KEY (supplier_id) REFERENCES inventory_suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INVENTORY PURCHASE ORDER ITEMS TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS inventory_purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    received_quantity INT DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_purchase_order (purchase_order_id),
    INDEX idx_item_id (item_id),
    INDEX idx_received_quantity (received_quantity),

    FOREIGN KEY (purchase_order_id) REFERENCES inventory_purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INSERT DEFAULT INVENTORY CATEGORIES
-- ================================================================

-- Insert categories without parent references first
INSERT INTO inventory_categories (name, description, is_active) VALUES
('Food & Beverages', 'Food items, beverages, and related products', 1),
('Cleaning Supplies', 'Cleaning products and maintenance supplies', 1),
('Linens & Towels', 'Bed linens, towels, and related textile items', 1),
('Room Amenities', 'Guest room supplies and amenities', 1),
('Office Supplies', 'Office and administrative supplies', 1),
('Electronics', 'Electronic equipment and accessories', 1),
('Furniture', 'Furniture and fixtures', 1),
('Kitchen Equipment', 'Kitchen appliances and equipment', 1),
('Maintenance', 'Maintenance and repair supplies', 1),
('Miscellaneous', 'Other inventory items not categorized elsewhere', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    is_active = 1;

-- ================================================================
-- INSERT SAMPLE SUPPLIERS
-- ================================================================

INSERT INTO inventory_suppliers (name, contact_person, email, phone, address, city, state, zip_code, country, is_active) VALUES
('Fresh Foods Supplier', 'John Smith', 'john@freshfoods.com', '+1-555-0123', '123 Market Street', 'Springfield', 'IL', '62701', 'USA', 1),
('Cleaning Supplies Co', 'Sarah Johnson', 'sarah@cleaningsupplies.com', '+1-555-0124', '456 Industrial Blvd', 'Springfield', 'IL', '62702', 'USA', 1),
('Linen & Textile Ltd', 'Mike Davis', 'mike@linentextile.com', '+1-555-0125', '789 Textile Ave', 'Springfield', 'IL', '62703', 'USA', 1),
('Hotel Amenities Inc', 'Lisa Wilson', 'lisa@hotelamenities.com', '+1-555-0126', '321 Guest Lane', 'Springfield', 'IL', '62704', 'USA', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    contact_person = VALUES(contact_person),
    email = VALUES(email),
    phone = VALUES(phone),
    is_active = 1;

-- ================================================================
-- INSERT SAMPLE INVENTORY ITEMS
-- ================================================================

INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, location, is_active)
SELECT
    'White Bath Towels', 'Standard white bath towels for guest rooms', (SELECT id FROM inventory_categories WHERE name = 'Linens & Towels' LIMIT 1), (SELECT id FROM inventory_suppliers WHERE name = 'Linen & Textile Ltd' LIMIT 1), 'TOWEL-WHITE-001', 'pieces', 50, 200, 75, 8.50, 'Linen Storage Room A', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inventory_items WHERE sku = 'TOWEL-WHITE-001');

INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, location, is_active)
SELECT
    'Shampoo Bottles', 'Individual shampoo bottles for guest rooms', (SELECT id FROM inventory_categories WHERE name = 'Room Amenities' LIMIT 1), (SELECT id FROM inventory_suppliers WHERE name = 'Hotel Amenities Inc' LIMIT 1), 'SHAMPOO-001', 'bottles', 100, 500, 150, 1.25, 'Amenities Storage B', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inventory_items WHERE sku = 'SHAMPOO-001');

INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, location, is_active)
SELECT
    'Coffee Packets', 'Individual coffee packets for guest rooms', (SELECT id FROM inventory_categories WHERE name = 'Food & Beverages' LIMIT 1), (SELECT id FROM inventory_suppliers WHERE name = 'Fresh Foods Supplier' LIMIT 1), 'COFFEE-001', 'packets', 200, 1000, 300, 0.75, 'Kitchen Storage C', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inventory_items WHERE sku = 'COFFEE-001');

INSERT INTO inventory_items (name, description, category_id, supplier_id, sku, unit_of_measure, minimum_stock_level, maximum_stock_level, reorder_point, unit_cost, location, is_active)
SELECT
    'All-Purpose Cleaner', 'Multi-surface cleaning solution', (SELECT id FROM inventory_categories WHERE name = 'Cleaning Supplies' LIMIT 1), (SELECT id FROM inventory_suppliers WHERE name = 'Cleaning Supplies Co' LIMIT 1), 'CLEANER-ALL-001', 'bottles', 20, 100, 30, 12.50, 'Maintenance Storage D', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM inventory_items WHERE sku = 'CLEANER-ALL-001');

-- ================================================================
-- INSERT INITIAL STOCK DATA
-- ================================================================

-- Insert initial stock for the sample items
INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock)
SELECT id, 150, 0, 150 FROM inventory_items WHERE sku = 'TOWEL-WHITE-001'
ON DUPLICATE KEY UPDATE
    current_stock = VALUES(current_stock),
    available_stock = VALUES(available_stock);

INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock)
SELECT id, 300, 0, 300 FROM inventory_items WHERE sku = 'SHAMPOO-001'
ON DUPLICATE KEY UPDATE
    current_stock = VALUES(current_stock),
    available_stock = VALUES(available_stock);

INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock)
SELECT id, 500, 0, 500 FROM inventory_items WHERE sku = 'COFFEE-001'
ON DUPLICATE KEY UPDATE
    current_stock = VALUES(current_stock),
    available_stock = VALUES(available_stock);

INSERT INTO inventory_stock (item_id, current_stock, reserved_stock, available_stock)
SELECT id, 50, 0, 50 FROM inventory_items WHERE sku = 'CLEANER-ALL-001'
ON DUPLICATE KEY UPDATE
    current_stock = VALUES(current_stock),
    available_stock = VALUES(available_stock);

-- ================================================================
-- CREATE TRIGGERS FOR AUTOMATIC STOCK CALCULATIONS
-- ================================================================

-- Trigger to update available stock when current_stock or reserved_stock changes
DROP TRIGGER IF EXISTS update_available_stock;
DELIMITER $$
CREATE TRIGGER update_available_stock
    BEFORE UPDATE ON inventory_stock
    FOR EACH ROW
BEGIN
    SET NEW.available_stock = NEW.current_stock - NEW.reserved_stock;
END$$
DELIMITER ;

-- ================================================================
-- CREATE VIEWS FOR EASY REPORTING
-- ================================================================

-- View for low stock alerts
CREATE OR REPLACE VIEW inventory_low_stock_alerts AS
SELECT
    i.id,
    i.name,
    i.sku,
    i.minimum_stock_level,
    s.current_stock,
    s.available_stock,
    c.name as category_name,
    i.location
FROM inventory_items i
JOIN inventory_stock s ON i.id = s.item_id
JOIN inventory_categories c ON i.category_id = c.id
WHERE i.is_active = 1
AND s.current_stock <= i.minimum_stock_level
ORDER BY s.current_stock ASC;

-- View for inventory summary by category
CREATE OR REPLACE VIEW inventory_category_summary AS
SELECT
    c.id as category_id,
    c.name as category_name,
    COUNT(i.id) as total_items,
    SUM(s.current_stock) as total_stock,
    SUM(s.current_stock * i.unit_cost) as total_value,
    COUNT(CASE WHEN s.current_stock <= i.minimum_stock_level THEN 1 END) as low_stock_items
FROM inventory_categories c
LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
LEFT JOIN inventory_stock s ON i.id = s.item_id
WHERE c.is_active = 1
GROUP BY c.id, c.name;

SELECT 'Inventory and stock management tables created successfully!' AS Status;
