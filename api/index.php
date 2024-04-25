<?php

require "./lib.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // handle SigFox requests

  // finds the database id of the device
  $device_id = getDeviceId($_POST["device"]);

  // if it doesn't exist, create it
  if (!$device_id) {
    $device_id = createDevice($_POST["device"]);
  }

  $temp = $_POST["t"];
  $hum = $_POST["h"];
  // format the time for db insertion
  $time = gmdate("Y-m-d H:i:s", $_POST["time"]);
  $seq = $_POST["seq"];

  saveMeasure($temp, $hum, $time, $seq, $device_id);

  // get refresh rate of the device
  $refresh_rate = getConfig($_POST["device"])["refresh_secs"];

  // required by SigFox
  header("Content-Type: application/json");

  // encode the refresh rate in hexadecimal
  $short_hex_refresh_rate = dechex($refresh_rate);
  // pad the beggining of the hex string with 0's 
  // to keep a length of 16 character (8 bytes)
  // Required by SigFox
  $hex_refresh_rate = str_pad($short_hex_refresh_rate, 16, "0", STR_PAD_LEFT);

  // encode the string into json, with the structure required by SigFox
  echo json_encode([$_POST["device"] => ["downlinkData" => $hex_refresh_rate]]);
} else {
  global $data;
  // fetches measures from the db
  $data = getMeasures();

  // displays them in a php page 
  require "./list.view.php";
}
