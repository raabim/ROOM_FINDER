<?php
// cancel_booking.php
// Allows a seeker to cancel a pending booking request.

session_start();
require 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

if (!isset($_POST['booking_id']) || !filter_var($_POST['booking_id'], FILTER_VALIDATE_INT)) {
    $response['message'] = "Invalid booking ID.";
    echo json_encode($response);
    exit();
}

$seeker_id = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id']);

try {
    // Start a transaction
    $conn->begin_transaction();

    // Fetch room ID associated with the booking
    $stmt = $conn->prepare("SELECT room_id, status FROM bookings WHERE booking_id = ? AND seeker_id = ?");
    $stmt->bind_param("ii", $booking_id, $seeker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception("Booking not found or you don't have permission to cancel it.");
    }
    
    $room_id = $booking['room_id'];
    
    // Update the booking status to 'cancelled'
    $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=? AND seeker_id=?");
    $stmt->bind_param("ii", $booking_id, $seeker_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update booking status.");
    }
    $stmt->close();

    // Set the room status back to 'available'
    $stmt = $conn->prepare("UPDATE rooms SET status='available' WHERE id=?");
    $stmt->bind_param("i", $room_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update room status.");
    }
    $stmt->close();
    
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Booking cancelled successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = "Failed to cancel booking: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>
