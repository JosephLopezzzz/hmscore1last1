<?php
// Test script to check Events functionality
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h2>Events System Test</h2>";

try {
    $pdo = getPdo();
    if (!$pdo) {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
    echo "<p style='color: green;'>✅ Database connection successful</p>";

    // Check if events table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'events'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Events table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE events");
        $columns = $stmt->fetchAll();
        echo "<h3>Events table structure:</h3><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Count events
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
        $count = $stmt->fetch()['count'];
        echo "<p>Total events: {$count}</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Events table does not exist</p>";
        echo "<p>Please run the database migration: <code>database/07_events_table.sql</code></p>";
    }

    // Check if event_reservations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'event_reservations'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Event_reservations table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Event_reservations table does not exist</p>";
        echo "<p>Please run the database migration: <code>database/08_event_links_tables.sql</code></p>";
    }

    // Check rooms table
    $stmt = $pdo->query("SHOW TABLES LIKE 'rooms'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Rooms table exists</p>";
        
        // Count rooms
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM rooms");
        $count = $stmt->fetch()['count'];
        echo "<p>Total rooms: {$count}</p>";
        
        // Show sample rooms
        $stmt = $pdo->query("SELECT id, room_number, room_type, status FROM rooms LIMIT 5");
        $rooms = $stmt->fetchAll();
        echo "<h3>Sample rooms:</h3><ul>";
        foreach ($rooms as $room) {
            echo "<li>Room {$room['room_number']} - {$room['room_type']} ({$room['status']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Rooms table does not exist</p>";
    }

    // Test event_actions.php
    echo "<h3>Testing event_actions.php:</h3>";
    
    // Test get_rooms action
    $_GET['action'] = 'get_rooms';
    ob_start();
    include 'event_actions.php';
    $output = ob_get_clean();
    
    echo "<p>get_rooms response:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Test get_all_events action
    $_GET['action'] = 'get_all_events';
    ob_start();
    include 'event_actions.php';
    $output = ob_get_clean();
    
    echo "<p>get_all_events response:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
