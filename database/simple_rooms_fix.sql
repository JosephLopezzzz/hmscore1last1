-- Simple fix for rooms table - add missing columns one by one
USE inn_nexus;

-- First, let's see what columns currently exist in the rooms table
DESCRIBE rooms;

-- Add missing columns one by one (these will be skipped if they already exist)
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_number VARCHAR(10) NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') DEFAULT 'Single';
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS floor_number INT DEFAULT 1;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS max_guests INT DEFAULT 2;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS rate DECIMAL(10,2) DEFAULT 1500.00;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS amenities TEXT NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS last_cleaned TIMESTAMP NULL;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS maintenance_notes TEXT NULL;

-- Now check the structure again
DESCRIBE rooms;
