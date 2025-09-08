<?php
session_start();
include 'db.php'; 


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm-password']);
    $role = $_POST['role'];

    
    if (empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }

    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit;
    }
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Email already exists. Please try another.";
        $stmt->close();
        exit;
    }
    $stmt->close();

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "Registration successful! <a href='login.html'>Login here</a>";
    } else {
        echo "Something went wrong. Please try again.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
