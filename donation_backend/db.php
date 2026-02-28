<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect("localhost", "root", "", "donation_app");

if (!$conn) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}
?>