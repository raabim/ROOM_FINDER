<?php

session_start(); 
require 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $role = htmlspecialchars(trim($_POST['role']));

    $message = ''; 

    if (empty($email) || empty($password) || empty($role)) {
        $message = "All fields are required.";
    } elseif (!$email) {
        $message = "Invalid email format.";
    } elseif (!in_array($role, ['owner', 'seeker'])) {
        $message = "Invalid role selected.";
    } else {
        
        $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Checking role
                if ($user['role'] === 'owner') {
                    header("Location: owner_dashboard.php");
                } else {
                    header("Location: seeker_dashboard.php");
                }
                exit(); 
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "No user found with that email and role combination.";
        }
        $stmt->close();
    }
    
    header("Location: login.html?message=" . urlencode($message));
    exit();
} else {
    
    header("Location: login.html");
    exit();
}
?>
