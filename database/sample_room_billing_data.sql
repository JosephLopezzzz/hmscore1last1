-- ================================================================
-- SAMPLE ROOM AND BILLING DATA
-- ================================================================
-- This script inserts comprehensive sample data for rooms, billing, and housekeeping
-- Run this after creating all necessary tables
-- ================================================================

USE inn_nexus;

-- ================================================================
-- SAMPLE ROOM DATA (30 Detailed Rooms)
-- ================================================================

-- =====================================================
-- FLOOR 1: Economy and Standard Rooms (8 rooms)
-- =====================================================

-- Singles (1 person)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('101', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('102', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('103', 'Single', 1, 'Cleaning', 1, 1200.00, 'WiFi, TV, Mini Fridge'),

-- Standard Double (2 people - couples)
('104', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('106', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),

-- Twin (2 people - friends/colleagues)
('107', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),
('108', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),

-- =====================================================
-- FLOOR 2: Deluxe and Family Rooms (6 rooms)
-- =====================================================

-- Deluxe Double (2 people - couples, premium)
('201', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('202', 'Deluxe', 2, 'Occupied', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('203', 'Deluxe', 2, 'Cleaning', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),

-- Family Room (4 people - families with kids)
('204', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('205', 'Family', 2, 'Occupied', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('206', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),

-- =====================================================
-- FLOOR 3: Suites and Large Groups (6 rooms)
-- =====================================================

-- Suite (2 people - luxury couples)
('301', 'Suite', 3, 'Vacant', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room'),
('302', 'Suite', 3, 'Occupied', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room'),

-- Triple (3 people - small groups)
('303', 'Triple', 3, 'Vacant', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds'),
('304', 'Triple', 3, 'Cleaning', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds'),

-- Quad (4 people - groups/families)
('305', 'Quad', 3, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),
('306', 'Quad', 3, 'Maintenance', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),

-- =====================================================
-- FLOOR 4: Premium Family and Accessible Rooms (5 rooms)
-- =====================================================

-- Executive Suite (3 people)
('401', 'Executive Suite', 4, 'Vacant', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room'),
('402', 'Executive Suite', 4, 'Occupied', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room'),

-- Family Suite (5 people - large families)
('403', 'Family Suite', 4, 'Vacant', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room'),
('404', 'Family Suite', 4, 'Cleaning', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room'),

-- Accessible Room (2 people - wheelchair accessible)
('405', 'Accessible', 4, 'Vacant', 2, 2000.00, 'WiFi, TV, Mini Fridge, Wheelchair Access, Roll-in Shower'),

-- =====================================================
-- FLOOR 5: Penthouse and VIP (5 rooms)
-- =====================================================

-- Penthouse (4 people - luxury)
('501', 'Penthouse', 5, 'Vacant', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room'),
('502', 'Penthouse', 5, 'Occupied', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room'),

-- VIP Suite (6 people - large groups/events)
('503', 'VIP Suite', 5, 'Vacant', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area'),
('504', 'VIP Suite', 5, 'Maintenance', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area'),

-- Presidential Suite (8 people - ultimate luxury)
('505', 'Presidential', 5, 'Vacant', 8, 10000.00, 'WiFi, Smart TV, Full Kitchen, 2 Jacuzzis, Private Terrace, 4 Bedrooms, Cinema Room, Butler Service')

ON DUPLICATE KEY UPDATE
    room_type = VALUES(room_type),
    floor_number = VALUES(floor_number),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities),
    status = VALUES(status);

-- ================================================================
-- SAMPLE BILLING TRANSACTIONS
-- ================================================================

INSERT INTO billing_transactions (reservation_id, guest_name, room_number, transaction_type, amount, payment_method, status, transaction_date) VALUES
(NULL, 'Sarah Johnson', '204', 'Room Charge', 555.00, 'Card', 'Paid', NOW()),
(NULL, 'Michael Chen', '315', 'Room Charge', 840.00, 'Cash', 'Pending', NOW()),
(NULL, 'Emma Williams', '102', 'Room Charge', 495.00, 'GCash', 'Paid', NOW()),
(NULL, 'David Brown', '410', 'Room Charge', 900.00, 'Bank Transfer', 'Paid', NOW()),
(NULL, 'Lisa Anderson', '208', 'Room Charge', 975.00, 'Card', 'Pending', NOW()),
(NULL, 'James Wilson', '301', 'Room Charge', 1220.00, 'Card', 'Paid', NOW()),
(NULL, 'Maria Garcia', '405', 'Room Charge', 680.00, 'Cash', 'Pending', NOW()),
(NULL, 'Robert Taylor', '503', 'Room Charge', 2100.00, 'Bank Transfer', 'Paid', NOW())

ON DUPLICATE KEY UPDATE
    guest_name = VALUES(guest_name),
    amount = VALUES(amount),
    payment_method = VALUES(payment_method),
    status = VALUES(status);

-- ================================================================
-- DYNAMIC HOUSEKEEPING TASKS GENERATION
-- ================================================================

-- Create housekeeping tasks for rooms that need them
INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
SELECT
    r.id,
    r.room_number,
    CASE
        WHEN r.status = 'Cleaning' THEN 'cleaning'
        WHEN r.status = 'Maintenance' THEN 'maintenance'
        ELSE 'cleaning'
    END as task_type,
    CASE
        WHEN r.status = 'Cleaning' THEN 'pending'
        WHEN r.status = 'Maintenance' THEN 'maintenance'
        WHEN r.status = 'Occupied' THEN 'completed'
        ELSE 'pending'
    END as status,
    CASE
        WHEN r.status = 'Maintenance' THEN 'urgent'
        WHEN r.room_type IN ('Suite', 'Deluxe', 'Executive Suite', 'Penthouse', 'Presidential', 'VIP Suite', 'Family') THEN 'high'
        ELSE 'normal'
    END as priority,
    r.guest_name,
    r.maintenance_notes as notes
FROM rooms r
WHERE r.status IN ('Cleaning', 'Maintenance')
   OR r.room_number IN ('101', '102', '201', '204', '304', '404', '504')
ON DUPLICATE KEY UPDATE
    updated_at = NOW(),
    status = VALUES(status),
    priority = VALUES(priority);

-- ================================================================
-- SUMMARY AND VERIFICATION
-- ================================================================

-- Show final room summary
SELECT
    'SAMPLE DATA INSERTION COMPLETE' as status,
    COUNT(*) as total_rooms_inserted,
    COUNT(DISTINCT floor_number) as floors_covered,
    COUNT(DISTINCT room_type) as room_types_available
FROM rooms;

-- Show rooms by floor
SELECT
    floor_number,
    COUNT(*) as rooms_per_floor,
    GROUP_CONCAT(DISTINCT room_type ORDER BY room_type SEPARATOR ', ') as room_types,
    MIN(rate) as min_rate,
    MAX(rate) as max_rate,
    SUM(max_guests) as total_capacity
FROM rooms
GROUP BY floor_number
ORDER BY floor_number;

-- Show billing transactions summary
SELECT
    COUNT(*) as total_billing_transactions,
    SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_transactions,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_transactions,
    SUM(amount) as total_amount
FROM billing_transactions;

-- Show housekeeping tasks summary
SELECT
    COUNT(*) as total_housekeeping_tasks,
    COUNT(DISTINCT room_number) as rooms_with_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
FROM housekeeping_tasks;

SELECT '✅ Sample room, billing, and housekeeping data loaded successfully!' AS final_status;
