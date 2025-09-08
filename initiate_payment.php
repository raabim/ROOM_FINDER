<?php
session_start();
require 'db.php';
require 'config_khalti.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seeker') {
    die("Unauthorized");
}

$seeker_id  = intval($_SESSION['user_id']);
$booking_id = intval($_POST['booking_id'] ?? 0);

// Fetch booking
$sql = "SELECT b.booking_id, b.room_id, b.room_type, b.status, r.rent
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.booking_id = ? AND b.seeker_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $booking_id, $seeker_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bk || $bk['status'] !== 'Approved') {
    die("Booking not eligible for payment");
}

$amount_paisa = (2000 * 100);

$payload = [
  "return_url"          => RETURN_URL . "?booking_id=$booking_id",
  "website_url"         => WEBSITE_URL,
  "amount"              => $amount_paisa,
  "purchase_order_id"   => "BK-" . $booking_id,
  "purchase_order_name" => "Room " . $bk['room_id'],
  "customer_info"       => [
      "name"  => $_SESSION['email'] ?? "Seeker",
      "email" => $_SESSION['email'] ?? "test@example.com",
      "phone" => "9800000001"
  ]
];

// Debug logs
error_log("➡️ Endpoint: " . KHALTI_INITIATE_URL);
error_log("➡️ Authorization: " . KHALTI_SECRET_KEY);

$ch = curl_init(KHALTI_INITIATE_URL);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
      "Authorization: " . KHALTI_SECRET_KEY,
      "Content-Type: application/json"
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    die("cURL error: $curlErr");
}

$data = json_decode($response, true);

if ($httpCode === 200 && !empty($data['payment_url'])) {
    header("Location: " . $data['payment_url']);
    exit;
} else {
    echo "<pre>❌ Failed to initiate payment\n";
    echo "HTTP Code: $httpCode\n";
    print_r($data);
    echo "</pre>";
}
?>