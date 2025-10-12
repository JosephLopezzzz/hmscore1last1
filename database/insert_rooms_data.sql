-- Insert room data after columns are added
USE inn_nexus;

-- Insert sample room data (use INSERT IGNORE to avoid duplicates)
INSERT IGNORE INTO rooms (room_number, room_type, floor_number, status, max_guests, rate) VALUES
-- Floor 1
('101', 'Single', 1, 'Occupied', 1, 1500.00),
('102', 'Double', 1, 'Vacant', 2, 2000.00),
('103', 'Deluxe', 1, 'Cleaning', 3, 2500.00),
('104', 'Single', 1, 'Vacant', 1, 1500.00),
('105', 'Suite', 1, 'Maintenance', 4, 3500.00),

-- Floor 2
('201', 'Suite', 2, 'Vacant', 4, 3500.00),
('202', 'Double', 2, 'Occupied', 2, 2000.00),
('203', 'Single', 2, 'Vacant', 1, 1500.00),
('204', 'Deluxe', 2, 'Cleaning', 3, 2500.00),
('205', 'Double', 2, 'Occupied', 2, 2000.00),

-- Floor 3
('301', 'Single', 3, 'Cleaning', 1, 1500.00),
('302', 'Deluxe', 3, 'Occupied', 3, 2500.00),
('303', 'Suite', 3, 'Vacant', 4, 3500.00),
('304', 'Single', 3, 'Maintenance', 1, 1500.00),
('305', 'Double', 3, 'Vacant', 2, 2000.00);

-- Check if data was inserted
SELECT COUNT(*) as total_rooms FROM rooms;
SELECT * FROM rooms LIMIT 5;
