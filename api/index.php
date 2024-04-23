<?php

require "./lib.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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

    $refresh_rate = getConfig($_POST["device"])["refresh_secs"];

    header("Content-Type: application/json");

    // Transforme la donnée de décimal à hexadécimal en gardant une taille de 16 caractères (8 bytes)
    $hex_refresh_rate = str_pad(dechex($refresh_rate), 16, "0", STR_PAD_LEFT);
    echo json_encode([$_POST["device"] => ["downlinkData" => $hex_refresh_rate]]);
} else if (str_contains($_SERVER["REQUEST_URI"], "rate")) {
    if (!array_key_exists("id", $_GET))
        die(402);

    $device_id = $_GET["id"];


} else {
    global $data;
    $data = getMeasures();

    require "./list.view.php";
}
