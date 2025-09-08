<?php
session_start();
require 'PHPmailler/src/PHPMailer.php';
require 'PHPmailler/src/SMTP.php';
require 'PHPmailler/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php'; // Database connection

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo "Invalid email format.";
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Email not found in our system.";
    exit;
}
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

$otp = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', time() + 600);


$stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expires_at = ? WHERE id = ?");
$stmt->bind_param("ssi", $otp, $expires_at, $user_id);
$stmt->execute();
$stmt->close();


$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'myproject653@gmail.com';
    $mail->Password = 'yikq zovh aexz bado';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('myproject653@gmail.com', 'Room Finder');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Password Reset';
    $mail->Body = "Your OTP for resetting your password is: <b>$otp</b><br>It will expire in 10 minutes.";

    $mail->send();

    
    $_SESSION['reset_email'] = $email; 

    header("Location: verify_otp.php"); 
    exit;
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}