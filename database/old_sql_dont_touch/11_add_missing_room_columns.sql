-- ================================================================
-- ADD MISSING COLUMNS TO ROOMS TABLE
-- ================================================================
-- This script adds guest_name and housekeeping_status columns
-- that are needed for the Rooms/Housekeeping integration
-- ================================================================

USE inn_nexus;

-- Add guest_name column if it doesn't exist
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS guest_name VARCHAR(200) NULL AFTER maintenance_notes;

-- Add housekeeping_status column if it doesn't exist
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS housekeeping_status ENUM('clean', 'dirty', 'cleaning', 'inspected') DEFAULT 'clean' AFTER last_cleaned;

-- Add index for housekeeping_status
ALTER TABLE rooms 
ADD INDEX IF NOT EXISTS idx_housekeeping_status (housekeeping_status);

SELECT 'Missing room columns added successfully!' AS Status;

