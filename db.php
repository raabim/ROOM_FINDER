<?php
$host = 'localhost:3300';
$user = 'root';
$pass = '';
$db = 'raabim';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>