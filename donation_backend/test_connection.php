<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Backend is reachable!",
    "timestamp" => date("Y-m-d H:i:s"),
    "php_version" => phpversion()
]);
?>
