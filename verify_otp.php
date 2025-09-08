<?php
session_start();
require 'db.php'; // Your DB connection

$message = '';
$step = 'verify';

// If no email in session, block access
if (empty($_SESSION['reset_email'])) {
    die("Session expired or invalid access. Please start over.");
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // OTP verification
    if (isset($_POST['verify_otp'])) {
        $enteredOtp = $_POST['otp'];

        // Check OTP and expiry
        $stmt = $conn->prepare("SELECT otp_expires_at FROM users WHERE email = ? AND otp = ?");
        $stmt->bind_param("ss", $email, $enteredOtp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $expires_at = $row['otp_expires_at'];
            if (strtotime($expires_at) >= time()) {
                $_SESSION['otp_verified'] = true;
                $message = "OTP verified. You may now reset your password.";
                $step = 'reset';
            } else {
                $message = "OTP expired. Please request a new one.";
            }
        } else {
            $message = "Invalid OTP. Please try again.";
        }
        $stmt->close();
    }

    // Password reset
    if (isset($_POST['reset_password'])) {
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            $message = "You must verify OTP before resetting password.";
        } else {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword !== $confirmPassword) {
                $message = "Passwords do not match.";
                $step = 'reset';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL, otp_expires_at = NULL WHERE email = ?");
                $stmt->bind_param("ss", $hashedPassword, $email);

                if ($stmt->execute()) {
                    $message = "Password reset successful. You can now <a href='login.php'>log in</a>.";
                    unset($_SESSION['otp_verified'], $_SESSION['reset_email']);
                    $step = 'done';
                } else {
                    $message = "Failed to update password. Please try again.";
                    $step = 'reset';
                }
                $stmt->close();
            }
        }
    }
} else {
    // If OTP already verified, show reset password form
    if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
        $step = 'reset';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Password</title>
  <style>
    body { background: #edf2f7; font-family: Arial, sans-serif; margin: 0; padding: 0; }
    .box { width: 360px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #f44336; }
    .msg { margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-left: 5px solid #f44336; font-size: 0.95em; }
    label { display: block; margin-top: 15px; font-size: 0.95em; }
    input[type="text"], input[type="password"] { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; }
    button { margin-top: 20px; padding: 12px; width: 100%; background: #f44336; color: #fff; border: none; font-size: 1em; border-radius: 5px; cursor: pointer; }
    button:hover { background: #d32f2f; }
    .show-password { display: flex; align-items: center; margin-top: 10px; font-size: 0.9em; color: #555; gap: 8px; }
    a { color: #d32f2f; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Reset Password</h2>

    <?php if (!empty($message)): ?>
      <div class="msg"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($step === 'verify'): ?>
      <form method="POST">
        <label for="otp">Enter OTP</label>
        <input type="text" name="otp" id="otp" required maxlength="6" pattern="\d{6}" title="6 digit OTP" />
        <button type="submit" name="verify_otp">Verify OTP</button>
      </form>

    <?php elseif ($step === 'reset'): ?>
      <form method="POST">
        <label for="new_password">New Password</label>
        <input type="password" name="new_password" id="new_password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <div class="show-password">
          <input type="checkbox" onclick="togglePassword()"> Show Passwords
        </div>

        <button type="submit" name="reset_password">Reset Password</button>
      </form>

    <?php elseif ($step === 'done'): ?>
      <p>Your password has been reset successfully. <a href="login.php">Click here to login</a>.</p>
    <?php endif; ?>
  </div>

  <script>
    function togglePassword() {
      const pwd = document.getElementById("new_password");
      const cpwd = document.getElementById("confirm_password");
      pwd.type = pwd.type === "password" ? "text" : "password";
      cpwd.type = cpwd.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>
