<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
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

require_once "db.php";
require_once "auth_guard.php";
require_once "mail.php";

require_admin_auth($conn);

$data = json_decode(file_get_contents("php://input"), true);

$itemId = isset($data["item_id"]) ? (int)$data["item_id"] : 0;
$action = trim($data["action"] ?? "");

if ($itemId <= 0 || ($action !== "approve" && $action !== "decline")) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "item_id and valid action are required"
    ]);
    exit;
}

$newStatus = $action === "approve" ? "available" : "declined";

/*
 |------------------------------------------------------------
 | First get item + donor info before update
 |------------------------------------------------------------
*/
$lookup = mysqli_prepare($conn, "
    SELECT 
        i.item_id,
        i.title,
        i.status,
        i.donor_id,
        u.name AS donor_name,
        u.email AS donor_email
    FROM item i
    LEFT JOIN user u ON i.donor_id = u.user_id
    WHERE i.item_id = ?
    LIMIT 1
");

if (!$lookup) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare item lookup",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($lookup, "i", $itemId);
mysqli_stmt_execute($lookup);
$result = mysqli_stmt_get_result($lookup);
$item = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($lookup);

if (!$item) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Item not found"
    ]);
    exit;
}

if ($item["status"] !== "pending") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item already reviewed"
    ]);
    exit;
}

/*
 |------------------------------------------------------------
 | Update item status
 |------------------------------------------------------------
*/
$stmt = mysqli_prepare($conn, "UPDATE item SET status = ? WHERE item_id = ? AND status = 'pending'");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $newStatus, $itemId);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to update item status",
        "error" => mysqli_stmt_error($stmt)
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

if (mysqli_stmt_affected_rows($stmt) <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Item not found or already reviewed"
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

mysqli_stmt_close($stmt);

/*
 |------------------------------------------------------------
 | Send donor email after update
 |------------------------------------------------------------
*/
$emailSent = false;
$emailError = null;

$donorEmail = trim($item["donor_email"] ?? "");
$donorName = trim($item["donor_name"] ?? "Donor");
$itemTitle = trim($item["title"] ?? "");

if ($donorEmail !== "") {
    if ($action === "approve") {
        [$emailSent, $emailError] = sendDonationApprovedEmail(
            $donorEmail,
            $donorName,
            $itemId,
            $itemTitle
        );
    } else {
        [$emailSent, $emailError] = sendDonationDeclinedEmail(
            $donorEmail,
            $donorName,
            $itemId,
            $itemTitle
        );
    }
} else {
    $emailError = "Donor email not found";
}

echo json_encode([
    "success" => true,
    "message" => $action === "approve" ? "Item approved" : "Item declined",
    "status" => $newStatus,
    "email_sent" => $emailSent,
    "email_error" => $emailError
]);