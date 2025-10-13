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
    check_in_date DATETIME NOT NULL,
    check_out_date DATETIME NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Pending',
    invoice_method ENUM('email', 'print') DEFAULT 'print',
    payment_source ENUM('cash', 'online') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_check_in_date (check_in_date),
    INDEX idx_check_out_date (check_out_date),
    INDEX idx_status (status),

    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Reservations table created successfully!' AS Status;
