-- ================================================================
-- INN NEXUS - RESERVATIONS TABLE
-- ================================================================
-- Creates the reservations table for booking management
-- ================================================================

USE inn_nexus;

-- RESERVATIONS TABLE (Booking Management)
CREATE TABLE IF NOT EXISTS reservations (
    id VARCHAR(50) PRIMARY KEY,
    guest_id INT NULL,
    room_id INT NULL,
    room_number VARCHAR(10) NULL,
    guest_name VARCHAR(200) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    birthdate DATE NULL,
    check_in_date DATETIME NOT NULL,
    check_out_date DATETIME NOT NULL,
    room_type ENUM('Single', 'Double', 'Deluxe', 'Suite', 'Twin', 'Triple', 'Quad', 'Family', 'Junior Suite', 'Executive Suite', 'Family Suite', 'Luxury Suite', 'Presidential Suite', 'Penthouse', 'Honeymoon Suite', 'Accessible Single', 'Accessible Double', 'Pet-Friendly', 'Extended Stay', 'Connecting') NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) DEFAULT 0.00,
    contact_number VARCHAR(20) NULL,
    special_requests TEXT NULL,
    notes TEXT NULL,
    invoice_method ENUM('email', 'print') DEFAULT 'print',
    payment_source ENUM('cash', 'online') DEFAULT 'cash',
    occupancy INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_check_in_date (check_in_date),
    INDEX idx_room_type (room_type),
    INDEX idx_room_status (room_number, status, check_in_date, check_out_date),
    CONSTRAINT chk_occupancy CHECK (occupancy > 0 AND occupancy <= 3),
    INDEX idx_check_out_date (check_out_date),
    INDEX idx_status (status),

    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Reservations table created successfully!' AS Status;
