<?php
session_start();
require 'db.php';
require 'config_stripe.php';
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'seeker') {
    die("Unauthorized");
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$seeker_id  = intval($_SESSION['user_id']);

$sql = "SELECT b.booking_id, b.room_id, b.room_type, b.status, r.rent
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.booking_id = ? AND b.seeker_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $booking_id, $seeker_id);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bk || $bk['status'] !== 'Approved') die("Booking not eligible for payment");

$amount_paisa = (int)round($bk['rent'] * 100); // Stripe expects cents

$session = \Stripe\Checkout\Session::create([
  'payment_method_types' => ['card'],
  'line_items' => [[
    'price_data' => [
      'currency' => 'npr',
      'product_data' => [
        'name' => 'Room ' . $bk['room_id'] . ' (' . $bk['room_type'] . ')',
      ],
      'unit_amount' => $amount_paisa,
    ],
    'quantity' => 1,
  ]],
  'mode' => 'payment',
  'success_url' => SITE_URL . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $booking_id,
  'cancel_url'  => SITE_URL . '/stripe_cancel.php?booking_id=' . $booking_id,
]);

header("Location: " . $session->url);
?>