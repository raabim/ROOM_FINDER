
<?php
session_start();
require 'db.php'; 

header('Content-Type: application/json'); // Set header for JSON response


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$owner_id = $_SESSION['user_id'];

try {
    
    $stmt = $conn->prepare("SELECT id, type, floor, rent, capacity, location, description, image_path, status FROM rooms WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt->close();

    echo json_encode($rooms);

} catch (mysqli_sql_exception $e) {
    error_log("Database error in getrooms.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
} finally {
    $conn->close();
}
?>
