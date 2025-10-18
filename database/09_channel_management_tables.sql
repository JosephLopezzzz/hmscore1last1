-- ================================================================
-- INN NEXUS - CHANNEL MANAGEMENT TABLES
-- ================================================================
-- Creates tables for hotel channel management system
-- ================================================================

USE inn_nexus;

-- CHANNELS TABLE (OTA and Distribution Channel Management)
CREATE TABLE IF NOT EXISTS channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(200) NOT NULL,
    type ENUM('OTA', 'GDS', 'Direct', 'Wholesale', 'Corporate') DEFAULT 'OTA',
    api_endpoint VARCHAR(500) NULL,
    api_key VARCHAR(500) NULL,
    username VARCHAR(200) NULL,
    password VARCHAR(500) NULL,
    status ENUM('Active', 'Inactive', 'Maintenance', 'Error') DEFAULT 'Active',
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'PHP',
    timezone VARCHAR(50) DEFAULT 'Asia/Manila',
    contact_person VARCHAR(200) NULL,
    contact_email VARCHAR(200) NULL,
    contact_phone VARCHAR(50) NULL,
    notes TEXT NULL,
    last_sync TIMESTAMP NULL,
    sync_status ENUM('Success', 'Failed', 'In Progress', 'Pending') DEFAULT 'Pending',
    sync_errors TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_last_sync (last_sync)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL RATES TABLE (Rate Management per Channel)
CREATE TABLE IF NOT EXISTS channel_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    rate_type ENUM('Base', 'Weekend', 'Holiday', 'Seasonal', 'Corporate') DEFAULT 'Base',
    rate DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'PHP',
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_room_type (room_type),
    INDEX idx_rate_type (rate_type),
    INDEX idx_valid_dates (valid_from, valid_to),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL ROOM MAPPINGS TABLE (Room Mapping per Channel)
CREATE TABLE IF NOT EXISTS channel_room_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    channel_room_id VARCHAR(100) NOT NULL,
    local_room_id INT NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (local_room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_channel_room_id (channel_room_id),
    INDEX idx_local_room_id (local_room_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL AVAILABILITY TABLE (Availability Management)
CREATE TABLE IF NOT EXISTS channel_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    room_id INT NOT NULL,
    date DATE NOT NULL,
    available_rooms INT DEFAULT 0,
    total_rooms INT DEFAULT 0,
    closed_to_arrival TINYINT(1) DEFAULT 0,
    closed_to_departure TINYINT(1) DEFAULT 0,
    minimum_stay INT DEFAULT 1,
    maximum_stay INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_room_id (room_id),
    INDEX idx_date (date),
    UNIQUE KEY unique_channel_room_date (channel_id, room_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL SYNC LOGS TABLE (Sync History and Logs)
CREATE TABLE IF NOT EXISTS channel_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    sync_type ENUM('Rates', 'Availability', 'Bookings', 'Full') NOT NULL,
    status ENUM('Success', 'Failed', 'Partial') NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    records_processed INT DEFAULT 0,
    records_successful INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    error_message TEXT NULL,
    sync_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT SAMPLE CHANNELS
INSERT INTO channels (name, display_name, type, status, commission_rate, contact_person, contact_email, notes) VALUES
('booking_com', 'Booking.com', 'OTA', 'Active', 15.00, 'John Smith', 'john@booking.com', 'Primary OTA partner'),
('expedia', 'Expedia', 'OTA', 'Active', 12.00, 'Sarah Johnson', 'sarah@expedia.com', 'Secondary OTA partner'),
('agoda', 'Agoda', 'OTA', 'Active', 10.00, 'Mike Chen', 'mike@agoda.com', 'Regional OTA partner'),
('direct_booking', 'Direct Booking', 'Direct', 'Active', 0.00, 'Hotel Staff', 'reservations@hotel.com', 'Direct bookings from hotel website'),
('corporate', 'Corporate Bookings', 'Corporate', 'Active', 5.00, 'Corporate Sales', 'corporate@hotel.com', 'Corporate rate agreements')
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    type = VALUES(type),
    status = VALUES(status);

SELECT 'Channel management tables created successfully!' AS Status;
