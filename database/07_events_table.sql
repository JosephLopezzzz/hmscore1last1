USE inn_nexus;
-- Events table for conferences and group bookings
CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  organizer_name VARCHAR(255) NOT NULL,
  organizer_contact VARCHAR(255) NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  attendees_expected INT DEFAULT 0,
  setup_type ENUM('Conference','Banquet','Theater','Classroom','U-Shape','Other') DEFAULT 'Conference',
  room_blocks JSON NULL,
  price_estimate DECIMAL(10,2) DEFAULT 0.00,
  status ENUM('Pending','Approved','Cancelled') DEFAULT 'Pending',
  created_by VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_events_time (start_datetime, end_datetime),
  INDEX idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


