-- Fix the rooms table structure to match our integration script
USE inn_nexus;

-- First, let's see what columns exist in the rooms table
DESCRIBE rooms;

-- Add missing columns to rooms table
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS room_number VARCHAR(10) NULL,
ADD COLUMN IF NOT EXISTS room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single',
ADD COLUMN IF NOT EXISTS floor_number INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS max_guests INT DEFAULT 2,
ADD COLUMN IF NOT EXISTS amenities TEXT NULL,
ADD COLUMN IF NOT EXISTS last_cleaned TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS maintenance_notes TEXT NULL;

-- If the rooms table is empty or has different column names, let's check
SELECT * FROM rooms LIMIT 5;
