<?php
require_once 'database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/add_language_columns.sql');
    
    // Execute SQL
    $conn->exec($sql);
    
    echo "✅ Language columns added successfully!\n";
    echo "Database has been updated with English language support.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
