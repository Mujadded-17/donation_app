<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = mysqli_connect("localhost", "root", "", "donation_app");

if (!$conn) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}
?>