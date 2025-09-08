<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.html?message=" . urlencode("Access denied. Please log in as an owner."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = $_SESSION['user_id'];

    $type = htmlspecialchars($_POST['type']);
    $floor = (int)$_POST['floor'];
    $rent = (float)$_POST['rent'];
    $capacity = (int)$_POST['capacity'];
    $location = htmlspecialchars($_POST['location']);
    $description = htmlspecialchars($_POST['description']);
    $status = htmlspecialchars($_POST['status']);
    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_base_dir = 'uploads/'; // Base upload directory
        $room_images_subdir = 'room_images/'; // Subdirectory for room images
        $full_upload_dir = $upload_base_dir . $room_images_subdir; // Combined server path

        // Create the directory if it doesn't exist
        if (!is_dir($full_upload_dir)) {
            mkdir($full_upload_dir, 0777, true);
        }

        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_file = $full_upload_dir . $image_name; // Full path where file will be saved
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = array('jpg', 'png', 'jpeg', 'gif');

        if (!in_array($imageFileType, $allowed_types)) {
            header("Location: owner_dashboard.php?message=" . urlencode("Only JPG, JPEG, PNG & GIF allowed.") . "&type=error");
            exit();
        }

        if ($_FILES['image']['size'] > 5000000) { // 5MB limit
            header("Location: owner_dashboard.php?message=" . urlencode("Image file is too large (max 5MB).") . "&type=error");
            exit();
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // CRITICAL CHANGE: Store only the relative path from 'uploads/'
            $image_path = $room_images_subdir . $image_name;
        } else {
            header("Location: owner_dashboard.php?message=" . urlencode("Error uploading image.") . "&type=error");
            exit();
        }
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $stmt = $pdo->prepare("INSERT INTO rooms (owner_id, type, floor, rent, capacity, location, description, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$owner_id, $type, $floor, $rent, $capacity, $location, $description, $image_path, $status]);

        header("Location: owner_dashboard.php?message=" . urlencode("Room added successfully!") . "&type=success");
        exit();
    } catch (PDOException $e) {
        error_log("Error adding room: " . $e->getMessage());
        header("Location: owner_dashboard.php?message=" . urlencode("Database error: " . $e->getMessage()) . "&type=error");
        exit();
    }
} else {
    header("Location: owner_dashboard.php");
    exit();
}
?>