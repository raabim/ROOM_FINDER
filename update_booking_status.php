<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$owner_id   = intval($_SESSION['user_id']);
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$status     = $_POST['status'] ?? '';

$allowed = ['Pending','Approved','Cancelled'];
if (!in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// First, update the booking status
$sql = "UPDATE bookings SET status = ? WHERE booking_id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sii', $status, $booking_id, $owner_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    
    // Get the room_id for this booking
    $roomSql = "SELECT room_id FROM bookings WHERE booking_id = ? AND owner_id = ?";
    $roomStmt = $conn->prepare($roomSql);
    $roomStmt->bind_param('ii', $booking_id, $owner_id);
    $roomStmt->execute();
    $roomResult = $roomStmt->get_result();
    
    if ($roomRow = $roomResult->fetch_assoc()) {
        $room_id = $roomRow['room_id'];
        
        // If booking is approved → set room to Reserved
        if ($status === 'Approved') {
            $updateRoom = $conn->prepare("UPDATE rooms SET status = 'Reserved' WHERE id = ?");
            $updateRoom->bind_param('i', $room_id);
            $updateRoom->execute();
            $updateRoom->close();
        }
        
        // If booking is cancelled → set room back to Available
        if ($status === 'Cancelled') {
            $updateRoom = $conn->prepare("UPDATE rooms SET status = 'Available' WHERE id = ?");
            $updateRoom->bind_param('i', $room_id);
            $updateRoom->execute();
            $updateRoom->close();
        }
    }
    $roomStmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Booking status and room updated.']);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking status.']);
}

$stmt->close();
$conn->close();
?>
