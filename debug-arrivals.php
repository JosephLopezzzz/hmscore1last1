<?php
require_once __DIR__ . '/includes/db.php';

echo "<h1>Debug: fetchArrivals() Results</h1>";
echo "<pre>";

$arrivals = fetchArrivals();
echo "Number of arrivals found: " . count($arrivals) . "\n";
echo "Raw data:\n";
print_r($arrivals);

echo "\n\nFormatted display:\n";
if (empty($arrivals)) {
    echo "No arrivals found for today.\n";
} else {
    foreach ($arrivals as $arrival) {
        echo "ID: {$arrival['id']}\n";
        echo "Name: {$arrival['name']}\n";
        echo "Room: {$arrival['room']}\n";
        echo "Time: {$arrival['time']}\n";
        echo "Status: {$arrival['status']}\n";
        echo "---\n";
    }
}

echo "</pre>";
?>
