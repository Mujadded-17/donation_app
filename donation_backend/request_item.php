<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only POST method is allowed"
    ]);
    exit;
}

include "db.php";
include "auth_guard.php";

$authUser = require_auth($conn);
$user_id = (int)$authUser["user_id"];

$json = file_get_contents("php://input");
$data = json_decode($json, true);

$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;

if ($item_id === 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item ID is required"
    ]);
    exit;
}

// Check if item exists and is available
$stmt = mysqli_prepare($conn, "SELECT item_id, donor_id, status FROM item WHERE item_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare item query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$item) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Item not found"
    ]);
    exit;
}

if ($item['status'] !== 'available') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item is no longer available"
    ]);
    exit;
}

if ((int)$item['donor_id'] === $user_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "You cannot request your own item"
    ]);
    exit;
}

// Check if user already requested this item
$stmt = mysqli_prepare($conn, "SELECT donation_id FROM donation WHERE item_id = ? AND receiver_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare duplicate-check query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $item_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "You have already requested this item"
    ]);
    exit;
}
mysqli_stmt_close($stmt);

// Create donation request
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO donation (item_id, donor_id, receiver_id, status)
     VALUES (?, ?, ?, 'requested')"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare insert query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

$donor_id = (int)$item['donor_id'];
mysqli_stmt_bind_param($stmt, "iii", $item_id, $donor_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $donation_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $message = "You have a new request for your item";
    $type = "donation_request";
    $stmt = mysqli_prepare($conn, "INSERT INTO notification (user_id, type, message) VALUES (?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $donor_id, $type, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    echo json_encode([
        "success" => true,
        "message" => "Request sent successfully",
        "donation_id" => $donation_id
    ]);
} else {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to create request",
        "error" => mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>