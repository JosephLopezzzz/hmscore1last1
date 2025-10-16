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
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') NOT NULL,
    rate_type ENUM('Standard', 'Corporate', 'Promotional', 'Last Minute', 'Weekend') DEFAULT 'Standard',
    base_rate DECIMAL(10,2) NOT NULL,
    extra_person_rate DECIMAL(10,2) DEFAULT 0.00,
    child_rate DECIMAL(10,2) DEFAULT 0.00,
    breakfast_included BOOLEAN DEFAULT FALSE,
    breakfast_rate DECIMAL(10,2) DEFAULT 0.00,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    minimum_stay INT DEFAULT 1,
    maximum_stay INT NULL,
    closed_to_arrival BOOLEAN DEFAULT FALSE,
    closed_to_departure BOOLEAN DEFAULT FALSE,
    status ENUM('Active', 'Inactive', 'Expired') DEFAULT 'Active',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_room_type (room_type),
    INDEX idx_rate_type (rate_type),
    INDEX idx_valid_dates (valid_from, valid_to),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL AVAILABILITY TABLE (Room Availability per Channel)
CREATE TABLE IF NOT EXISTS channel_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite') NOT NULL,
    available_date DATE NOT NULL,
    total_rooms INT NOT NULL DEFAULT 0,
    booked_rooms INT NOT NULL DEFAULT 0,
    blocked_rooms INT NOT NULL DEFAULT 0,
    available_rooms INT GENERATED ALWAYS AS (total_rooms - booked_rooms - blocked_rooms) STORED,
    minimum_stay INT DEFAULT 1,
    maximum_stay INT NULL,
    rate DECIMAL(10,2) NULL,
    status ENUM('Open', 'Closed', 'On Request') DEFAULT 'Open',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_channel_room_date (channel_id, room_type, available_date),
    INDEX idx_channel_id (channel_id),
    INDEX idx_room_type (room_type),
    INDEX idx_available_date (available_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL ROOM MAPPINGS TABLE (Room to Channel Mapping)
CREATE TABLE IF NOT EXISTS channel_room_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    room_id INT NOT NULL,
    channel_room_id VARCHAR(50) NULL,
    channel_room_name VARCHAR(100) NULL,
    status ENUM('Active', 'Inactive', 'Mapped', 'Unmapped') DEFAULT 'Active',
    last_sync TIMESTAMP NULL,
    sync_status ENUM('Success', 'Failed', 'Pending') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_channel_room (channel_id, room_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CHANNEL SYNC LOGS TABLE (Synchronization History)
CREATE TABLE IF NOT EXISTS channel_sync_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    sync_type ENUM('Rates', 'Availability', 'Reservations', 'Inventory') NOT NULL,
    sync_direction ENUM('Push', 'Pull', 'Both') DEFAULT 'Both',
    records_processed INT DEFAULT 0,
    records_successful INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    errors TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration_seconds INT NULL,
    status ENUM('Success', 'Partial', 'Failed', 'Running') DEFAULT 'Running',

    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Channel management tables created successfully!' AS Status;

-- ================================================================
-- SAMPLE DATA INSERTION
-- ================================================================

-- Insert common OTA channels
INSERT IGNORE INTO channels (name, display_name, type, status, commission_rate) VALUES
('booking.com', 'Booking.com', 'OTA', 'Active', 15.00),
('expedia', 'Expedia', 'OTA', 'Active', 18.00),
('agoda', 'Agoda', 'OTA', 'Active', 12.00),
('airbnb', 'Airbnb', 'OTA', 'Active', 3.00),
('direct', 'Direct Booking', 'Direct', 'Active', 0.00),
('traveloka', 'Traveloka', 'OTA', 'Active', 10.00);

SELECT 'Sample channel data inserted successfully!' AS Status;
