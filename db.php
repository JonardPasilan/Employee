<?php
require_once __DIR__ . '/auth.php';
$host = 'localhost';      // Or [IP_ADDRESS]
$username = 'root';
$password = '';           // Change to your actual password
$database = 'employee';

// 1. Initialize the connection object
$conn = new mysqli($host, $username, $password, $database);

// 2. Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
