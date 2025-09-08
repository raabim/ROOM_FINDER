<?php
session_start();
require 'db.php';
require 'config_khalti.php';

$pidx = $_GET['pidx'] ?? '';
$booking_id = $_GET['booking_id'] ?? 0;

if (!$pidx) die("Missing PIDX");

$ch = curl_init(KHALTI_LOOKUP_URL);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
      "Authorization: " . KHALTI_SECRET_KEY,
      "Content-Type: application/json"
  ],
  CURLOPT_POSTFIELDS => json_encode(["pidx" => $pidx]),
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    die("cURL error: $curlErr");
}

$data = json_decode($response, true);
$status = $data['status'] ?? 'Unknown';
$txn    = $data['transaction_id'] ?? null;

// Debug
error_log("🔍 Lookup response: " . print_r($data, true));

if ($status === 'Completed') {
    $stmt = $conn->prepare("UPDATE bookings SET status='Paid' WHERE booking_id=?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();
} 
header("Location: seeker_dashboard.php");
?>