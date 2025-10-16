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

-- LOYALTY PROGRAMS TABLE
CREATE TABLE IF NOT EXISTS loyalty_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    points_per_stay DECIMAL(10,2) DEFAULT 100,
    points_per_dollar DECIMAL(10,2) DEFAULT 1,
    minimum_points_redeem INT DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    enrollment_auto TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GUEST LOYALTY MEMBERSHIPS TABLE
CREATE TABLE IF NOT EXISTS guest_loyalty_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_id INT NOT NULL,
    loyalty_program_id INT NOT NULL,
    membership_number VARCHAR(50) NOT NULL UNIQUE,
    points_balance DECIMAL(10,2) DEFAULT 0,
    tier_level ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    enrolled_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (loyalty_program_id) REFERENCES loyalty_programs(id) ON DELETE CASCADE,
    INDEX idx_guest_id (guest_id),
    INDEX idx_membership_number (membership_number),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOYALTY REWARDS TABLE
CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loyalty_program_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    reward_type ENUM('discount', 'free_night', 'upgrade', 'amenity', 'points_bonus') NOT NULL,
    points_required INT NOT NULL,
    discount_value DECIMAL(10,2) NULL,
    discount_percentage DECIMAL(5,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    usage_limit INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (loyalty_program_id) REFERENCES loyalty_programs(id) ON DELETE CASCADE,
    INDEX idx_loyalty_program (loyalty_program_id),
    INDEX idx_points_required (points_required),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOYALTY TRANSACTIONS TABLE
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_loyalty_id INT NOT NULL,
    transaction_type ENUM('earn', 'redeem', 'expire', 'adjust') NOT NULL,
    points_amount DECIMAL(10,2) NOT NULL,
    description TEXT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (guest_loyalty_id) REFERENCES guest_loyalty_memberships(id) ON DELETE CASCADE,
    INDEX idx_guest_loyalty (guest_loyalty_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EMAIL CAMPAIGNS TABLE
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    recipient_type ENUM('all_guests', 'loyalty_members', 'recent_guests', 'custom_list') NOT NULL,
    status ENUM('draft', 'scheduled', 'sent', 'cancelled') DEFAULT 'draft',
    scheduled_date DATETIME NULL,
    sent_date DATETIME NULL,
    sent_count INT DEFAULT 0,
    open_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_recipient_type (recipient_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PROMOTION USAGE TABLE
CREATE TABLE IF NOT EXISTS promotion_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promotional_offer_id INT NOT NULL,
    guest_id INT NULL,
    reservation_id INT NULL,
    usage_date DATE NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (promotional_offer_id) REFERENCES promotional_offers(id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    INDEX idx_promotional_offer (promotional_offer_id),
    INDEX idx_guest_id (guest_id),
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_usage_date (usage_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Marketing and Promotion tables created successfully!' AS Status;
