-- ================================================================
-- INN NEXUS - ROOMS TABLE
-- ================================================================
-- Creates the rooms table for room inventory management
-- ================================================================

USE inn_nexus;

-- ROOMS TABLE (Room Inventory Management)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single',
    floor_number INT DEFAULT 1,
    status ENUM('Vacant', 'Occupied', 'Cleaning', 'Maintenance', 'Reserved') DEFAULT 'Vacant',
    max_guests INT DEFAULT 2,
    rate DECIMAL(10,2) DEFAULT 1500.00,
    amenities TEXT NULL,
    last_cleaned TIMESTAMP NULL,
    maintenance_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_room_number (room_number),
    INDEX idx_status (status),
    INDEX idx_floor_number (floor_number),
    INDEX idx_room_type (room_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Rooms table created successfully!' AS Status;

-- ================================================================
-- ROOM DATA FIXES (Applied after table creation)
-- ================================================================

-- For any remaining NULL values, set sequential room numbers
SET @row_number = 100;

UPDATE rooms
SET room_number = (@row_number := @row_number + 1)
WHERE room_number IS NULL OR room_number = '';

-- Ensure all rooms have proper room types
UPDATE rooms
SET room_type = CASE
    WHEN room_type IS NULL OR room_type = '' THEN 'Single'
    ELSE room_type
END;

-- Ensure all rooms have floor numbers
UPDATE rooms
SET floor_number = CASE
    WHEN floor_number IS NULL OR floor_number = 0 THEN
        CASE
            WHEN CAST(room_number AS UNSIGNED) < 200 THEN 1
            WHEN CAST(room_number AS UNSIGNED) < 300 THEN 2
            ELSE 3
        END
    ELSE floor_number
END;

-- Set default rates if missing
UPDATE rooms
SET rate = CASE
    WHEN rate IS NULL OR rate = 0 THEN
        CASE room_type
            WHEN 'Single' THEN 1500.00
            WHEN 'Double' THEN 2000.00
            WHEN 'Deluxe' THEN 2500.00
            WHEN 'Suite' THEN 3500.00
            ELSE 1500.00
        END
    ELSE rate
END;

-- Set default max_guests if missing
UPDATE rooms
SET max_guests = CASE
    WHEN max_guests IS NULL OR max_guests = 0 THEN
        CASE room_type
            WHEN 'Single' THEN 1
            WHEN 'Double' THEN 2
            WHEN 'Deluxe' THEN 2
            WHEN 'Suite' THEN 4
            ELSE 2
        END
    ELSE max_guests
END;

-- Standardize status values (capitalize first letter)
UPDATE rooms
SET status = CASE status
    WHEN 'occupied' THEN 'Occupied'
    WHEN 'vacant' THEN 'Vacant'
    WHEN 'cleaning' THEN 'Cleaning'
    WHEN 'maintenance' THEN 'Maintenance'
    WHEN 'reserved' THEN 'Reserved'
    WHEN 'dirty' THEN 'Cleaning'
    ELSE status
END;

SELECT 'Rooms table created and data fixed successfully!' AS Status;

-- ================================================================
-- ADD HOUSEKEEPING COLUMNS (For Rooms/Housekeeping integration)
-- ================================================================

-- Add guest_name column if it doesn't exist
ALTER TABLE rooms
ADD COLUMN IF NOT EXISTS guest_name VARCHAR(200) NULL AFTER maintenance_notes;

-- Add housekeeping_status column if it doesn't exist
ALTER TABLE rooms
ADD COLUMN IF NOT EXISTS housekeeping_status ENUM('clean', 'dirty', 'cleaning', 'inspected') DEFAULT 'clean' AFTER last_cleaned;

-- Add index for housekeeping_status
ALTER TABLE rooms
ADD INDEX IF NOT EXISTS idx_housekeeping_status (housekeeping_status);

SELECT 'Rooms table created, data fixed, and housekeeping columns added successfully!' AS Status;
