<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
	die(405);
}

$config = parse_ini_file("db_config.ini");
$host = $config['HOST'];
$db = $config['DB'];
$uname = $config['USERNAME'];
$pass = $config['PASSWORD'];

// Create connection
$conn = new mysqli($host, $uname, $pass, $db);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully\n";

$sql = "INSERT INTO measures VALUES (null, ?, ?, ?);";
$qry = $conn->prepare($sql);
if (!$qry->execute([$_POST["t"], $_POST["h"],  gmdate("Y-m-d H:i:s", $_POST["time"])])) {
	echo $qry->error;
	die(500);
}
echo "Saved successfully";
