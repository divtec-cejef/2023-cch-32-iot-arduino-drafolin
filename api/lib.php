<?php
require "./conf.php";

function hexTo32Float($strHex)
{
  //reverses the string byte by byte (character pairs)
  // allows for conversion between LE and BE
  $strHex = implode('', array_reverse(str_split($strHex, 2)));
  // splits the string in byte array, filling single-digit bytes with 0
  $hex = sscanf($strHex, "%02x%02x%02x%02x");
  // create binary string from the array
  $bin = implode('', array_map('chr', $hex));
  // transforms the binary into 32-bit float number
  $array = unpack("Gnum", $bin);
  return $array['num'];
}

function getDeviceId(string $device_id): int|false
{
  // grabs the database connection
  global $conn;

  // make and execute the sql query to count all devices with the 
  // specified id
  $sql = "SELECT count(*) FROM devices WHERE device_id = ?;";
  $qry = $conn->prepare($sql);
  $qry->execute([$device_id]);

  // checks if the record exists in the db
  $res = $qry->get_result();
  $res = $res->fetch_assoc()["count(*)"];

  // return if it doesnt
  if ($res == 0) {
    return false;
  }

  // make and execute the query to get the actual device id
  $sql = "SELECT id FROM devices WHERE device_id = ?;";
  $qry = $conn->prepare($sql);
  $qry->execute([$device_id]);
  $res = $qry->get_result();
  $res = $res->fetch_assoc()["id"];

  // returns it
  return $res;
}

function createDevice(string $device_id): int
{
  // grabs the db connection
  global $conn;

  // make and execute the query to create a device
  $sql = "INSERT INTO devices VALUES (null, ?, 3600);";
  $qry = $conn->prepare($sql);
  $qry->execute([$device_id]);

  // return the created device id
  return getDeviceId($device_id);
}

function verifyIntegrity(int $seq, string $device_id)
{
  // grabs the db connection
  global $conn;

  // make and execute the query to grab the last inserted sequence number
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
    die(500);
  }
  $res = $qry->get_result()->fetch_all(MYSQLI_ASSOC);

  // checks if the last inserted sequence number 
  // is exactly one lower than the current one
  if ($res[0]["sequence_number"] + 1 == $seq)
    return;

  // calculates the difference with the last sequence number
  $limit = $seq - ($res[0]["sequence_number"] + 1);

  // grabs the timestamp from the newest record
  $since = date_create($res[0]["measure_time"]);

  // verifies the date is valid
  if (!$since)
    die("invalid date");

  // add one second not to include the record, then transform to milliseconds
  $timestamp = (date_timestamp_get($since) + 1) * 1000;

  // sends the query
  $ch = curl_init("https://api.sigfox.com/v2/devices/18E231/messages?since=$timestamp&limit=$limit");
  $config = parse_ini_file("sigfox_config.ini");
  curl_setopt($ch, CURLOPT_USERPWD, $config["USERNAME"] . ":" . $config["PASSWORD"]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $res = curl_exec($ch);
  $res = json_decode($res, true)["data"];

  // sorts the array according to the sequence number
  usort($res, fn ($a, $b) => $a["seqNumber"] - $b["seqNumber"]);

  foreach ($res as $entry) {
    // formats the date
    $time = gmdate("Y-m-d H:i:s", $entry["time"] / 1000);

    // converts the first half of the data string to floating point number
    $tempStr = substr($entry["data"], 0, 8);
    $temp = hexTo32Float($tempStr);

    // converts the second half of the data string to floating point number
    $humStr = substr($entry["data"], 8, 8);
    $hum = hexTo32Float($humStr);

    $seq = $entry["seqNumber"];

    // makes and execute the query to insert the record into the db
    $sql = "INSERT INTO measures VALUES (null, ?, ?, ?, ?, ?);";
    $qry = $conn->prepare($sql);
    if (!$qry->execute([$temp, $hum, $time, $seq, $device_id])) {
      echo $qry->error;
      die(500);
    }
  }
}

function saveMeasure(float $temp, float $hum, string $time, int $seq, int $device_id)
{
  // grabs the db connection
  global $conn;

  // makes and executes the query to set equal sequence numbers to null
  $sql = "UPDATE measures 
        SET sequence_number = NULL
        WHERE sequence_number = ?
        AND device = ?";
  $qry = $conn->prepare($sql);
  if (!$qry->execute([$seq, $device_id])) {
    echo $qry->error;
    die(500);
  }

  // verifies the integrity of the db records
  verifyIntegrity($seq, $device_id);

  // makes and executes the query to insert the record into the db
  $sql = "INSERT INTO measures VALUES (null, ?, ?, ?, ?, ?);";
  $qry = $conn->prepare($sql);
  if (!$qry->execute([$temp, $hum, $time, $seq, $device_id])) {
    echo $qry->error;
    die(500);
  }
}

function getMeasures()
{
  // grabs the db connection
  global $conn;

  // makes and executes the query to get the measures and corresponding device ids
  $sql = "SELECT * FROM measures m INNER JOIN devices d ON (m.device=d.id);";
  $qry = $conn->prepare($sql);
  $qry->execute();
  $res = $qry->get_result();
  return $res->fetch_all(MYSQLI_ASSOC);
}

function getConfig(string $device_id): bool|array|null
{
  // grabs the db connection
  global $conn;

  // makes and executes the query to get the configs for the specified device
  $sql = "SELECT refresh_secs FROM devices WHERE device_id = ?;";
  $qry = $conn->prepare($sql);
  $qry->execute([$device_id]);
  $res = $qry->get_result();
  return $res->fetch_assoc();
}
