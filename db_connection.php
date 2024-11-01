<?php
$servername = "localhost"; // Change this if your DB server is different
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "libraryms"; // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
