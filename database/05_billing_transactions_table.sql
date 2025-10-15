-- ================================================================
-- INN NEXUS - BILLING TRANSACTIONS TABLE
-- ================================================================
-- Creates the billing_transactions table for financial transactions
-- ================================================================

USE inn_nexus;

-- BILLING_TRANSACTIONS TABLE (Financial Transactions)
CREATE TABLE IF NOT EXISTS billing_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id VARCHAR(50) NULL, -- Guest info and room info is here
    transaction_type ENUM('Room Charge', 'Service', 'Payment', 'Refund') DEFAULT 'Room Charge',
    amount DECIMAL(10,2) NOT NULL,
    payment_amount DECIMAL(10,2) DEFAULT NULL COMMENT 'Money the customer given to pay',
    balance DECIMAL(10,2) DEFAULT NULL COMMENT 'Amount to be paid by the payment_amount',
    `change` DECIMAL(10,2) DEFAULT NULL COMMENT 'payment_amount - balance (calculated)',
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') DEFAULT 'Cash',
    status ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_reservation_id (reservation_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status),

    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Billing transactions table created successfully!' AS Status;
