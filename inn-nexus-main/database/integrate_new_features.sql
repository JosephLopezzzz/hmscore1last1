-- Database integration for new features
-- This script enhances the existing database to support the new billing and rooms functionality

USE inn_nexus;

-- Add new columns to reservations table for enhanced functionality
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS special_requests TEXT NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add new columns to rooms table for room management
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single',
ADD COLUMN IF NOT EXISTS floor_number INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS max_guests INT DEFAULT 2,
ADD COLUMN IF NOT EXISTS amenities TEXT NULL,
ADD COLUMN IF NOT EXISTS last_cleaned TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS maintenance_notes TEXT NULL;

-- Create billing_transactions table for detailed billing tracking
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_guest_name (guest_name),
    INDEX idx_room_number (room_number),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status)
);

-- Create room_status_logs table for tracking room status changes
CREATE TABLE IF NOT EXISTS room_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(100) NULL,
    change_reason TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_number (room_number),
    INDEX idx_changed_at (changed_at)
);

-- Insert sample room data if rooms table is empty
INSERT IGNORE INTO rooms (room_number, room_type, floor_number, status, max_guests, rate) VALUES
-- Floor 1
('101', 'Single', 1, 'Occupied', 1, 1500.00),
('102', 'Double', 1, 'Vacant', 2, 2000.00),
('103', 'Deluxe', 1, 'Cleaning', 3, 2500.00),
('104', 'Single', 1, 'Vacant', 1, 1500.00),
('105', 'Suite', 1, 'Maintenance', 4, 3500.00),

-- Floor 2
('201', 'Suite', 2, 'Vacant', 4, 3500.00),
('202', 'Double', 2, 'Occupied', 2, 2000.00),
('203', 'Single', 2, 'Vacant', 1, 1500.00),
('204', 'Deluxe', 2, 'Cleaning', 3, 2500.00),
('205', 'Double', 2, 'Occupied', 2, 2000.00),

-- Floor 3
('301', 'Single', 3, 'Cleaning', 1, 1500.00),
('302', 'Deluxe', 3, 'Occupied', 3, 2500.00),
('303', 'Suite', 3, 'Vacant', 4, 3500.00),
('304', 'Single', 3, 'Maintenance', 1, 1500.00),
('305', 'Double', 3, 'Vacant', 2, 2000.00);

-- Insert sample billing transactions
INSERT INTO billing_transactions (reservation_id, guest_name, room_number, transaction_type, amount, payment_method, status, transaction_date) VALUES
('RES-001', 'Sarah Johnson', '204', 'Room Charge', 555.00, 'Card', 'Paid', NOW()),
('RES-002', 'Michael Chen', '315', 'Room Charge', 840.00, 'Cash', 'Pending', NOW()),
('RES-003', 'Emma Williams', '102', 'Room Charge', 495.00, 'GCash', 'Paid', NOW()),
('RES-004', 'David Brown', '410', 'Room Charge', 900.00, 'Bank Transfer', 'Paid', NOW()),
('RES-005', 'Lisa Anderson', '208', 'Room Charge', 975.00, 'Card', 'Pending', NOW()),
('TXN-101', 'Sarah Johnson', '204', 'Service', 185.00, 'Card', 'Paid', NOW()),
('TXN-102', 'Emma Williams', '102', 'Service', 45.00, 'Cash', 'Paid', NOW()),
('TXN-103', 'David Brown', '410', 'Service', 120.00, 'Card', 'Paid', NOW()),
('TXN-104', 'Michael Chen', '315', 'Service', 250.00, 'Card', 'Paid', NOW());

-- Update existing reservations with contact numbers and special requests
UPDATE reservations SET 
    contact_number = '+63 912 345 6789',
    special_requests = 'Late check-in requested'
WHERE guest_name = 'John Doe' AND contact_number IS NULL;

UPDATE reservations SET 
    contact_number = '+63 917 123 4567',
    special_requests = 'High floor preferred'
WHERE guest_name = 'Mary Smith' AND contact_number IS NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_reservations_contact ON reservations(contact_number);
CREATE INDEX IF NOT EXISTS idx_rooms_floor ON rooms(floor_number);
CREATE INDEX IF NOT EXISTS idx_rooms_type ON rooms(room_type);

-- Show summary of integration
SELECT 
    'Integration Complete' as status,
    (SELECT COUNT(*) FROM rooms) as total_rooms,
    (SELECT COUNT(*) FROM reservations) as total_reservations,
    (SELECT COUNT(*) FROM billing_transactions) as total_transactions,
    (SELECT COUNT(*) FROM room_status_logs) as total_status_logs;
