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
include "chat_bootstrap.php";

$authUser = require_auth($conn);
$userId = (int)$authUser["user_id"];

$donationId = isset($_GET["donation_id"]) ? (int)$_GET["donation_id"] : 0;
if ($donationId <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Valid donation_id is required"
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
        "message" => "Failed to load donation",
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
        "message" => "You are not allowed to view this chat"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        dm.message_id,
        dm.donation_id,
        dm.sender_id,
        dm.message,
        dm.created_at,
        u.name AS sender_name,
        u.email AS sender_email
     FROM donation_message dm
     LEFT JOIN user u ON u.user_id = dm.sender_id
     WHERE dm.donation_id = ?
     ORDER BY dm.message_id ASC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare messages query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $donationId);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch chat messages",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$messages = [];
$maxMessageId = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $row["seen_by_peer"] = false;
    $row["seen_at"] = null;
    $messages[] = $row;

    $mid = (int)$row["message_id"];
    if ($mid > $maxMessageId) {
        $maxMessageId = $mid;
    }
}
mysqli_stmt_close($stmt);

if ($maxMessageId > 0) {
    list($seenOk, $seenErr) = upsert_seen_message($conn, $donationId, $userId, $maxMessageId);
    if (!$seenOk) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update seen state",
            "error" => $seenErr
        ]);
        exit;
    }
}

$peerUserId = $userId === $donorId ? $receiverId : $donorId;
list($peerSeenState, $peerSeenErr) = get_seen_state($conn, $donationId, $peerUserId);
if ($peerSeenErr) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to load seen state",
        "error" => $peerSeenErr
    ]);
    exit;
}

$peerLastSeenMessageId = (int)($peerSeenState["last_seen_message_id"] ?? 0);
$peerSeenAt = $peerSeenState["seen_at"] ?? null;

foreach ($messages as &$row) {
    $rowMid = (int)$row["message_id"];
    $rowSender = (int)$row["sender_id"];
    if ($rowSender === $userId && $rowMid <= $peerLastSeenMessageId) {
        $row["seen_by_peer"] = true;
        $row["seen_at"] = $peerSeenAt;
    }
}
unset($row);

echo json_encode([
    "success" => true,
    "donation" => $donation,
    "messages" => $messages,
    "current_user_id" => $userId,
    "peer_last_seen_message_id" => $peerLastSeenMessageId
]);
