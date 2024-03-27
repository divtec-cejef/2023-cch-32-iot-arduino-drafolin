<?php
require "./conf.php";

function getDeviceId(string $device_id): int|false
{
	global $conn;
	$sql = "SELECT count(*) FROM devices WHERE device_id = ?;";
	$qry = $conn->prepare($sql);
	$qry->execute([$device_id]);
	$res = $qry->get_result();
	$res = $res->fetch_assoc()["count(*)"];
	if ($res == 0) {
		return false;
	}

	$sql = "SELECT id FROM devices WHERE device_id = ?;";
	$qry = $conn->prepare($sql);
	$qry->execute([$device_id]);
	$res = $qry->get_result();
	$res = $res->fetch_assoc()["id"];
	return $res;
}

function createDevice(string $device_id): int
{
	global $conn;
	$sql = "INSERT INTO devices VALUES (null, ?);";
	$qry = $conn->prepare($sql);
	$qry->execute([$device_id]);

	return getDeviceId($device_id);
}

function saveMeasure(float $temp, float $hum, string $time, int $seq, int $device_id)
{
	global $conn;
	$sql = "INSERT INTO measures VALUES (null, ?, ?, ?, ?, ?);";
	$qry = $conn->prepare($sql);
	if (!$qry->execute([$temp, $hum, $time, $seq, $device_id])) {
		echo $qry->error;
		die (500);
	}
}

function getMeasures()
{
	global $conn;
	$sql = "SELECT * FROM measures m INNER JOIN devices d ON (m.device=d.id);";
	$qry = $conn->prepare($sql);
	$qry->execute();
	$res = $qry->get_result();
	$res = $res->fetch_all(MYSQLI_ASSOC);
	return $res;
}
