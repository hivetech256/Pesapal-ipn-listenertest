<?php
$servername = getenv('DB_HOST');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$dburi = getenv('DB_URI');
$dbport = getenv('DB_PORT');

$conn = new mysqli($servername, $username, $password, $dbname, $dburi, $dbport);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>
