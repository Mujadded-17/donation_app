<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

include "db.php";

$sql = "SELECT * FROM item ORDER BY post_date DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch items",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $items
]);
?>