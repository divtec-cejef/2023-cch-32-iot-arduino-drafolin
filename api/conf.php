<?php
global $conn;
$config = parse_ini_file("db_config.ini");
$host = $config['HOST'];
$db = $config['DB'];
$uname = $config['USERNAME'];
$pass = $config['PASSWORD'];

// Create connection
$conn = new mysqli($host, $uname, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die ("Connection failed: " . $conn->connect_error);
}

if (!$_SERVER["REQUEST_METHOD"] == "GET")
    echo "<script>console.log('Connected successfully')</script>";
