<?php
session_start();
include 'db.php'; 

header('Content-Type: application/json'); 
try {
    
    $stmt = $conn->prepare("SELECT id, type, floor, rent, capacity, location, description, image_path, status FROM rooms");
   
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt->close();

    echo json_encode($rooms);

} catch (mysqli_sql_exception $e) {
    error_log("Database error in get_public_rooms.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.', 'details' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>