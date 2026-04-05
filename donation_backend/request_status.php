<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only GET method is allowed"
    ]);
    exit;
}

include "db.php";
include "auth_guard.php";

$authUser = require_auth($conn);
$userId = (int)$authUser["user_id"];

$itemId = isset($_GET["item_id"]) ? (int)$_GET["item_id"] : 0;
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Valid item_id is required"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT donation_id, status, request_date
     FROM donation
     WHERE item_id = ? AND receiver_id = ?
     ORDER BY request_date DESC
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare request status query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $itemId, $userId);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch request status",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "requested" => $row ? true : false,
    "donation_id" => $row["donation_id"] ?? null,
    "donation_status" => $row["status"] ?? null
]);
