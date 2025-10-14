-- ================================================================
-- INN NEXUS - GUEST FOLIOS AND PAYMENT SYSTEM TABLES
-- ================================================================
-- Creates tables for guest folios, transactions, and payments
-- Run this after the main database creation script
-- ================================================================

USE inn_nexus;

-- ================================================================
-- GUEST FOLIOS TABLE (Main billing entity)
-- ================================================================
CREATE TABLE IF NOT EXISTS guest_folios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_id VARCHAR(20) NOT NULL UNIQUE,
    guest_id INT NOT NULL,
    guest_name VARCHAR(255) NOT NULL,
    room_no VARCHAR(10) NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    total_charges DECIMAL(10,2) DEFAULT 0.00,
    total_paid DECIMAL(10,2) DEFAULT 0.00,
    balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('open', 'partial', 'paid', 'cancelled') DEFAULT 'open',
    folio_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_folio_id (folio_id),
    INDEX idx_guest_id (guest_id),
    INDEX idx_guest_name (guest_name),
    INDEX idx_room_no (room_no),
    INDEX idx_status (status),
    INDEX idx_folio_date (folio_date),

    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TRANSACTIONS TABLE (Payment transactions)
-- ================================================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_id VARCHAR(20) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_no VARCHAR(255) NULL,
    processed_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_folio_id (folio_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (folio_id) REFERENCES guest_folios(folio_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PAYMENTS TABLE (Detailed payment records)
-- ================================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_id VARCHAR(20) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') NOT NULL,
    reference_no VARCHAR(255) NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    processed_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_folio_id (folio_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (folio_id) REFERENCES guest_folios(folio_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- GUEST TIERS TABLE (For loyalty program)
-- ================================================================
CREATE TABLE IF NOT EXISTS guest_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_name ENUM('member', 'silver', 'gold', 'platinum') NOT NULL UNIQUE,
    min_stays INT DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tier_name (tier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default tiers
INSERT IGNORE INTO guest_tiers (tier_name, min_stays, discount_percentage) VALUES
('member', 0, 0.00),
('silver', 5, 5.00),
('gold', 15, 10.00),
('platinum', 30, 15.00);

-- ================================================================
-- ADD TIER COLUMN TO GUESTS TABLE IF NOT EXISTS
-- ================================================================
ALTER TABLE guests ADD COLUMN IF NOT EXISTS tier ENUM('member', 'silver', 'gold', 'platinum') DEFAULT 'member';
ALTER TABLE guests ADD COLUMN IF NOT EXISTS last_visit TIMESTAMP NULL;
ALTER TABLE guests ADD COLUMN IF NOT EXISTS stays INT DEFAULT 0;

SELECT 'Guest folios, transactions, payments, and guest tiers tables created successfully!' AS Status;
