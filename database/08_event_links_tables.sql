-- ================================================================
-- INN NEXUS - EVENT LINKS TABLES
-- ================================================================
-- Creates tables for linking events to reservations and services
-- ================================================================

USE inn_nexus;

-- Link reservations to events
CREATE TABLE IF NOT EXISTS event_reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  reservation_id VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_event_id (event_id),
  INDEX idx_reservation_id (reservation_id),
  
  CONSTRAINT fk_er_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  CONSTRAINT fk_er_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_event_res (event_id, reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional event services attached to an event
CREATE TABLE IF NOT EXISTS event_services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  service_name VARCHAR(255) NOT NULL,
  qty INT DEFAULT 1,
  price DECIMAL(10,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_event_id (event_id),
  
  CONSTRAINT fk_es_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Event links tables created successfully!' AS Status;



