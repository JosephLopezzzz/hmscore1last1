-- =====================================================
-- STEP 10: Final Insert - Clear Both Tables and Insert Rooms
-- =====================================================
-- This clears housekeeping_tasks first, then rooms, then inserts
-- Run this in phpMyAdmin SQL tab

-- Step 1: Clear housekeeping tasks (child table)
DELETE FROM housekeeping_tasks;

-- Step 2: Clear rooms (parent table)
DELETE FROM rooms;

-- Step 3: Reset auto-increment
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE housekeeping_tasks AUTO_INCREMENT = 1;

-- =====================================================
-- FLOOR 1: Economy and Standard Rooms (8 rooms)
-- =====================================================

-- Singles (1 person)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('101', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('102', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('103', 'Single', 1, 'Cleaning', 1, 1200.00, 'WiFi, TV, Mini Fridge');

-- Standard Double (2 people - couples)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('104', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('106', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker');

-- Twin (2 people - friends/colleagues)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('107', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),
('108', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds');

-- =====================================================
-- FLOOR 2: Deluxe and Family Rooms (6 rooms)
-- =====================================================

-- Deluxe Double (2 people - couples, premium)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('201', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('202', 'Deluxe', 2, 'Occupied', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('203', 'Deluxe', 2, 'Cleaning', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker');

-- Family Room (4 people - families with kids)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('204', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('205', 'Family', 2, 'Occupied', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('206', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed');

-- =====================================================
-- FLOOR 3: Suites and Large Groups (6 rooms)
-- =====================================================

-- Suite (2 people - luxury couples)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('301', 'Suite', 3, 'Vacant', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room'),
('302', 'Suite', 3, 'Occupied', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room');

-- Triple (3 people - small groups)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('303', 'Triple', 3, 'Vacant', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds'),
('304', 'Triple', 3, 'Cleaning', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds');

-- Quad (4 people - groups/families)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('305', 'Quad', 3, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),
('306', 'Quad', 3, 'Maintenance', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds');

-- =====================================================
-- FLOOR 4: Premium Family and Accessible Rooms (5 rooms)
-- =====================================================

-- Executive Suite (3 people)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('401', 'Executive Suite', 4, 'Vacant', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room'),
('402', 'Executive Suite', 4, 'Occupied', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room');

-- Family Suite (5 people - large families)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('403', 'Family Suite', 4, 'Vacant', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room'),
('404', 'Family Suite', 4, 'Cleaning', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room');

-- Accessible Room (2 people - wheelchair accessible)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('405', 'Accessible', 4, 'Vacant', 2, 2000.00, 'WiFi, TV, Mini Fridge, Wheelchair Access, Roll-in Shower');

-- =====================================================
-- FLOOR 5: Penthouse and VIP (5 rooms)
-- =====================================================

-- Penthouse (4 people - luxury)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('501', 'Penthouse', 5, 'Vacant', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room'),
('502', 'Penthouse', 5, 'Occupied', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room');

-- VIP Suite (6 people - large groups/events)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('503', 'VIP Suite', 5, 'Vacant', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area'),
('504', 'VIP Suite', 5, 'Maintenance', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area');

-- Presidential Suite (8 people - ultimate luxury)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('505', 'Presidential', 5, 'Vacant', 8, 10000.00, 'WiFi, Smart TV, Full Kitchen, 2 Jacuzzis, Private Terrace, 4 Bedrooms, Cinema Room, Butler Service');

-- =====================================================
-- Verify the results
-- =====================================================
SELECT 
    floor_number,
    COUNT(*) as rooms_per_floor,
    GROUP_CONCAT(DISTINCT room_type ORDER BY room_type SEPARATOR ', ') as room_types
FROM rooms
GROUP BY floor_number
ORDER BY floor_number;

SELECT '✅ Successfully inserted 30 diverse rooms across 5 floors!' AS status;

