-- ================================================================
-- INN NEXUS - GUESTS TABLE
-- ================================================================
-- Creates the guests table for storing guest information
-- ================================================================

USE inn_nexus;

-- GUESTS TABLE (Guest Information Management)
CREATE TABLE IF NOT EXISTS guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    id_type ENUM('Passport', 'Driver License', 'National ID') DEFAULT 'National ID',
    id_number VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    nationality VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_last_name (last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Guests table created successfully!' AS Status;
