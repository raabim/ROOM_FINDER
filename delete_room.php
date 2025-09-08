<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$owner_id = $_SESSION['user_id'];
$room_id = $_POST['id'] ?? null;

if (!$room_id) {
    echo json_encode(['success' => false, 'message' => 'Room ID is required.']);
    exit();
}


$stmt = $conn->prepare("SELECT image_path FROM rooms WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $room_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $image_path_from_db = $row['image_path'];

   
    $full_image_path = 'uploads/' . $image_path_from_db;
    
    if (!empty($image_path_from_db) && file_exists($full_image_path)) {
        unlink($full_image_path);
    }

    
    $delete_stmt = $conn->prepare("DELETE FROM rooms WHERE id = ? AND owner_id = ?");
    $delete_stmt->bind_param("ii", $room_id, $owner_id);

    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Room deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting room from database.']);
    }
    $delete_stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Room not found or you do not have permission to delete it.']);
}

$stmt->close();
$conn->close();
?>