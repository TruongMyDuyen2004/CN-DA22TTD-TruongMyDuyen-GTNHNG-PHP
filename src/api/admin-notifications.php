<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Count pending orders
    $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'] ?? 0;
    
    // Count pending reservations
    $pending_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")->fetch()['count'] ?? 0;
    
    // Count unread contacts
    $unread_contacts = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'")->fetch()['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'pending_orders' => (int)$pending_orders,
        'pending_reservations' => (int)$pending_reservations,
        'unread_contacts' => (int)$unread_contacts,
        'total' => (int)($pending_orders + $pending_reservations + $unread_contacts)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
