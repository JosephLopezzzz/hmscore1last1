-- ================================================================
-- FIX ROOM NUMBERS AND DATA
-- ================================================================
-- This script fixes NULL room numbers and ensures data consistency
-- ================================================================

-- ================================================================

USE inn_nexus;

-- First, try to copy from 'number' column if it exists
UPDATE rooms
SET room_number = number
WHERE (room_number IS NULL OR room_number = '')
  AND number IS NOT NULL;

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

-- Verify the fixes
SELECT 
    id,
    room_number,
    room_type,
    floor_number,
    status,
    max_guests,
    rate,
    guest_name
FROM rooms 
ORDER BY floor_number, CAST(room_number AS UNSIGNED);

-- Show summary
SELECT 
    floor_number,
    COUNT(*) as total_rooms,
    SUM(CASE WHEN status = 'Vacant' THEN 1 ELSE 0 END) as vacant,
    SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied,
    SUM(CASE WHEN status = 'Cleaning' THEN 1 ELSE 0 END) as cleaning,
    SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
FROM rooms
GROUP BY floor_number
ORDER BY floor_number;

SELECT 'Room data fixed successfully!' AS Status;

