-- Complete integration script - step by step
USE inn_nexus;

-- Step 1: Add missing columns to rooms table
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_number VARCHAR(10) NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single';
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS floor_number INT DEFAULT 1;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS max_guests INT DEFAULT 2;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS rate DECIMAL(10,2) DEFAULT 1500.00;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS amenities TEXT NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS last_cleaned TIMESTAMP NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS maintenance_notes TEXT NULL;

-- Step 2: Add missing columns to reservations table
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) NULL;
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS special_requests TEXT NULL;
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Step 3: Create billing_transactions table
CREATE TABLE IF NOT EXISTS billing_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id VARCHAR(50) NULL,
    guest_name VARCHAR(100) NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    transaction_type ENUM('Room Charge', 'Service', 'Payment', 'Refund') DEFAULT 'Room Charge',
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 4: Create room_status_logs table
CREATE TABLE IF NOT EXISTS room_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(100) NULL,
    change_reason TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Step 5: Insert sample room data
INSERT IGNORE INTO rooms (room_number, room_type, floor_number, status, max_guests, rate) VALUES
('101', 'Single', 1, 'Occupied', 1, 1500.00),
('102', 'Double', 1, 'Vacant', 2, 2000.00),
('103', 'Deluxe', 1, 'Cleaning', 3, 2500.00),
('104', 'Single', 1, 'Vacant', 1, 1500.00),
('105', 'Suite', 1, 'Maintenance', 4, 3500.00),
('201', 'Suite', 2, 'Vacant', 4, 3500.00),
('202', 'Double', 2, 'Occupied', 2, 2000.00),
('203', 'Single', 2, 'Vacant', 1, 1500.00),
('204', 'Deluxe', 2, 'Cleaning', 3, 2500.00),
('205', 'Double', 2, 'Occupied', 2, 2000.00),
('301', 'Single', 3, 'Cleaning', 1, 1500.00),
('302', 'Deluxe', 3, 'Occupied', 3, 2500.00),
('303', 'Suite', 3, 'Vacant', 4, 3500.00),
('304', 'Single', 3, 'Maintenance', 1, 1500.00),
('305', 'Double', 3, 'Vacant', 2, 2000.00);

-- Step 6: Insert sample billing transactions
INSERT IGNORE INTO billing_transactions (reservation_id, guest_name, room_number, transaction_type, amount, payment_method, status, transaction_date) VALUES
('RES-001', 'Sarah Johnson', '204', 'Room Charge', 555.00, 'Card', 'Paid', NOW()),
('RES-002', 'Michael Chen', '315', 'Room Charge', 840.00, 'Cash', 'Pending', NOW()),
('RES-003', 'Emma Williams', '102', 'Room Charge', 495.00, 'GCash', 'Paid', NOW()),
('RES-004', 'David Brown', '410', 'Room Charge', 900.00, 'Bank Transfer', 'Paid', NOW()),
('RES-005', 'Lisa Anderson', '208', 'Room Charge', 975.00, 'Card', 'Pending', NOW());

-- Step 7: Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_rooms_floor ON rooms(floor_number);
CREATE INDEX IF NOT EXISTS idx_rooms_type ON rooms(room_type);
CREATE INDEX IF NOT EXISTS idx_reservations_contact ON reservations(contact_number);
CREATE INDEX IF NOT EXISTS idx_billing_guest ON billing_transactions(guest_name);
CREATE INDEX IF NOT EXISTS idx_billing_room ON billing_transactions(room_number);

-- Step 8: Show summary
SELECT 'Integration Complete!' as status;
SELECT COUNT(*) as total_rooms FROM rooms;
SELECT COUNT(*) as total_reservations FROM reservations;
SELECT COUNT(*) as total_billing_transactions FROM billing_transactions;
