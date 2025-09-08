<?php
// get_my_bookings.php
// Fetches all booking requests made by the current seeker.

session_start();
require 'db.php';

header('Content-Type: application/json');

$response = [];

// Check for authentication and user role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$seeker_id = $_SESSION['user_id'];

$sql = "SELECT b.booking_id, b.room_id, b.room_type, b.booking_date, b.status, u.email as owner_email
        FROM bookings b
        JOIN users u ON b.owner_id = u.id
        WHERE b.seeker_id=?
        ORDER BY b.booking_date DESC"; // Order by most recent booking first
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seeker_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        "id" => $row['booking_id'],
    "room_id" => $row['room_id'],
    "room_type" => $row['room_type'],
    "owner_email" => $row['owner_email'],
    "booking_date" => $row['booking_date'],  
    "status" => $row['status']
    ];
}

echo json_encode($bookings);
$stmt->close();
$conn->close();
?>
