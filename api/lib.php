<?php
require "./conf.php";

function hexTo32Float($strHex)
{
    $strHex = implode('', array_reverse(str_split($strHex, 2)));
    $hex = sscanf($strHex, "%02x%02x%02x%02x");
    $bin = implode('', array_map('chr', $hex));
    $array = unpack("Gnum", $bin);
    return $array['num'];
}

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
    $sql = "INSERT INTO devices VALUES (null, ?, 3600);";
    $qry = $conn->prepare($sql);
    $qry->execute([$device_id]);

    return getDeviceId($device_id);
}

function verifyIntegrity(int $seq, string $device_id)
{
    global $conn;
    $sql = "SELECT sequence_number, measure_time FROM measures m 
        WHERE measure_time IN (
            SELECT MAX(m.measure_time) as measure_time 
            FROM measures m
            WHERE m.device = ?
            AND m.sequence_number IS NOT NULL
        )
        ";
    $qry = $conn->prepare($sql);
    if (!$qry->execute([$device_id])) {
        echo $qry->error;
        die (500);
    }
    $res = $qry->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($res[0]["sequence_number"] + 1 == $seq)
        return;

    $limit = $seq - ($res[0]["sequence_number"] + 1);
    $since = date_create($res[0]["measure_time"]);
    if (!$since)
        die("invalid date");
    $timestamp = (date_timestamp_get($since) + 1) * 1000;


    $ch = curl_init("https://api.sigfox.com/v2/devices/18E231/messages?since=$timestamp&limit=$limit");
    $config = parse_ini_file("sigfox_config.ini");
    curl_setopt($ch, CURLOPT_USERPWD, $config["USERNAME"] . ":" . $config["PASSWORD"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $res = json_decode($res, true)["data"];

    usort($res, fn($a, $b) => $a["seqNumber"] - $b["seqNumber"]);

    foreach ($res as $entry) {
        $time = gmdate("Y-m-d H:i:s", $entry["time"] / 1000);
        $tempStr = substr($entry["data"], 0, 8);
        $humStr = substr($entry["data"], 8, 8);
        $temp = hexTo32Float($tempStr);
        $hum = hexTo32Float($humStr);
        $seq = $entry["seqNumber"];


        $sql = "INSERT INTO measures VALUES (null, ?, ?, ?, ?, ?);";
        $qry = $conn->prepare($sql);
        if (!$qry->execute([$temp, $hum, $time, $seq, $device_id])) {
            echo $qry->error;
            die (500);
        }

    }
}

function saveMeasure(float $temp, float $hum, string $time, int $seq, int $device_id)
{
    global $conn;

    $sql = "UPDATE measures 
        SET sequence_number = NULL
        WHERE sequence_number = ?
        AND device = ?";
    $qry = $conn->prepare($sql);
    if (!$qry->execute([$seq, $device_id])) {
        echo $qry->error;
        die (500);
    }

    verifyIntegrity($seq, $device_id);

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
    return $res->fetch_all(MYSQLI_ASSOC);
}

function getConfig(string $device_id): bool|array|null
{
    global $conn;
    $sql = "SELECT refresh_secs FROM devices WHERE device_id = ?;";
    $qry = $conn->prepare($sql);
    $qry->execute([$device_id]);
    $res = $qry->get_result();
    return $res->fetch_assoc();
}
