<?php
/**
 * Automated Integration Test Suite
 * Run this in your browser: http://localhost/Wep_Programming_Pro/EthioEventHub/tests/INTEGRATION_TEST.php
 */

require_once __DIR__ . '/../includes/db.php';

echo "<h1>🛠️ EthioEvent Hub - Integration Test Suite</h1>";
echo "<hr>";

function test($name, $callback) {
    echo "Testing $name... ";
    try {
        if ($callback()) {
            echo "<span style='color:green'>[PASS]</span><br>";
        } else {
            echo "<span style='color:red'>[FAIL]</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>[ERROR]</span>: " . $e->getMessage() . "<br>";
    }
}

// 1. Test Database Connection
test("Database Connectivity", function() {
    $pdo = getDB();
    return $pdo instanceof PDO;
});

// 2. Test Tables Existence
test("Required Tables Presence", function() {
    $pdo = getDB();
    $requiredTables = ['users', 'events', 'categories', 'bookings', 'wishlist', 'reviews'];
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            throw new Exception("Missing table: $table");
        }
    }
    return true;
});

// 3. Test Sample Data
test("Sample Categories Seeded", function() {
    $pdo = getDB();
    $count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    return $count >= 4;
});

echo "<hr>";
echo "<h3>Test Suite Complete.</h3>";
?>
