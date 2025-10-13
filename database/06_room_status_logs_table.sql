-- ================================================================
-- INN NEXUS - ROOM STATUS LOGS TABLE
-- ================================================================
-- Creates the room_status_logs table for audit tracking
-- ================================================================

USE inn_nexus;

-- ROOM_STATUS_LOGS TABLE (Room Status Change Tracking)
CREATE TABLE IF NOT EXISTS room_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(100) NULL,
    change_reason TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_room_number (room_number),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Room status logs table created successfully!' AS Status;
