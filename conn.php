<?php
$servername = getenv('DB_HOST');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$dbport = getenv('DB_PORT'); // This should be the port number (e.g., 3306)

$conn = new mysqli($servername, $username, $password, $dbname, $dbport); // Removed $dburi from the parameters

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";
    header("Location: index.php");
?>
