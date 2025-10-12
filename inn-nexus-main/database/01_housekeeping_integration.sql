-- ================================================================
-- HOUSEKEEPING INTEGRATION SCRIPT
-- ================================================================
-- Creates housekeeping_tasks table and updates rooms table
-- for real-time synchronization between Rooms and Housekeeping
-- ================================================================

USE inn_nexus;

-- Create housekeeping_tasks table
CREATE TABLE IF NOT EXISTS housekeeping_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    task_type ENUM('cleaning', 'deep_clean', 'maintenance', 'inspection') DEFAULT 'cleaning',
    status ENUM('pending', 'in-progress', 'completed', 'maintenance') DEFAULT 'pending',
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_to VARCHAR(100) NULL,
    guest_name VARCHAR(200) NULL,
    notes TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_room_id (room_id),
    INDEX idx_room_number (room_number),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update rooms table to ensure all necessary fields exist
ALTER TABLE rooms 
ADD COLUMN IF NOT EXISTS guest_name VARCHAR(200) NULL AFTER status,
ADD COLUMN IF NOT EXISTS housekeeping_status ENUM('clean', 'dirty', 'cleaning', 'inspected') DEFAULT 'clean' AFTER guest_name;

-- Create index for housekeeping status
CREATE INDEX IF NOT EXISTS idx_housekeeping_status ON rooms(housekeeping_status);

-- Insert sample housekeeping tasks based on existing rooms
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
        WHEN r.room_type = 'Suite' OR r.room_type = 'Deluxe' THEN 'high'
        ELSE 'normal'
    END as priority,
    r.guest_name,
    r.maintenance_notes as notes
FROM rooms r
WHERE r.status IN ('Cleaning', 'Maintenance') OR r.room_number IN ('101', '102', '201', '204')
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Create trigger to auto-create housekeeping task when room status changes
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS after_room_status_update
AFTER UPDATE ON rooms
FOR EACH ROW
BEGIN
    -- When room status changes to Cleaning, create a housekeeping task
    IF NEW.status = 'Cleaning' AND OLD.status != 'Cleaning' THEN
        INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
        VALUES (
            NEW.id,
            NEW.room_number,
            'cleaning',
            'pending',
            CASE 
                WHEN NEW.room_type = 'Suite' OR NEW.room_type = 'Deluxe' THEN 'high'
                ELSE 'normal'
            END,
            NEW.guest_name,
            'Automated task created from room status change'
        );
    END IF;
    
    -- When room status changes to Maintenance, create a maintenance task
    IF NEW.status = 'Maintenance' AND OLD.status != 'Maintenance' THEN
        INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
        VALUES (
            NEW.id,
            NEW.room_number,
            'maintenance',
            'maintenance',
            'urgent',
            NEW.guest_name,
            NEW.maintenance_notes
        );
    END IF;
END$$

DELIMITER ;

-- Verify tables and data
SELECT 'Housekeeping tasks table created successfully!' AS Status;
SELECT COUNT(*) AS 'Total Housekeeping Tasks' FROM housekeeping_tasks;
SELECT COUNT(*) AS 'Rooms with Housekeeping Status' FROM rooms WHERE housekeeping_status IS NOT NULL;

