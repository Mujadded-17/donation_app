<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

include "db.php";

// Get the logged-in user's ID (if provided)
$currentUserId = isset($_GET["user_id"]) ? (int) $_GET["user_id"] : 0;
$categoryId = isset($_GET["category_id"]) ? (int) $_GET["category_id"] : 0;

// Build the query
if ($categoryId > 0) {
    // Fetch items for specific category, excluding current user's posts
    if ($currentUserId > 0) {
        $sql = "SELECT i.*, c.name as category_name, u.name as donor_name
                FROM item i
                LEFT JOIN category c ON i.category_id = c.category_id
                LEFT JOIN user u ON i.donor_id = u.user_id
                WHERE i.category_id = ? AND i.donor_id != ? AND i.status = 'available'
                ORDER BY i.post_date DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $categoryId, $currentUserId);
    } else {
        $sql = "SELECT i.*, c.name as category_name, u.name as donor_name
                FROM item i
                LEFT JOIN category c ON i.category_id = c.category_id
                LEFT JOIN user u ON i.donor_id = u.user_id
                WHERE i.category_id = ? AND i.status = 'available'
                ORDER BY i.post_date DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $categoryId);
    }
} else {
    // Fetch all items, excluding current user's posts
    if ($currentUserId > 0) {
        $sql = "SELECT i.*, c.name as category_name, u.name as donor_name
                FROM item i
                LEFT JOIN category c ON i.category_id = c.category_id
                LEFT JOIN user u ON i.donor_id = u.user_id
                WHERE i.donor_id != ? AND i.status = 'available'
                ORDER BY i.post_date DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $currentUserId);
    } else {
        $sql = "SELECT i.*, c.name as category_name, u.name as donor_name
                FROM item i
                LEFT JOIN category c ON i.category_id = c.category_id
                LEFT JOIN user u ON i.donor_id = u.user_id
                WHERE i.status = 'available'
                ORDER BY i.post_date DESC";
        $stmt = mysqli_prepare($conn, $sql);
    }
}

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_execute($stmt);
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
?>
