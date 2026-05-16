 <?php
// Database configuration
$db_host = "127.0.0.1";
$db_username = "root";
$db_password = "";
$db_name = "halal_system";

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
