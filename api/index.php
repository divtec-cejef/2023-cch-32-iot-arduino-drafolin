<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
	die(405);
}

require "./lib.php";

$device_id = $_POST["device"];

$device_id = getDeviceId($device_id);
if (!$device_id) {
	$device_id = createDevice($_POST["device"]);
}

$temp = $_POST["t"];
$hum = $_POST["h"];
$time = gmdate("Y-m-d H:i:s", $_POST["time"]);
$seq = $_POST["seq"];

saveMeasure($temp, $hum, $time, $seq, $device_id);
echo "Saved successfully";
