<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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
        d.donation_id,
        d.status AS donation_status,
        d.request_date,
        i.item_id,
        i.title,
        i.description,
        i.images,
        i.pickup_location,
        i.status AS item_status,
        u.name AS donor_name,
        c.name AS category_name
     FROM donation d
     INNER JOIN item i ON d.item_id = i.item_id
     LEFT JOIN user u ON i.donor_id = u.user_id
     LEFT JOIN category c ON i.category_id = c.category_id
     WHERE d.receiver_id = ?
     ORDER BY d.request_date DESC"
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

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch receiver dashboard data",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data,
    "count" => count($data)
]);
