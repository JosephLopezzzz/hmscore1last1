-- ================================================================
-- ADD MORE FLOORS AND DIVERSE ROOM TYPES
-- ================================================================
-- This script adds more rooms across multiple floors with various
-- room types suitable for singles, couples, families, and groups
-- ================================================================

USE inn_nexus;

-- Clear existing rooms (optional - remove this if you want to keep current rooms)
-- DELETE FROM rooms;
-- ALTER TABLE rooms AUTO_INCREMENT = 1;

-- Insert diverse rooms across 5 floors
-- Floor 1: Economy and Standard Rooms
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
-- Singles (1 person)
('101', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('102', 'Single', 1, 'Occupied', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('103', 'Single', 1, 'Cleaning', 1, 1200.00, 'WiFi, TV, Mini Fridge'),

-- Standard Double (2 people - couples)
('104', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('106', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),

-- Twin (2 people - friends/colleagues)
('107', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),
('108', 'Twin', 1, 'Maintenance', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),

-- Triple (3 people - small families)
('109', 'Triple', 1, 'Vacant', 3, 2200.00, 'WiFi, TV, Mini Fridge, Extra Bed'),
('110', 'Triple', 1, 'Occupied', 3, 2200.00, 'WiFi, TV, Mini Fridge, Extra Bed')

ON DUPLICATE KEY UPDATE 
    room_type = VALUES(room_type),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities);

-- Floor 2: Deluxe and Family Rooms
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
-- Deluxe Double (2 people - couples, premium)
('201', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('202', 'Deluxe', 2, 'Occupied', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('203', 'Deluxe', 2, 'Cleaning', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),

-- Family Room (4 people - families with kids)
('204', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('205', 'Family', 2, 'Occupied', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('206', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),

-- Quad Room (4 people - groups/families)
('207', 'Quad', 2, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),
('208', 'Quad', 2, 'Maintenance', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),

-- Studio (2-3 people - couples with baby)
('209', 'Studio', 2, 'Vacant', 3, 2400.00, 'WiFi, Smart TV, Kitchenette, Living Area'),
('210', 'Studio', 2, 'Occupied', 3, 2400.00, 'WiFi, Smart TV, Kitchenette, Living Area')

ON DUPLICATE KEY UPDATE 
    room_type = VALUES(room_type),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities);

-- Floor 3: Premium and Suite Rooms
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
-- Junior Suite (2-3 people - couples, premium)
('301', 'Junior Suite', 3, 'Vacant', 3, 3500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Jacuzzi, Living Area'),
('302', 'Junior Suite', 3, 'Occupied', 3, 3500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Jacuzzi, Living Area'),

-- Executive Suite (2-4 people - business/families)
('303', 'Executive Suite', 3, 'Vacant', 4, 4200.00, 'WiFi, Smart TV, Full Bar, Balcony, Jacuzzi, Separate Living Room, Work Desk'),
('304', 'Executive Suite', 3, 'Cleaning', 4, 4200.00, 'WiFi, Smart TV, Full Bar, Balcony, Jacuzzi, Separate Living Room, Work Desk'),

-- Family Suite (5-6 people - large families)
('305', 'Family Suite', 3, 'Vacant', 6, 5000.00, 'WiFi, Smart TV, Full Bar, 2 Bedrooms, 2 Bathrooms, Living Room, Kitchenette'),
('306', 'Family Suite', 3, 'Occupied', 6, 5000.00, 'WiFi, Smart TV, Full Bar, 2 Bedrooms, 2 Bathrooms, Living Room, Kitchenette'),

-- Connecting Rooms (4-5 people - families)
('307', 'Connecting', 3, 'Vacant', 5, 3800.00, 'WiFi, Smart TV, 2 Rooms Connected, Shared Balcony'),
('308', 'Connecting', 3, 'Vacant', 5, 3800.00, 'WiFi, Smart TV, 2 Rooms Connected, Shared Balcony')

ON DUPLICATE KEY UPDATE 
    room_type = VALUES(room_type),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities);

-- Floor 4: Luxury and Presidential Suites
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
-- Luxury Suite (2-4 people - VIP couples/small families)
('401', 'Luxury Suite', 4, 'Vacant', 4, 6000.00, 'WiFi, 65" Smart TV, Premium Bar, Panoramic Balcony, Jacuzzi, Steam Shower, Living Room, Dining Area'),
('402', 'Luxury Suite', 4, 'Occupied', 4, 6000.00, 'WiFi, 65" Smart TV, Premium Bar, Panoramic Balcony, Jacuzzi, Steam Shower, Living Room, Dining Area'),

-- Presidential Suite (4-6 people - VIP families/groups)
('403', 'Presidential Suite', 4, 'Vacant', 6, 8500.00, 'WiFi, Multiple Smart TVs, Premium Bar, Wrap-around Balcony, Jacuzzi, Steam Shower, 2 Bedrooms, Full Kitchen, Dining Room, Office'),
('404', 'Presidential Suite', 4, 'Cleaning', 6, 8500.00, 'WiFi, Multiple Smart TVs, Premium Bar, Wrap-around Balcony, Jacuzzi, Steam Shower, 2 Bedrooms, Full Kitchen, Dining Room, Office'),

-- Penthouse (6-8 people - VIP large groups/families)
('405', 'Penthouse', 4, 'Vacant', 8, 12000.00, 'WiFi, Multiple Smart TVs, Full Bar, Private Terrace, Jacuzzi, 3 Bedrooms, 3 Bathrooms, Full Kitchen, Living Room, Dining Room, Butler Service'),

-- Honeymoon Suite (2 people - newlyweds)
('406', 'Honeymoon Suite', 4, 'Occupied', 2, 7000.00, 'WiFi, Smart TV, Champagne Bar, Heart-shaped Jacuzzi, Rose Petals, Romantic Lighting, Balcony with View')

ON DUPLICATE KEY UPDATE 
    room_type = VALUES(room_type),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities);

-- Floor 5: Accessible and Special Rooms
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
-- Accessible Single (1 person - wheelchair accessible)
('501', 'Accessible Single', 5, 'Vacant', 1, 1200.00, 'WiFi, TV, Wheelchair Accessible, Grab Bars, Roll-in Shower'),
('502', 'Accessible Single', 5, 'Occupied', 1, 1200.00, 'WiFi, TV, Wheelchair Accessible, Grab Bars, Roll-in Shower'),

-- Accessible Double (2 people - wheelchair accessible)
('503', 'Accessible Double', 5, 'Vacant', 2, 1800.00, 'WiFi, TV, Wheelchair Accessible, Grab Bars, Roll-in Shower, Wider Doorways'),
('504', 'Accessible Double', 5, 'Cleaning', 2, 1800.00, 'WiFi, TV, Wheelchair Accessible, Grab Bars, Roll-in Shower, Wider Doorways'),

-- Pet-Friendly Room (2-3 people with pets)
('505', 'Pet-Friendly', 5, 'Vacant', 3, 2000.00, 'WiFi, TV, Pet Bed, Food Bowls, Easy-Clean Floors, Patio Access'),
('506', 'Pet-Friendly', 5, 'Occupied', 3, 2000.00, 'WiFi, TV, Pet Bed, Food Bowls, Easy-Clean Floors, Patio Access'),

-- Extended Stay (1-2 people - long-term guests)
('507', 'Extended Stay', 5, 'Vacant', 2, 2200.00, 'WiFi, Smart TV, Full Kitchen, Washer/Dryer, Work Desk, Living Area'),
('508', 'Extended Stay', 5, 'Occupied', 2, 2200.00, 'WiFi, Smart TV, Full Kitchen, Washer/Dryer, Work Desk, Living Area')

ON DUPLICATE KEY UPDATE 
    room_type = VALUES(room_type),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities);

-- Update room type enum to include new types
ALTER TABLE rooms MODIFY COLUMN room_type VARCHAR(50);

-- Verify all rooms
SELECT 
    floor_number,
    room_number,
    room_type,
    max_guests,
    rate,
    status
FROM rooms 
ORDER BY floor_number, CAST(room_number AS UNSIGNED);

-- Show summary by floor
SELECT 
    floor_number,
    COUNT(*) as total_rooms,
    MIN(rate) as min_rate,
    MAX(rate) as max_rate,
    SUM(max_guests) as total_capacity,
    GROUP_CONCAT(DISTINCT room_type ORDER BY room_type SEPARATOR ', ') as room_types
FROM rooms
GROUP BY floor_number
ORDER BY floor_number;

-- Show summary by room type
SELECT 
    room_type,
    COUNT(*) as total_rooms,
    AVG(max_guests) as avg_capacity,
    MIN(rate) as min_rate,
    MAX(rate) as max_rate,
    SUM(CASE WHEN status = 'Vacant' THEN 1 ELSE 0 END) as available
FROM rooms
GROUP BY room_type
ORDER BY max_guests, rate;

SELECT 'Successfully added diverse rooms across 5 floors!' AS Status;

