<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in and has the 'seeker' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    $response['message'] = 'Authentication failed or insufficient permissions.';
    echo json_encode($response);
    exit();
}

// Validate the room_id from the POST request
if (!isset($_POST['room_id']) || !filter_var($_POST['room_id'], FILTER_VALIDATE_INT)) {
    $response['message'] = 'Invalid room ID.';
    echo json_encode($response);
    exit();
}

$seeker_id = $_SESSION['user_id'];
$room_id = intval($_POST['room_id']);

try {
    // Begin a database transaction for data integrity
    $conn->begin_transaction();

    // Check if the room exists and is currently 'available'
    $stmt = $conn->prepare("SELECT id, type, owner_id FROM rooms WHERE id = ? AND status = 'available'");

    if (!$stmt) {
        throw new Exception("Error preparing room selection statement: " . $conn->error);
    }

    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $stmt->close();

    if (!$room) {
        $response['message'] = 'Room not found or not available for booking.';
        echo json_encode($response);
        $conn->rollback();
        exit();
    }

    $owner_id = $room['owner_id'];
    $room_type = $room['type'];
    $booking_date = date('Y-m-d H:i:s');
    $status = 'Pending'; // Initial status is pending owner acceptance

    // Insert the new booking request
    $stmt = $conn->prepare("INSERT INTO bookings (room_id, seeker_id, owner_id, room_type, booking_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Error preparing booking insertion statement: " . $conn->error);
    }

    $stmt->bind_param("iissss", $room_id, $seeker_id, $owner_id, $room_type, $booking_date, $status);

    if (!$stmt->execute()) {
        throw new Exception("Error executing booking insertion statement: " . $stmt->error);
    }
    $stmt->close();

    // NOTE: The room's status is intentionally NOT updated here.
    // It remains 'available' in the `rooms` table so other seekers can
    // see it and potentially send a request. The owner will manage
    // the 'pending' booking in their dashboard.

    // If all queries were successful, commit the transaction
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Booking request submitted successfully! An owner will contact you soon.';

} catch (Exception $e) {
    // If any error occurs, rollback the transaction and report the error
    $conn->rollback();
    error_log("Booking error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again later.';
}

echo json_encode($response);
$conn->close();
?>
