<?php
$servername = "localhost";
$username = "root";
$password = ""; // Empty password for default XAMPP MySQL setup
$dbname = "hotel";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>  