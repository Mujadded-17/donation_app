<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "success" => false,
        "message" => "Only GET method is allowed"
    ]);
    exit;
}

include "db.php";

$userId = isset($_GET["user_id"]) ? (int) $_GET["user_id"] : 0;
if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Valid user_id is required"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        item_id,
        title,
        description,
        images AS image_url,
        status,
        delivery_available,
        pickup_location,
        post_date AS created_at
     FROM item
     WHERE donor_id = ?
     ORDER BY post_date DESC"
);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
