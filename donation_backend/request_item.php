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
include "mail.php";

$authUser = require_auth($conn);
$user_id = (int)$authUser["user_id"];

$json = file_get_contents("php://input");
$data = json_decode($json, true);

$item_id = isset($data["item_id"]) ? intval($data["item_id"]) : 0;

if ($item_id === 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item ID is required"
    ]);
    exit;
}

// Check if item exists and is available
$stmt = mysqli_prepare($conn, "
    SELECT 
        i.item_id,
        i.donor_id,
        i.status,
        i.title,
        u.name AS donor_name,
        u.email AS donor_email
    FROM item i
    LEFT JOIN user u ON i.donor_id = u.user_id
    WHERE i.item_id = ?
    LIMIT 1
");

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

if ($item["status"] !== "available") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item is no longer available"
    ]);
    exit;
}

if ((int)$item["donor_id"] === $user_id) {
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

$donor_id = (int)$item["donor_id"];
mysqli_stmt_bind_param($stmt, "iii", $item_id, $donor_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $donation_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Seed chat with an automatic first message from receiver to donor.
    if (!ensure_chat_table($conn)) {
        mysqli_query($conn, "DELETE FROM donation WHERE donation_id = " . (int)$donation_id);
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to initialize chat for this request",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    $receiverName = trim((string)($authUser["name"] ?? ""));
    if ($receiverName === "") {
        $receiverName = "A receiver";
    }

    $receiverEmail = trim((string)($authUser["email"] ?? ""));
    $itemTitle = trim((string)($item["title"] ?? "this item"));

    $autoMessage = "Hi! " . $receiverName . " requested \"" . $itemTitle . "\". Please coordinate pickup details here.";

    $chatStmt = mysqli_prepare(
        $conn,
        "INSERT INTO donation_message (donation_id, sender_id, message) VALUES (?, ?, ?)"
    );

    if (!$chatStmt) {
        mysqli_query($conn, "DELETE FROM donation WHERE donation_id = " . (int)$donation_id);
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Request created but chat initialization failed",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($chatStmt, "iis", $donation_id, $user_id, $autoMessage);
    if (!mysqli_stmt_execute($chatStmt)) {
        mysqli_stmt_close($chatStmt);
        mysqli_query($conn, "DELETE FROM donation WHERE donation_id = " . (int)$donation_id);
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Request created but first chat message failed",
            "error" => mysqli_error($conn)
        ]);
        exit;
    }
    mysqli_stmt_close($chatStmt);

    // In-app notification to donor
    $message = "You have a new request for your item";
    $type = "donation_request";
    $stmt = mysqli_prepare($conn, "INSERT INTO notification (user_id, type, message) VALUES (?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $donor_id, $type, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Email donor
    $emailSent = false;
    $emailError = null;

    $donorEmail = trim((string)($item["donor_email"] ?? ""));
    $donorName = trim((string)($item["donor_name"] ?? "Donor"));

    if ($donorEmail !== "") {
        [$emailSent, $emailError] = sendItemRequestToDonorEmail(
            $donorEmail,
            $donorName,
            $receiverName,
            $receiverEmail,
            $itemTitle,
            $donation_id
        );
    } else {
        $emailError = "Donor email not found";
    }

    echo json_encode([
        "success" => true,
        "message" => "Request sent successfully",
        "donation_id" => $donation_id,
        "email_sent" => $emailSent,
        "email_error" => $emailError
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