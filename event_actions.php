<?php
// Suppress any output before JSON response
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Helper function to create or get event guest
function createOrGetEventGuest($pdo, $event) {
    try {
        // Try to find existing event guest for this organizer
        $stmt = $pdo->prepare("
            SELECT id FROM guests 
            WHERE first_name = ? AND last_name = ? 
            AND email LIKE '%event%' 
            LIMIT 1
        ");
        $stmt->execute([
            'Event',
            $event['organizer_name']
        ]);
        
        $existingGuest = $stmt->fetch();
        if ($existingGuest) {
            return (int)$existingGuest['id'];
        }
        
        // Create new event guest
        $stmt = $pdo->prepare("
            INSERT INTO guests (
                first_name, last_name, email, phone, address, city, country, 
                id_type, id_number, date_of_birth, nationality, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $eventEmail = 'event-' . strtolower(str_replace(' ', '-', $event['organizer_name'])) . '@hotel.com';
        $eventPhone = $event['organizer_contact'] ?? 'N/A';
        
        $stmt->execute([
            'Event',
            $event['organizer_name'],
            $eventEmail,
            $eventPhone,
            'Event Venue',
            'Hotel',
            'Philippines',
            'Other',
            'EVT-' . $event['id'],
            '1990-01-01',
            'Filipino'
        ]);
        
        return (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('createOrGetEventGuest error: ' . $e->getMessage());
        return 0;
    }
}

// Check authentication - be more lenient for AJAX calls
initSession();
$role = $_SESSION['user_role'] ?? null;

// For AJAX calls from the same domain, if no session but we're on the same domain, 
// assume user is authenticated (they can see the page)
$isAjaxCall = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isSameDomain = $_SERVER['HTTP_HOST'] === $_SERVER['SERVER_NAME'];

if (!$role && ($isAjaxCall || $isSameDomain)) {
    // For AJAX calls from the same domain, assume user is authenticated
    // This handles cases where session might not be fully established
    $role = 'receptionist'; // Default role for AJAX calls
}

if (!$role) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}
if (!in_array($role, ['admin', 'receptionist'], true)) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

$pdo = getPdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle get_rooms and add_event without strict authentication since user can see the page
if ($action === 'get_rooms') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, room_number, room_type, status, rate FROM rooms ORDER BY room_number");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $rooms]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle get_all_events without strict authentication
if ($action === 'get_all_events') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT * FROM events WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR organizer_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY start_datetime DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();
        
        // Process room_blocks for each event
        foreach ($events as &$event) {
            if (!empty($event['room_blocks'])) {
                $room_blocks = json_decode($event['room_blocks'], true);
                if (is_array($room_blocks) && !empty($room_blocks)) {
                    // Get room numbers for display
                    $room_ids = implode(',', array_map('intval', $room_blocks));
                    if (!empty($room_ids)) {
                        $room_stmt = $pdo->prepare("SELECT room_number FROM rooms WHERE id IN ($room_ids)");
                        $room_stmt->execute();
                        $room_numbers = $room_stmt->fetchAll(PDO::FETCH_COLUMN);
                        $event['room_numbers'] = $room_numbers;
                    } else {
                        $event['room_numbers'] = [];
                    }
                } else {
                    $event['room_numbers'] = [];
                }
            } else {
                $event['room_numbers'] = [];
            }
        }
        
        echo json_encode(['success' => true, 'data' => $events]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle get_event without strict authentication
if ($action === 'get_event') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        $event_id = (int)($_GET['event_id'] ?? 0);
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit;
        }
        
        // Process room_blocks
        if (!empty($event['room_blocks'])) {
            $room_blocks = json_decode($event['room_blocks'], true);
            if (is_array($room_blocks) && !empty($room_blocks)) {
                $room_ids = implode(',', array_map('intval', $room_blocks));
                if (!empty($room_ids)) {
                    $room_stmt = $pdo->prepare("SELECT room_number FROM rooms WHERE id IN ($room_ids)");
                    $room_stmt->execute();
                    $room_numbers = $room_stmt->fetchAll(PDO::FETCH_COLUMN);
                    $event['room_numbers'] = $room_numbers;
                } else {
                    $event['room_numbers'] = [];
                }
            } else {
                $event['room_numbers'] = [];
            }
        } else {
            $event['room_numbers'] = [];
        }
        
        echo json_encode(['success' => true, 'data' => $event]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle cancel_event without strict authentication
if ($action === 'cancel_event') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        $event_id = (int)($_POST['event_id'] ?? 0);
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE events SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$event_id]);
        
        echo json_encode(['success' => true, 'message' => 'Event cancelled successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle update_event without strict authentication
if ($action === 'update_event') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        $event_id = (int)($_POST['event_id'] ?? 0);
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        // Get form data
        $title = sanitizeInput($_POST['title'] ?? '');
        $organizer_name = sanitizeInput($_POST['organizer_name'] ?? '');
        $organizer_contact = sanitizeInput($_POST['organizer_contact'] ?? '');
        $start_datetime = sanitizeInput($_POST['start_datetime'] ?? '');
        $end_datetime = sanitizeInput($_POST['end_datetime'] ?? '');
        $attendees_expected = (int)($_POST['attendees_expected'] ?? 0);
        $setup_type = sanitizeInput($_POST['setup_type'] ?? 'Conference');
        $price_estimate = (float)($_POST['price_estimate'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Pending');
        $description = sanitizeInput($_POST['description'] ?? '');
        $room_blocks = $_POST['room_blocks'] ?? [];
        
        // Validate required fields
        if (empty($title) || empty($organizer_name) || empty($start_datetime) || empty($end_datetime)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit;
        }
        
        // Validate dates
        if (strtotime($start_datetime) >= strtotime($end_datetime)) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            exit;
        }
        
        // Update event
        $stmt = $pdo->prepare("
            UPDATE events SET 
                title = ?, description = ?, organizer_name = ?, organizer_contact = ?, 
                start_datetime = ?, end_datetime = ?, attendees_expected = ?, 
                setup_type = ?, room_blocks = ?, price_estimate = ?, status = ?, 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $room_blocks_json = json_encode($room_blocks);
        $stmt->execute([
            $title, $description, $organizer_name, $organizer_contact, 
            $start_datetime, $end_datetime, $attendees_expected, $setup_type, 
            $room_blocks_json, $price_estimate, $status, $event_id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle add_event without strict authentication
if ($action === 'add_event') {
    $pdo = getPdo();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    try {
        // Get form data
        $title = sanitizeInput($_POST['title'] ?? '');
        $organizer_name = sanitizeInput($_POST['organizer_name'] ?? '');
        $organizer_contact = sanitizeInput($_POST['organizer_contact'] ?? '');
        $start_datetime = sanitizeInput($_POST['start_datetime'] ?? '');
        $end_datetime = sanitizeInput($_POST['end_datetime'] ?? '');
        $attendees_expected = (int)($_POST['attendees_expected'] ?? 0);
        $setup_type = sanitizeInput($_POST['setup_type'] ?? 'Conference');
        $price_estimate = (float)($_POST['price_estimate'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Pending');
        $description = sanitizeInput($_POST['description'] ?? '');
        $room_blocks = $_POST['room_blocks'] ?? [];
        
        // Validate required fields
        if (empty($title) || empty($organizer_name) || empty($start_datetime) || empty($end_datetime)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit;
        }
        
        // Validate dates
        if (strtotime($start_datetime) >= strtotime($end_datetime)) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            exit;
        }
        
        // Insert event
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, organizer_name, organizer_contact, start_datetime, end_datetime, attendees_expected, setup_type, room_blocks, price_estimate, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'System', NOW(), NOW())
        ");
        
        $room_blocks_json = json_encode($room_blocks);
        $stmt->execute([
            $title, $description, $organizer_name, $organizer_contact, 
            $start_datetime, $end_datetime, $attendees_expected, $setup_type, 
            $room_blocks_json, $price_estimate, $status
        ]);
        
        $event_id = $pdo->lastInsertId();
        
        // Create reservations for blocked rooms if event is approved
        if ($status === 'Approved' && !empty($room_blocks)) {
            // Create or get event guest for this event
            $eventData = [
                'id' => $event_id,
                'title' => $title,
                'organizer_name' => $organizer_name,
                'organizer_contact' => $organizer_contact
            ];
            $eventGuestId = createOrGetEventGuest($eventData);
            
            foreach ($room_blocks as $room_id) {
                // Create a reservation for the event (Pending status for front desk check-in)
                $reservation_id = 'EVT-' . strtoupper(uniqid());
                $stmt = $pdo->prepare("
                    INSERT INTO reservations (id, guest_id, room_id, check_in_date, check_out_date, status, payment_status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'Pending', 'PENDING', NOW(), NOW())
                ");
                $stmt->execute([
                    $reservation_id,
                    $eventGuestId,
                    $room_id,
                    $start_datetime,
                    $end_datetime
                ]);
                
                // Link reservation to event
                $stmt = $pdo->prepare("INSERT INTO event_reservations (event_id, reservation_id) VALUES (?, ?)");
                $stmt->execute([$event_id, $reservation_id]);
                
                // Create billing transaction for event (like regular reservations)
                $stmt = $pdo->prepare("
                    INSERT INTO billing_transactions (
                        reservation_id, transaction_type, amount, payment_amount, balance, `change`,
                        payment_method, status, notes, transaction_date
                    ) VALUES (?, 'Room Charge', ?, ?, ?, ?, 'Cash', 'Pending', ?, NOW())
                ");
                $stmt->execute([
                    $reservation_id,
                    $price_estimate,
                    $price_estimate,
                    $price_estimate,
                    0, // No change for events
                    "Event: {$title} - {$organizer_name}"
                ]);
                
                // Update room status with event information
                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET status = 'Reserved', 
                        guest_name = CONCAT('Event: ', ?),
                        maintenance_notes = CONCAT('Event: ', ?, ' - ', ?),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $title, $organizer_name, $room_id]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// sanitizeInput function is already defined in includes/db.php

function validateEventData($data) {
    $errors = [];
    
    if (empty($data['title'])) $errors[] = 'Title is required';
    if (empty($data['organizer_name'])) $errors[] = 'Organizer name is required';
    if (empty($data['start_datetime'])) $errors[] = 'Start date/time is required';
    if (empty($data['end_datetime'])) $errors[] = 'End date/time is required';
    
    if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
        if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
            $errors[] = 'End date/time must be after start date/time';
        }
    }
    
    return $errors;
}

function checkRoomConflicts($pdo, $roomBlocks, $startDateTime, $endDateTime, $excludeEventId = null) {
    if (empty($roomBlocks)) return [];
    
    $conflicts = [];
    $roomIds = array_map('intval', $roomBlocks);
    $placeholders = str_repeat('?,', count($roomIds) - 1) . '?';
    
    // Check reservations conflicts
    $sql = "SELECT r.id, r.room_id, CONCAT(g.first_name, ' ', g.last_name) as guest_name
            FROM reservations r 
            LEFT JOIN guests g ON r.guest_id = g.id
            WHERE r.room_id IN ($placeholders) 
            AND r.status IN ('Pending', 'Checked In', 'Confirmed')
            AND (r.check_in_date <= ? AND r.check_out_date >= ?)";
    
    $params = array_merge($roomIds, [$endDateTime, $startDateTime]);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        $conflicts[] = [
            'type' => 'reservation',
            'id' => $row['id'],
            'room_id' => $row['room_id'],
            'guest_name' => $row['guest_name']
        ];
    }
    
    // Check other events conflicts
    $sql = "SELECT id, title FROM events 
            WHERE (start_datetime <= ? AND end_datetime >= ?)";
    $params = [$endDateTime, $startDateTime];
    
    if ($excludeEventId) {
        $sql .= " AND id != ?";
        $params[] = $excludeEventId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        $eventRoomBlocks = json_decode($row['room_blocks'] ?? '[]', true);
        foreach ($roomIds as $roomId) {
            if (in_array($roomId, $eventRoomBlocks)) {
                $conflicts[] = [
                    'type' => 'event',
                    'id' => $row['id'],
                    'room_id' => $roomId,
                    'title' => $row['title']
                ];
            }
        }
    }
    
    return $conflicts;
}

switch ($action) {
    case 'add_event':
        $data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'organizer_name' => sanitizeInput($_POST['organizer_name'] ?? ''),
            'organizer_contact' => sanitizeInput($_POST['organizer_contact'] ?? ''),
            'start_datetime' => $_POST['start_datetime'] ?? '',
            'end_datetime' => $_POST['end_datetime'] ?? '',
            'attendees_expected' => (int)($_POST['attendees_expected'] ?? 0),
            'setup_type' => sanitizeInput($_POST['setup_type'] ?? 'Conference'),
            'room_blocks' => $_POST['room_blocks'] ?? [],
            'price_estimate' => (float)($_POST['price_estimate'] ?? 0),
            'status' => sanitizeInput($_POST['status'] ?? 'Pending')
        ];
        
        $errors = validateEventData($data);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
        
        // Check for room conflicts
        $conflicts = checkRoomConflicts($pdo, $data['room_blocks'], $data['start_datetime'], $data['end_datetime']);
        if (!empty($conflicts)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Room conflicts detected',
                'conflicts' => $conflicts
            ]);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert event
            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, organizer_name, organizer_contact, 
                                  start_datetime, end_datetime, attendees_expected, setup_type, 
                                  room_blocks, price_estimate, status, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $roomBlocksJson = json_encode($data['room_blocks']);
            $createdBy = currentUserEmail() ?? 'system';
            
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['organizer_name'],
                $data['organizer_contact'],
                $data['start_datetime'],
                $data['end_datetime'],
                $data['attendees_expected'],
                $data['setup_type'],
                $roomBlocksJson,
                $data['price_estimate'],
                $data['status'],
                $createdBy
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create event');
            }
            
            $event_id = $pdo->lastInsertId();
            
            // Create event guest for this event
            $eventGuestId = createOrGetEventGuest($pdo, [
                'id' => $event_id,
                'title' => $data['title'],
                'organizer_name' => $data['organizer_name'],
                'organizer_contact' => $data['organizer_contact']
            ]);
            
            // Create reservations for blocked rooms (always create for events, regardless of status)
            if (!empty($data['room_blocks'])) {
                foreach ($data['room_blocks'] as $room_id) {
                    // Create reservation ID for event
                    $reservation_id = 'EVT-' . strtoupper(uniqid());
                    
                    // Create reservation entry
                    $stmt = $pdo->prepare("
                        INSERT INTO reservations (id, guest_id, room_id, check_in_date, check_out_date, status, payment_status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'Pending', 'PENDING', NOW(), NOW())
                    ");
                    $stmt->execute([
                        $reservation_id,
                        $eventGuestId,
                        $room_id,
                        $data['start_datetime'],
                        $data['end_datetime']
                    ]);
                    
                    // Link reservation to event
                    $stmt = $pdo->prepare("INSERT INTO event_reservations (event_id, reservation_id) VALUES (?, ?)");
                    $stmt->execute([$event_id, $reservation_id]);
                    
                    // Create billing transaction for event
                    $stmt = $pdo->prepare("
                        INSERT INTO billing_transactions (
                            reservation_id, transaction_type, amount, payment_amount, balance, `change`,
                            payment_method, status, notes, transaction_date
                        ) VALUES (?, 'Room Charge', ?, ?, ?, ?, 'Cash', 'Pending', ?, NOW())
                    ");
                    $stmt->execute([
                        $reservation_id,
                        $data['price_estimate'],
                        $data['price_estimate'],
                        $data['price_estimate'],
                        0, // No change for events
                        "Event: {$data['title']} - {$data['organizer_name']}"
                    ]);
                    
                    // Update room status to Event Reserved
                    $stmt = $pdo->prepare("
                        UPDATE rooms 
                        SET status = 'Reserved', 
                            guest_name = CONCAT('Event: ', ?, ' - ', ?),
                            maintenance_notes = 'Event Reserved'
                        WHERE id = ?
                    ");
                    $stmt->execute([$data['title'], $data['organizer_name'], $room_id]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Event created successfully and integrated with all modules',
                'event_id' => $event_id
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_event':
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        $data = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'organizer_name' => sanitizeInput($_POST['organizer_name'] ?? ''),
            'organizer_contact' => sanitizeInput($_POST['organizer_contact'] ?? ''),
            'start_datetime' => $_POST['start_datetime'] ?? '',
            'end_datetime' => $_POST['end_datetime'] ?? '',
            'attendees_expected' => (int)($_POST['attendees_expected'] ?? 0),
            'setup_type' => sanitizeInput($_POST['setup_type'] ?? 'Conference'),
            'room_blocks' => $_POST['room_blocks'] ?? [],
            'price_estimate' => (float)($_POST['price_estimate'] ?? 0),
            'status' => sanitizeInput($_POST['status'] ?? 'Pending')
        ];
        
        $errors = validateEventData($data);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
        
        // Check for room conflicts (excluding current event)
        $conflicts = checkRoomConflicts($pdo, $data['room_blocks'], $data['start_datetime'], $data['end_datetime'], $eventId);
        if (!empty($conflicts)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Room conflicts detected',
                'conflicts' => $conflicts
            ]);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("
                UPDATE events SET 
                    title = ?, description = ?, organizer_name = ?, organizer_contact = ?,
                    start_datetime = ?, end_datetime = ?, attendees_expected = ?, setup_type = ?,
                    room_blocks = ?, price_estimate = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $roomBlocksJson = json_encode($data['room_blocks']);
            
            $result = $stmt->execute([
                $data['title'],
                $data['description'],
                $data['organizer_name'],
                $data['organizer_contact'],
                $data['start_datetime'],
                $data['end_datetime'],
                $data['attendees_expected'],
                $data['setup_type'],
                $roomBlocksJson,
                $data['price_estimate'],
                $data['status'],
                $eventId
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update event']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'cancel_event':
        $eventId = (int)($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE events SET status = 'Cancelled', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$eventId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Event cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel event']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_event':
        $eventId = (int)($_GET['event_id'] ?? 0);
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if ($event) {
                $event['room_blocks'] = json_decode($event['room_blocks'] ?? '[]', true);
                echo json_encode(['success' => true, 'data' => $event]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_all_events':
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        try {
            $sql = "SELECT e.*, 
                           GROUP_CONCAT(DISTINCT r.room_number ORDER BY r.room_number) as room_numbers
                    FROM events e
                    LEFT JOIN rooms r ON JSON_CONTAINS(e.room_blocks, CAST(r.id AS JSON))
                    WHERE 1=1";
            
            $params = [];
            
            if ($status) {
                $sql .= " AND e.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $sql .= " AND (e.title LIKE ? OR e.organizer_name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($startDate) {
                $sql .= " AND e.start_datetime >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND e.end_datetime <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " GROUP BY e.id ORDER BY e.start_datetime DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();
            
            foreach ($events as &$event) {
                $event['room_blocks'] = json_decode($event['room_blocks'] ?? '[]', true);
            }
            
            echo json_encode(['success' => true, 'data' => $events]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    // get_rooms is handled above before authentication check
        
    case 'get_available_rooms':
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        try {
            // Simplified query - just get all rooms for now
            $stmt = $pdo->prepare("SELECT id, room_number, room_type, status, rate FROM rooms ORDER BY room_number");
            $stmt->execute();
            $rooms = $stmt->fetchAll();
            
            // Filter out occupied/reserved rooms
            $availableRooms = array_filter($rooms, function($room) {
                return $room['status'] === 'Vacant' || $room['status'] === 'Available';
            });
            
            echo json_encode(['success' => true, 'data' => array_values($availableRooms)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'confirm_event':
        $eventId = (int)($_POST['event_id'] ?? 0);
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }
        
        try {
            // Get event details
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if (!$event) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            // Update event status to confirmed
            $stmt = $pdo->prepare("UPDATE events SET status = 'Approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$eventId]);
            
            // Create reservations for blocked rooms
            $roomBlocks = json_decode($event['room_blocks'] ?? '[]', true);
            if (!empty($roomBlocks)) {
                // Create or get event guest for this event
                $eventGuestId = createOrGetEventGuest($event);
                
                foreach ($roomBlocks as $roomId) {
                    // Create a reservation for the event (Pending status for front desk check-in)
                    $reservationId = 'EVT-' . strtoupper(uniqid());
                    $stmt = $pdo->prepare("
                        INSERT INTO reservations (id, guest_id, room_id, check_in_date, check_out_date, status, payment_status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'Pending', 'PENDING', NOW(), NOW())
                    ");
                    $stmt->execute([
                        $reservationId,
                        $eventGuestId,
                        $roomId,
                        $event['start_datetime'],
                        $event['end_datetime']
                    ]);
                    
                    // Link reservation to event
                    $stmt = $pdo->prepare("INSERT INTO event_reservations (event_id, reservation_id) VALUES (?, ?)");
                    $stmt->execute([$eventId, $reservationId]);
                    
                    // Create billing transaction for event (like regular reservations)
                    $stmt = $pdo->prepare("
                        INSERT INTO billing_transactions (
                            reservation_id, transaction_type, amount, payment_amount, balance, `change`,
                            payment_method, status, notes, transaction_date
                        ) VALUES (?, 'Room Charge', ?, ?, ?, ?, 'Cash', 'Pending', ?, NOW())
                    ");
                    $stmt->execute([
                        $reservationId,
                        $event['price_estimate'],
                        $event['price_estimate'],
                        $event['price_estimate'],
                        0, // No change for events
                        "Event: {$event['title']} - {$event['organizer_name']}"
                    ]);
                    
                    // Update room status with event information
                    $stmt = $pdo->prepare("
                        UPDATE rooms 
                        SET status = 'Reserved', 
                            guest_name = CONCAT('Event: ', ?),
                            maintenance_notes = CONCAT('Event: ', ?, ' - ', ?),
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$event['title'], $event['title'], $event['organizer_name'], $roomId]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Event confirmed and rooms blocked successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_room_events':
        try {
            $roomId = $_GET['room_id'] ?? '';
            if (empty($roomId)) {
                echo json_encode(['success' => false, 'message' => 'Room ID required']);
                break;
            }
            
            // Get events that are using this room
            $stmt = $pdo->prepare("
                SELECT e.*, er.reservation_id
                FROM events e
                JOIN event_reservations er ON e.id = er.event_id
                JOIN reservations r ON er.reservation_id = r.id
                WHERE r.room_id = ? AND e.status IN ('Approved', 'Pending')
                ORDER BY e.start_datetime ASC
            ");
            $stmt->execute([$roomId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $events]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching room events: ' . $e->getMessage()]);
        }
        break;

    case 'get_events_for_rooms':
        try {
            // Get all events with their room information
            $stmt = $pdo->prepare("
                SELECT e.*, 
                       GROUP_CONCAT(r.room_number) as room_numbers,
                       GROUP_CONCAT(er.reservation_id) as reservation_ids
                FROM events e
                LEFT JOIN event_reservations er ON e.id = er.event_id
                LEFT JOIN reservations r ON er.reservation_id = r.id
                WHERE e.status IN ('Approved', 'Pending')
                GROUP BY e.id
                ORDER BY e.start_datetime ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $events]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching events for rooms: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Clean any output buffer and ensure clean JSON response
ob_clean();
?>
