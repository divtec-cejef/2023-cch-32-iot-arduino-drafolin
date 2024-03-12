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
echo "Connected successfully";

$sql = "INSERT INTO measures VALUES (null, ?, ?, ?);";
$qry = $conn->prepare($sql);
$res;
$qry->bind_param("dds", $_REQUEST["t"], $_REQUEST["h"], $_REQUEST["time"]);
if (!$qry->execute()) {
	echo $qry->error;
	die(500);
}
