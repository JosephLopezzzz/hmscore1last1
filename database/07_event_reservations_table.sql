-- ================================================================
-- EVENT RESERVATIONS TABLE
-- ================================================================
-- This script creates the event_reservations table to link events with reservations
-- Run this after the events table (07_events_table.sql)
-- ================================================================

USE inn_nexus;

-- ================================================================
-- EVENT RESERVATIONS TABLE
-- ================================================================

CREATE TABLE IF NOT EXISTS event_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    reservation_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_event_id (event_id),
    INDEX idx_reservation_id (reservation_id),
    UNIQUE KEY unique_event_reservation (event_id, reservation_id),

    -- Foreign keys
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SUMMARY
-- ================================================================
-- Table created: event_reservations
-- Purpose: Links events with their corresponding reservations
-- ================================================================

SELECT 'Event reservations table created successfully!' AS Status;
