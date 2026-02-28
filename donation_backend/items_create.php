<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST method is allowed"
    ]);
    exit;
}

include "db.php";

$userId = isset($_POST["user_id"]) ? (int) $_POST["user_id"] : 0;
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$pickupLocation = trim($_POST["pickup_location"] ?? "");
$status = trim($_POST["status"] ?? "Pending");
$deliveryAvailable = ($_POST["delivery_available"] ?? "0") === "1" ? 1 : 0;
$categoryId = isset($_POST["category_id"]) ? (int) $_POST["category_id"] : 1;

if ($userId <= 0 || $title === "" || $description === "" || $pickupLocation === "") {
    echo json_encode([
        "success" => false,
        "message" => "user_id, title, description, and pickup_location are required"
    ]);
    exit;
}

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "Valid image file is required"
    ]);
    exit;
}

$imageFile = $_FILES["image"];
$imageType = mime_content_type($imageFile["tmp_name"]);
if ($imageType === false || strpos($imageType, "image/") !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Uploaded file must be an image"
    ]);
    exit;
}

$uploadDir = __DIR__ . "/uploads/items/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($imageFile["name"], PATHINFO_EXTENSION);
$safeExtension = $extension ? strtolower($extension) : "jpg";
$fileName = "item_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $safeExtension;
$fullPath = $uploadDir . $fileName;
$relativePath = "uploads/items/" . $fileName;

if (!move_uploaded_file($imageFile["tmp_name"], $fullPath)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save uploaded image"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO item (title, description, images, status, delivery_available, pickup_location, donor_id, category_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ssssisii",
    $title,
    $description,
    $relativePath,
    $status,
    $deliveryAvailable,
    $pickupLocation,
    $userId,
    $categoryId
);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to create donation item",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Donation submitted successfully",
    "item_id" => mysqli_insert_id($conn)
]);
