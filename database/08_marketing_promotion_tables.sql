-- ================================================================
-- INN NEXUS - MARKETING AND PROMOTION TABLES
-- ================================================================
-- Creates tables for marketing campaigns, promotions, and loyalty programs
-- ================================================================

USE inn_nexus;

-- MARKETING CAMPAIGNS TABLE
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    campaign_type ENUM('email', 'social_media', 'advertising', 'promotion', 'loyalty', 'seasonal') NOT NULL,
    target_audience TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    budget DECIMAL(10,2) NULL,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_campaign_type (campaign_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PROMOTIONAL OFFERS TABLE
CREATE TABLE IF NOT EXISTS promotional_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    offer_type ENUM('percentage_discount', 'fixed_amount_discount', 'free_nights', 'upgrade', 'package_deal') NOT NULL,
    discount_value DECIMAL(10,2) NULL,
    discount_percentage DECIMAL(5,2) NULL,
    min_stay_nights INT DEFAULT 1,
    max_discount_amount DECIMAL(10,2) NULL,
    applicable_room_types TEXT NULL,
    applicable_rate_plans TEXT NULL,
    usage_limit INT NULL,
    usage_count INT DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_valid_dates (valid_from, valid_until),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SELECT 'Marketing and Promotion tables created successfully!' AS Status;
