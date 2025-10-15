-- ================================================================
-- SAMPLE ROOM AND BILLING DATA
-- ================================================================
-- This script inserts comprehensive sample data for rooms, billing, and housekeeping
-- Run this after creating all necessary tables
-- ================================================================

USE inn_nexus;

-- ================================================================
-- SAMPLE ROOM DATA (30 Detailed Rooms)
-- ================================================================

-- =====================================================
-- FLOOR 1: Economy and Standard Rooms (8 rooms)
-- =====================================================

-- Singles (1 person)
INSERT INTO rooms (room_number, room_type, floor_number, status, max_guests, rate, amenities) VALUES
('101', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('102', 'Single', 1, 'Vacant', 1, 1200.00, 'WiFi, TV, Mini Fridge'),
('103', 'Single', 1, 'Cleaning', 1, 1200.00, 'WiFi, TV, Mini Fridge'),

-- Standard Double (2 people - couples)
('104', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('105', 'Double', 1, 'Occupied', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),
('106', 'Double', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, Coffee Maker'),

-- Twin (2 people - friends/colleagues)
('107', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),
('108', 'Twin', 1, 'Vacant', 2, 1800.00, 'WiFi, TV, Mini Fridge, 2 Single Beds'),

-- =====================================================
-- FLOOR 2: Deluxe and Family Rooms (6 rooms)
-- =====================================================

-- Deluxe Double (2 people - couples, premium)
('201', 'Deluxe', 2, 'Vacant', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('202', 'Deluxe', 2, 'Occupied', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),
('203', 'Deluxe', 2, 'Cleaning', 2, 2500.00, 'WiFi, Smart TV, Mini Bar, Balcony, Coffee Maker'),

-- Family Room (4 people - families with kids)
('204', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('205', 'Family', 2, 'Occupied', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),
('206', 'Family', 2, 'Vacant', 4, 3000.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Sofa Bed'),

-- =====================================================
-- FLOOR 3: Suites and Large Groups (6 rooms)
-- =====================================================

-- Suite (2 people - luxury couples)
('301', 'Suite', 3, 'Vacant', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room'),
('302', 'Suite', 3, 'Occupied', 2, 3500.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Living Room'),

-- Triple (3 people - small groups)
('303', 'Triple', 3, 'Vacant', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds'),
('304', 'Triple', 3, 'Cleaning', 3, 2200.00, 'WiFi, TV, Mini Fridge, 3 Single Beds'),

-- Quad (4 people - groups/families)
('305', 'Quad', 3, 'Vacant', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),
('306', 'Quad', 3, 'Maintenance', 4, 2800.00, 'WiFi, TV, Mini Fridge, 4 Single Beds'),

-- =====================================================
-- FLOOR 4: Premium Family and Accessible Rooms (5 rooms)
-- =====================================================

-- Executive Suite (3 people)
('401', 'Executive Suite', 4, 'Vacant', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room'),
('402', 'Executive Suite', 4, 'Occupied', 3, 4000.00, 'WiFi, Smart TV, Mini Bar, Jacuzzi, Balcony, Work Desk, Living Room'),

-- Family Suite (5 people - large families)
('403', 'Family Suite', 4, 'Vacant', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room'),
('404', 'Family Suite', 4, 'Cleaning', 5, 4500.00, 'WiFi, Smart TV, Mini Bar, 2 Bedrooms, Kitchen, Living Room'),

-- Accessible Room (2 people - wheelchair accessible)
('405', 'Accessible', 4, 'Vacant', 2, 2000.00, 'WiFi, TV, Mini Fridge, Wheelchair Access, Roll-in Shower'),

-- =====================================================
-- FLOOR 5: Penthouse and VIP (5 rooms)
-- =====================================================

-- Penthouse (4 people - luxury)
('501', 'Penthouse', 5, 'Vacant', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room'),
('502', 'Penthouse', 5, 'Occupied', 4, 6000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Balcony, 2 Bedrooms, Living Room'),

-- VIP Suite (6 people - large groups/events)
('503', 'VIP Suite', 5, 'Vacant', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area'),
('504', 'VIP Suite', 5, 'Maintenance', 6, 7000.00, 'WiFi, Smart TV, Full Kitchen, Jacuzzi, Private Terrace, 3 Bedrooms, Dining Area'),

-- Presidential Suite (8 people - ultimate luxury)
('505', 'Presidential', 5, 'Vacant', 8, 10000.00, 'WiFi, Smart TV, Full Kitchen, 2 Jacuzzis, Private Terrace, 4 Bedrooms, Cinema Room, Butler Service')

ON DUPLICATE KEY UPDATE
    room_type = VALUES(room_type),
    floor_number = VALUES(floor_number),
    max_guests = VALUES(max_guests),
    rate = VALUES(rate),
    amenities = VALUES(amenities),
    status = VALUES(status);

-- ================================================================
-- SAMPLE GUESTS DATA (15 Diverse Guests)
-- ================================================================

-- Insert sample guest data for testing and demonstration
INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number, date_of_birth, nationality, notes) VALUES
('Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0101', '123 Oak Street', 'New York', 'United States', 'Driver License', 'DL123456789', '1985-03-15', 'American', 'VIP customer, prefers quiet rooms'),
('Michael', 'Chen', 'm.chen@techcorp.com', '+1-555-0102', '456 Pine Avenue', 'San Francisco', 'United States', 'Passport', 'P123456789', '1990-07-22', 'American', 'Business traveler, frequent guest'),
('Emma', 'Williams', 'emma.w@email.com', '+44-20-7946-0103', '789 Elm Road', 'London', 'United Kingdom', 'Passport', 'P987654321', '1988-11-08', 'British', 'Family vacation, two children'),
('David', 'Brown', 'd.brown@consulting.com', '+1-555-0104', '321 Maple Drive', 'Chicago', 'United States', 'National ID', 'SSN123456789', '1975-01-30', 'American', 'Conference attendee'),
('Lisa', 'Anderson', 'lisa.anderson@email.com', '+46-8-525-0105', '654 Cedar Lane', 'Stockholm', 'Sweden', 'Passport', 'P555666777', '1982-05-18', 'Swedish', 'Honeymoon trip'),
('James', 'Wilson', 'james.w@freelance.com', '+61-2-9374-0106', '987 Birch Boulevard', 'Sydney', 'Australia', 'Driver License', 'DL987654321', '1992-09-12', 'Australian', 'Digital nomad, long-term stay'),
('Maria', 'Garcia', 'maria.garcia@email.com', '+34-91-445-0107', '147 Palm Street', 'Madrid', 'Spain', 'National ID', 'DNI12345678', '1978-12-03', 'Spanish', 'Cultural tour visitor'),
('Robert', 'Taylor', 'r.taylor@engineering.com', '+1-555-0108', '258 Willow Way', 'Seattle', 'United States', 'Passport', 'P456789123', '1980-04-25', 'American', 'Tech conference speaker'),
('Anna', 'Novak', 'anna.novak@email.cz', '+420-224-0109', '369 Spruce Street', 'Prague', 'Czech Republic', 'Passport', 'P789123456', '1995-08-14', 'Czech', 'Student traveler, budget conscious'),
('Pierre', 'Dubois', 'pierre.dubois@business.fr', '+33-1-4276-0110', '741 Fir Avenue', 'Paris', 'France', 'National ID', 'IDF123456789', '1970-06-20', 'French', 'Wine tour, anniversary celebration'),
('Yuki', 'Tanaka', 'yuki.tanaka@email.jp', '+81-3-3570-0111', '852 Cherry Blossom St', 'Tokyo', 'Japan', 'Passport', 'P321654987', '1987-02-28', 'Japanese', 'Business meeting in the city'),
('Ahmed', 'Al-Rashid', 'ahmed.rashid@email.ae', '+971-4-331-0112', '963 Desert Palm Ave', 'Dubai', 'United Arab Emirates', 'Passport', 'P654987321', '1983-10-10', 'Emirati', 'Luxury shopping trip'),
('Sofia', 'Rodriguez', 'sofia.r@travelblog.com', '+52-55-5208-0113', '159 Cactus Road', 'Mexico City', 'Mexico', 'Passport', 'P147258369', '1991-12-05', 'Mexican', 'Travel blogger, social media influencer'),
('Thomas', 'Müller', 'thomas.mueller@email.de', '+49-30-2098-0114', '357 Linden Street', 'Berlin', 'Germany', 'National ID', 'IDG987654321', '1976-07-15', 'German', 'Music festival attendee'),
('Isabella', 'Costa', 'isabella.costa@email.br', '+55-11-3069-0115', '468 Carnival Square', 'Rio de Janeiro', 'Brazil', 'Passport', 'P963852741', '1989-03-22', 'Brazilian', 'Carnival season visitor')

ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    email = VALUES(email),
    phone = VALUES(phone),
    address = VALUES(address),
    city = VALUES(city),
    country = VALUES(country),
    id_type = VALUES(id_type),
    id_number = VALUES(id_number),
    date_of_birth = VALUES(date_of_birth),
    nationality = VALUES(nationality),
    notes = VALUES(notes);

-- ================================================================
-- SAMPLE BILLING TRANSACTIONS
-- ================================================================

INSERT INTO billing_transactions (reservation_id, transaction_type, amount, payment_amount, balance, `change`, payment_method, status, transaction_date, notes) VALUES
(NULL, 'Room Charge', 555.00, 555.00, 0.00, 0.00, 'Card', 'Paid', NOW(), 'Room charge for stay'),
(NULL, 'Room Charge', 840.00, NULL, 840.00, NULL, 'Cash', 'Pending', NOW(), 'Pending payment for room'),
(NULL, 'Room Charge', 495.00, 495.00, 0.00, 0.00, 'GCash', 'Paid', NOW(), 'Mobile payment for room'),
(NULL, 'Room Charge', 900.00, 900.00, 0.00, 0.00, 'Bank Transfer', 'Paid', NOW(), 'Bank transfer payment'),
(NULL, 'Room Charge', 975.00, NULL, 975.00, NULL, 'Card', 'Pending', NOW(), 'Credit card payment pending'),
(NULL, 'Room Charge', 1220.00, 1220.00, 0.00, 0.00, 'Card', 'Paid', NOW(), 'Room charge settled'),
(NULL, 'Room Charge', 680.00, NULL, 680.00, NULL, 'Cash', 'Pending', NOW(), 'Cash payment pending'),
(NULL, 'Room Charge', 2100.00, 2100.00, 0.00, 0.00, 'Bank Transfer', 'Paid', NOW(), 'Full payment received')

ON DUPLICATE KEY UPDATE
    amount = VALUES(amount),
    payment_amount = VALUES(payment_amount),
    balance = VALUES(balance),
    `change` = VALUES(`change`),
    payment_method = VALUES(payment_method),
    status = VALUES(status),
    notes = VALUES(notes);

-- ================================================================
-- DYNAMIC HOUSEKEEPING TASKS GENERATION
-- ================================================================

-- Create housekeeping tasks for rooms that need them
INSERT INTO housekeeping_tasks (room_id, room_number, task_type, status, priority, guest_name, notes)
SELECT
    r.id,
    r.room_number,
    CASE
        WHEN r.status = 'Cleaning' THEN 'cleaning'
        WHEN r.status = 'Maintenance' THEN 'maintenance'
        ELSE 'cleaning'
    END as task_type,
    CASE
        WHEN r.status = 'Cleaning' THEN 'pending'
        WHEN r.status = 'Maintenance' THEN 'maintenance'
        WHEN r.status = 'Occupied' THEN 'completed'
        ELSE 'pending'
    END as status,
    CASE
        WHEN r.status = 'Maintenance' THEN 'urgent'
        WHEN r.room_type IN ('Suite', 'Deluxe', 'Executive Suite', 'Penthouse', 'Presidential', 'VIP Suite', 'Family') THEN 'high'
        ELSE 'normal'
    END as priority,
    r.guest_name,
    r.maintenance_notes as notes
FROM rooms r
WHERE r.status IN ('Cleaning', 'Maintenance')
   OR r.room_number IN ('101', '102', '201', '204', '304', '404', '504')
ON DUPLICATE KEY UPDATE
    updated_at = NOW(),
    status = VALUES(status),
    priority = VALUES(priority);

-- ================================================================
-- SUMMARY AND VERIFICATION
-- ================================================================

-- Show final room summary
SELECT
    'SAMPLE DATA INSERTION COMPLETE' as status,
    COUNT(*) as total_rooms_inserted,
    COUNT(DISTINCT floor_number) as floors_covered,
    COUNT(DISTINCT room_type) as room_types_available
FROM rooms;

-- Show rooms by floor
SELECT
    floor_number,
    COUNT(*) as rooms_per_floor,
    GROUP_CONCAT(DISTINCT room_type ORDER BY room_type SEPARATOR ', ') as room_types,
    MIN(rate) as min_rate,
    MAX(rate) as max_rate,
    SUM(max_guests) as total_capacity
FROM rooms
GROUP BY floor_number
ORDER BY floor_number;

-- Show billing transactions summary
SELECT
    COUNT(*) as total_billing_transactions,
    SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_transactions,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_transactions,
    SUM(amount) as total_amount
FROM billing_transactions;

-- Show housekeeping tasks summary
SELECT
    COUNT(*) as total_housekeeping_tasks,
    COUNT(DISTINCT room_number) as rooms_with_tasks,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
FROM housekeeping_tasks;

SELECT '✅ Sample room, billing, and housekeeping data loaded successfully!' AS final_status;

-- ================================================================
-- SAMPLE RESERVATIONS DATA (Dynamic Check-out Today)
-- ================================================================
-- This section adds sample reservations with check-out dates set to today
-- Run this to populate active reservations for testing

-- Sample Reservation 1
INSERT INTO reservations (
    id,
    guest_id,
    room_id,
    check_in_date,
    check_out_date,
    status,
    payment_status
) VALUES (
    'RSV-TEST-001',
    1,
    1,
    DATE_SUB(CURDATE(), INTERVAL 2 DAY),
    CURDATE(),
    'Checked In',
    'FULLY PAID'
);

-- Sample Reservation 2
INSERT INTO reservations (
    id,
    guest_id,
    room_id,
    check_in_date,
    check_out_date,
    status,
    payment_status
) VALUES (
    'RSV-TEST-002',
    2,
    2,
    DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    CURDATE(),
    'Checked In',
    'FULLY PAID'
);

-- Sample Reservation 3
INSERT INTO reservations (
    id,
    guest_id,
    room_id,
    check_in_date,
    check_out_date,
    status,
    payment_status
) VALUES (
    'RSV-TEST-003',
    3,
    3,
    DATE_SUB(CURDATE(), INTERVAL 3 DAY),
    CURDATE(),
    'Checked In',
    'FULLY PAID'
);

INSERT INTO billing_transactions (
    reservation_id,
    transaction_type,
    amount,
    payment_amount,
    balance,
    `change`,
    payment_method,
    status,
    transaction_date,
    notes
) VALUES
-- Transactions for Reservation 1
('RSV-TEST-001', 'Room Charge', 150.00, 150.00, 0.00, 0.00, 'Cash', 'Paid', CURDATE(), 'Room charge for reservation RSV-TEST-001'),
-- Transactions for Reservation 2
('RSV-TEST-002', 'Room Charge', 200.00, 200.00, 0.00, 0.00, 'Card', 'Paid', CURDATE(), 'Room charge for reservation RSV-TEST-002'),
('RSV-TEST-002', 'Service', 50.00, 50.00, 0.00, 0.00, 'Card', 'Paid', CURDATE(), 'Additional service for reservation RSV-TEST-002'),
-- Transactions for Reservation 3
('RSV-TEST-003', 'Room Charge', 175.00, 175.00, 0.00, 0.00, 'Cash', 'Paid', CURDATE(), 'Room charge for reservation RSV-TEST-003');

SELECT '✅ Sample reservations and billing data loaded successfully!' AS final_status;
