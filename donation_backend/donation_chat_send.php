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
include "chat_bootstrap.php";

$authUser = require_auth($conn);
$userId = (int)$authUser["user_id"];

$data = json_decode(file_get_contents("php://input"), true);
$donationId = isset($data["donation_id"]) ? (int)$data["donation_id"] : 0;
$message = trim((string)($data["message"] ?? ""));

if ($donationId <= 0 || $message === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "donation_id and message are required"
    ]);
    exit;
}

if (mb_strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Message is too long"
    ]);
    exit;
}

if (!ensure_chat_table($conn)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to initialize chat storage",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

if (!ensure_chat_seen_table($conn)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to initialize chat seen storage",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

list($donation, $donationErr) = get_donation_for_user($conn, $donationId);
if ($donationErr) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to validate donation",
        "error" => $donationErr
    ]);
    exit;
}

if (!$donation) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Donation not found"
    ]);
    exit;
}

$donorId = (int)$donation["donor_id"];
$receiverId = (int)$donation["receiver_id"];
if ($userId !== $donorId && $userId !== $receiverId) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "You are not allowed to send message in this chat"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO donation_message (donation_id, sender_id, message)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare message insert",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "iis", $donationId, $userId, $message);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to send message",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

$messageId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

list($seenOk, $seenErr) = upsert_seen_message($conn, $donationId, $userId, (int)$messageId);
if (!$seenOk) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Message sent but failed to update seen state",
        "error" => $seenErr
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT dm.message_id, dm.donation_id, dm.sender_id, dm.message, dm.created_at, u.name AS sender_name, u.email AS sender_email
     FROM donation_message dm
     LEFT JOIN user u ON u.user_id = dm.sender_id
     WHERE dm.message_id = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Message sent but failed to load response",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $messageId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$messageRow = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "message" => "Message sent",
    "data" => $messageRow
]);
