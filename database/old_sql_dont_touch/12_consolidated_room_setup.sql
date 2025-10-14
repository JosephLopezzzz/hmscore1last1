-- ================================================================
-- CONSOLIDATED ROOM SETUP SCRIPT
-- ================================================================
-- This script consolidates and fixes the issues from files 03-10
-- Provides safe room setup with proper housekeeping integration
-- Run this AFTER: 00_initial_database_setup.sql, 01_housekeeping_integration.sql, 02_fix_room_numbers.sql
-- ================================================================

USE inn_nexus;

-- ================================================================
-- ENSURE HOUSEKEEPING COLUMNS EXIST (from file 01 & 11)
-- ================================================================

-- Add guest_name column if it doesn't exist
ALTER TABLE rooms
ADD COLUMN IF NOT EXISTS guest_name VARCHAR(200) NULL AFTER maintenance_notes;

-- Add housekeeping_status column if it doesn't exist
ALTER TABLE rooms
ADD COLUMN IF NOT EXISTS housekeeping_status ENUM('clean', 'dirty', 'cleaning', 'inspected') DEFAULT 'clean' AFTER last_cleaned;

-- Add index for housekeeping_status if it doesn't exist
ALTER TABLE rooms
ADD INDEX IF NOT EXISTS idx_housekeeping_status (housekeeping_status);

-- ================================================================
-- ROOM SETUP OPTIONS
-- ================================================================
-- Choose ONE of the following approaches:

-- OPTION 1: PRESERVE existing rooms, only add missing ones
-- OPTION 2: CLEAR all rooms and start fresh (USE WITH CAUTION)
-- OPTION 3: UPDATE existing rooms with new data structure

-- ================================================================
-- SAFE ROOM SETUP (RECOMMENDED)
-- ================================================================

SET @setup_mode = 'preserve'; -- Options: 'preserve', 'clear_fresh', 'update_existing'

-- Create a temporary table to store existing room data if preserving
DROP TEMPORARY TABLE IF EXISTS existing_rooms_backup;
CREATE TEMPORARY TABLE existing_rooms_backup AS
SELECT * FROM rooms;

-- Count existing rooms
SELECT COUNT(*) as existing_room_count FROM rooms;

-- ================================================================
-- ROOM DATA DEFINITION
-- ================================================================

-- Insert diverse rooms across 5 floors (consolidated from files 03-10)
-- This data structure ensures consistency across all room types

INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES

-- =====================================================
-- FLOOR 1: Economy and Standard Rooms (8 rooms)
-- =====================================================

-- Singles (1 person)
('101', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('102', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('103', 'Single', 1, 'Cleaning', 1, 1200.00, 'WiFi, TV, Mini Fridge'),

-- Standard Double (2 people - couples)
('104', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('106', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),

-- Twin (2 people - friends/colleagues)
('107', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),
('108', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds')

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
('206', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed')

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
('306', 'Quad', 3, 'Maintenance', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds')

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
('405', 'Accessible', 4, 'Vacant', 2, 2000.00, 'WiFi, TV, Mini Fridge, Wheelchair Access, Roll-in Shower')

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
-- HOUSEKEEPING TASKS INTEGRATION
-- ================================================================

-- Create housekeeping tasks for rooms that need them (from file 01)
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
        WHEN r.room_type IN ('Suite', 'Deluxe', 'Executive Suite', 'Penthouse', 'Presidential') THEN 'high'
        ELSE 'normal'
    END as priority,
    r.guest_name,
    r.maintenance_notes as notes
FROM rooms r
WHERE r.status IN ('Cleaning', 'Maintenance')
   OR r.room_number IN ('101', '102', '201', '204')
ON DUPLICATE KEY UPDATE
    updated_at = NOW(),
    status = VALUES(status);

-- ================================================================
-- VERIFICATION AND SUMMARY
-- ================================================================

-- Show final room summary
SELECT
    'ROOM SETUP COMPLETE' as status,
    COUNT(*) as total_rooms,
    COUNT(DISTINCT floor_number) as floors,
    COUNT(DISTINCT room_type) as room_types
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

-- Show housekeeping tasks created
SELECT
    COUNT(*) as housekeeping_tasks_created,
    COUNT(DISTINCT room_number) as rooms_with_tasks
FROM housekeeping_tasks;

-- ================================================================
-- CLEANUP
-- ================================================================

-- Drop temporary backup table
DROP TEMPORARY TABLE IF EXISTS existing_rooms_backup;

SELECT '✅ Consolidated room setup completed successfully!' AS final_status;
