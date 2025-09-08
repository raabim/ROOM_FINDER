<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$owner_id = $_SESSION['user_id'];

// Total rooms
$totalRooms = $conn->query("SELECT COUNT(*) as cnt FROM rooms WHERE owner_id = $owner_id")->fetch_assoc()['cnt'];

// Available rooms
$availableRooms = $conn->query("SELECT COUNT(*) as cnt FROM rooms WHERE owner_id = $owner_id AND status='Available'")->fetch_assoc()['cnt'];

// Pending bookings
$pendingBookings = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE owner_id = $owner_id AND status='Pending'")->fetch_assoc()['cnt'];

// Approved bookings
$approvedBookings = $conn->query("SELECT COUNT(*) as cnt FROM bookings WHERE owner_id = $owner_id AND status='Approved'")->fetch_assoc()['cnt'];

echo json_encode([
    'totalRooms' => $totalRooms,
    'availableRooms' => $availableRooms,
    'pendingBookings' => $pendingBookings,
    'approvedBookings' => $approvedBookings
]);
?>
