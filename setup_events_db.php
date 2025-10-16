<?php
// Setup script for Events database tables
require_once __DIR__ . '/includes/db.php';

echo "<h2>Setting up Events Database Tables</h2>";

try {
    $pdo = getPdo();
    if (!$pdo) {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
        exit;
    }
    echo "<p style='color: green;'>✅ Database connection successful</p>";

    // Read and execute events table SQL
    $eventsSql = file_get_contents(__DIR__ . '/database/07_events_table.sql');
    if ($eventsSql) {
        $pdo->exec($eventsSql);
        echo "<p style='color: green;'>✅ Events table created/updated</p>";
    } else {
        echo "<p style='color: red;'>❌ Could not read events table SQL</p>";
    }

    // Read and execute event links table SQL
    $linksSql = file_get_contents(__DIR__ . '/database/08_event_links_tables.sql');
    if ($linksSql) {
        $pdo->exec($linksSql);
        echo "<p style='color: green;'>✅ Event links tables created/updated</p>";
    } else {
        echo "<p style='color: red;'>❌ Could not read event links table SQL</p>";
    }

    // Verify tables exist
    $tables = ['events', 'event_reservations', 'event_services'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
        }
    }

    echo "<p><strong>Setup complete! You can now test the Events functionality.</strong></p>";
    echo "<p><a href='test_events.php'>Run Events Test</a> | <a href='reservations.php'>Go to Reservations</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
