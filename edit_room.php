<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.html?message=" . urlencode("Access denied. Please log in as an owner."));
    exit();
}

$owner_id = $_SESSION['user_id'];
$room_id = null;
$room = null;
$message = '';
$message_type = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $room_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :room_id AND owner_id = :owner_id");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
        $stmt->execute();
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            $message = "Room not found or you don't have permission to edit this room.";
            $message_type = "error";
        }

    } catch (PDOException $e) {
        error_log("Error fetching room for edit: " . $e->getMessage());
        $message = "Database error fetching room details.";
        $message_type = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? null;
    $type = htmlspecialchars($_POST['type'] ?? '');
    $floor = (int)($_POST['floor'] ?? 0);
    $rent = (float)($_POST['rent'] ?? 0.0);
    $capacity = (int)($_POST['capacity'] ?? 0);
    $location = htmlspecialchars($_POST['location'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');
    $status = htmlspecialchars($_POST['status'] ?? 'Available');
    $is_verified = (int)($_POST['is_verified'] ?? 0);

    if (!$room_id) {
        $message = "Room ID is missing for update.";
        $message_type = "error";
        // Re-fetch room to display form with existing data if ID was somehow lost
        // Or redirect to dashboard with error
        header("Location: owner_dashboard.php?message=" . urlencode($message) . "&type=error");
        exit();
    }

    $current_image_path_from_db = $room['image_path'] ?? null; // Get current image path from the fetched room

    $image_path_for_db = $current_image_path_from_db; // Default to current path
    $upload_base_dir = 'uploads/';
    $room_images_subdir = 'room_images/';
    $full_upload_dir = $upload_base_dir . $room_images_subdir;

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($full_upload_dir)) {
            mkdir($full_upload_dir, 0777, true);
        }

        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_file = $full_upload_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = array('jpg', 'png', 'jpeg', 'gif');

        if (!in_array($imageFileType, $allowed_types)) {
            $message = "Only JPG, JPEG, PNG & GIF allowed.";
            $message_type = "error";
        } elseif ($_FILES['image']['size'] > 5000000) {
            $message = "Image file is too large (max 5MB).";
            $message_type = "error";
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // CRITICAL CHANGE: Store only the relative path from 'uploads/'
                $image_path_for_db = $room_images_subdir . $image_name;

                // Delete old image file if it exists and a new one was uploaded
                if ($current_image_path_from_db && file_exists($upload_base_dir . $current_image_path_from_db)) {
                    unlink($upload_base_dir . $current_image_path_from_db);
                }
            } else {
                $message = "Error uploading new image.";
                $message_type = "error";
            }
        }
    }

    // Only proceed with DB update if no image upload error occurred
    if (empty($message)) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $stmt = $pdo->prepare("UPDATE rooms SET type = ?, floor = ?, rent = ?, capacity = ?, location = ?, description = ?, image_path = ?, status = ?, is_verified = ? WHERE id = ? AND owner_id = ?");
            $stmt->execute([$type, $floor, $rent, $capacity, $location, $description, $image_path_for_db, $status, $is_verified, $room_id, $owner_id]);

            $message = "Room updated successfully!";
            $message_type = "success";
            // Re-fetch room data to show updated info on the form
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :room_id AND owner_id = :owner_id");
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
            $stmt->execute();
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error updating room: " . $e->getMessage());
            $message = "Database error updating room: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 20px auto; background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="number"], input[type="file"], textarea, select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        input[type="file"] { padding: 5px; }
        textarea { resize: vertical; min-height: 80px; }
        .button-group { text-align: center; margin-top: 30px; }
        .button-group button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
            transition: background-color 0.3s ease;
        }
        .button-group button:hover { background-color: #45a049; }
        .cancel-btn { background-color: #f44336; }
        .cancel-btn:hover { background-color: #d32f2f; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .current-image { text-align: center; margin-bottom: 20px; }
        .current-image img { max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Room</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($room): ?>
            <form action="edit_room.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">

                <div class="form-group">
                    <label for="type">Room Type:</label>
                    <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($room['type']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($room['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity:</label>
                    <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="floor">Floor:</label>
                    <input type="number" id="floor" name="floor" value="<?php echo htmlspecialchars($room['floor']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="rent">Rent (Rs.):</label>
                    <input type="number" id="rent" name="rent" step="0.01" value="<?php echo htmlspecialchars($room['rent']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($room['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Current Image:</label>
                    <?php if (!empty($room['image_path'])): ?>
                        <div class="current-image">
                            <img src="uploads/<?php echo htmlspecialchars($room['image_path']); ?>" alt="Current Room Image">
                        </div>
                    <?php else: ?>
                        <p>No current image.</p>
                    <?php endif; ?>
                    <label for="image">Upload New Image (optional):</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="Available" <?php echo ($room['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Occupied" <?php echo ($room['status'] == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                        <option value="Maintenance" <?php echo ($room['status'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_verified">Verified:</label>
                    <select name="is_verified" id="is_verified" required>
                        <option value="1" <?php echo ($room['is_verified'] == 1) ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo ($room['is_verified'] == 0) ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit">Update Room</button>
                    <button type="button" class="cancel-btn" onclick="window.location.href='owner_dashboard.php'">Cancel</button>
                </div>
            </form>
        <?php elseif (empty($message)): ?>
            <p style="text-align: center;">Loading room details...</p>
        <?php endif; ?>
    </div>
</body>
</html>