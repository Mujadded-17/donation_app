<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

include "db.php";
include "auth_guard.php";

require_admin_auth($conn);

$categoryId = isset($_GET["category_id"]) ? (int) $_GET["category_id"] : 0;

if ($categoryId > 0) {
    $sql = "SELECT i.*, c.name as category_name, u.name as donor_name, u.email as donor_email
            FROM item i
            LEFT JOIN category c ON i.category_id = c.category_id
            LEFT JOIN user u ON i.donor_id = u.user_id
            WHERE i.status = 'pending' AND i.category_id = ?
            ORDER BY i.post_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare query", "error" => mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "i", $categoryId);
} else {
    $sql = "SELECT i.*, c.name as category_name, u.name as donor_name, u.email as donor_email
            FROM item i
            LEFT JOIN category c ON i.category_id = c.category_id
            LEFT JOIN user u ON i.donor_id = u.user_id
            WHERE i.status = 'pending'
            ORDER BY i.post_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Failed to prepare query", "error" => mysqli_error($conn)]);
        exit;
    }
}

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => false, "message" => "Failed to fetch pending items", "error" => mysqli_stmt_error($stmt)]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $items,
    "count" => count($items)
]);
