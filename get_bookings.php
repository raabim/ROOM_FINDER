<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode([]);
    exit();
}

$owner_id = $_SESSION['user_id'];

$sql = "SELECT b.booking_id AS id, b.room_id, b.room_type, b.status, b.booking_date,
               u.email AS seeker_email
        FROM bookings b
        JOIN users u ON b.seeker_id = u.id
        WHERE b.owner_id = ?
        ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode($bookings);
$stmt->close();
$conn->close();
?>
