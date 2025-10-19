-- ================================================================
-- FIX DATA INCONSISTENCIES
-- ================================================================
-- This script fixes data inconsistencies found in the rooms table
-- Run this after identifying issues with room data
-- ================================================================

USE inn_nexus;

-- ================================================================
-- FIX EMPTY ROOM TYPES
-- ================================================================

-- Update empty room types to 'Standard' for rooms without a type
UPDATE rooms 
SET room_type = 'Standard' 
WHERE room_type = '' OR room_type IS NULL;

-- ================================================================
-- FIX INVALID STATUSES
-- ================================================================

-- Update empty status to 'Vacant'
UPDATE rooms 
SET status = 'Vacant' 
WHERE status = '' OR status IS NULL;

-- ================================================================
-- FIX EVENT RESERVED STATUS INCONSISTENCIES
-- ================================================================

-- Update rooms with 'Event Reserved' in maintenance_notes but wrong status
UPDATE rooms 
SET status = 'Reserved', 
    guest_name = CONCAT('Event: ', COALESCE(guest_name, 'Event'))
WHERE maintenance_notes LIKE '%Event Reserved%' 
  AND status != 'Reserved' 
  AND status != 'Event Ongoing';

-- ================================================================
-- CLEAN UP MAINTENANCE NOTES
-- ================================================================

-- Clear maintenance notes that contain event info (should be in guest_name)
UPDATE rooms 
SET maintenance_notes = '' 
WHERE maintenance_notes LIKE '%Event Reserved%' 
  AND guest_name IS NOT NULL;

-- ================================================================
-- VERIFY FIXES
-- ================================================================

-- Check for any remaining inconsistencies
SELECT 
    id,
    room_number,
    room_type,
    status,
    guest_name,
    maintenance_notes,
    CASE 
        WHEN room_type = '' OR room_type IS NULL THEN 'Empty room type'
        WHEN status = '' OR status IS NULL THEN 'Empty status'
        WHEN maintenance_notes LIKE '%Event Reserved%' AND status != 'Reserved' THEN 'Event status mismatch'
        ELSE 'OK'
    END as issue
FROM rooms 
WHERE room_type = '' OR room_type IS NULL 
   OR status = '' OR status IS NULL
   OR (maintenance_notes LIKE '%Event Reserved%' AND status != 'Reserved');

-- ================================================================
-- SUMMARY
-- ================================================================

SELECT 'Data inconsistencies fixed successfully!' AS Status;
