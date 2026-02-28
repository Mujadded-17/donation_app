<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Only POST method is allowed"]);
    exit;
}

include "db.php";
include "auth_guard.php";

require_admin_auth($conn);

$data = json_decode(file_get_contents("php://input"), true);

$itemId = isset($data["item_id"]) ? (int) $data["item_id"] : 0;
$action = trim($data["action"] ?? "");

if ($itemId <= 0 || ($action !== "approve" && $action !== "decline")) {
    echo json_encode(["success" => false, "message" => "item_id and valid action are required"]);
    exit;
}

$newStatus = $action === "approve" ? "available" : "declined";

$stmt = mysqli_prepare($conn, "UPDATE item SET status = ? WHERE item_id = ? AND status = 'pending'");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Failed to prepare query", "error" => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $newStatus, $itemId);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Failed to update item status", "error" => mysqli_stmt_error($stmt)]);
    exit;
}

if (mysqli_stmt_affected_rows($stmt) <= 0) {
    echo json_encode(["success" => false, "message" => "Item not found or already reviewed"]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => $action === "approve" ? "Item approved" : "Item declined",
    "status" => $newStatus
]);
