<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "db.php";
require_once "config.php";
require_once "auth_guard.php";

$authUser = require_auth($conn);
$donor_id = (int)$authUser["user_id"];

// Try to load mail module (optional - doesn't break donation if unavailable)
$mailReady = false;
$mailBootstrapError = null;

// Try simple mail first (no external dependencies)
$simplePath = __DIR__ . "/mail_simple.php";
if (file_exists($simplePath)) {
    try {
        require_once $simplePath;
        $mailReady = function_exists("sendThankYouEmail");
    } catch (Throwable $e) {
        $mailReady = false;
        $mailBootstrapError = $e->getMessage();
    }
}

// read form fields
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$pickup_location = trim($_POST["pickup_location"] ?? "");
$category_id = (int)($_POST["category_id"] ?? 0);
$delivery_available = isset($_POST["delivery_available"]) ? (int)$_POST["delivery_available"] : 0;

if ($title === "" || $description === "" || $pickup_location === "" || $category_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "title, description, pickup_location, and category_id are required"
    ]);
    exit;
}

// handle upload
$imageName = null;

if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
    $tmp = $_FILES["image"]["tmp_name"];
    $original = basename($_FILES["image"]["name"]);

    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid image type. Allowed: jpg, jpeg, png, webp"
        ]);
        exit;
    }

    $safeBase = preg_replace("/[^a-zA-Z0-9._-]/", "_", pathinfo($original, PATHINFO_FILENAME));
    $imageName = time() . "_" . $safeBase . "." . $ext;

    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $dest = $uploadDir . $imageName;

    if (!move_uploaded_file($tmp, $dest)) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to save image on server"
        ]);
        exit;
    }

    $imageName = "uploads/" . $imageName;
}

$stmt = $conn->prepare("
  INSERT INTO item
        (title, description, images, status, delivery_available, pickup_location, donor_id, category_id)
  VALUES
        (?, ?, ?, 'pending', ?, ?, ?, ?)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed",
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssissi",
    $title,
    $description,
    $imageName,
    $delivery_available,
    $pickup_location,
    $donor_id,
    $category_id
);

if ($stmt->execute()) {
    $item_id = $conn->insert_id;

    $emailSent = false;
    $emailError = null;

    try {
        $stmtU = $conn->prepare("SELECT name, email FROM `user` WHERE user_id = ?");
        if ($stmtU) {
            $stmtU->bind_param("i", $donor_id);
            $stmtU->execute();
            $donor = $stmtU->get_result()->fetch_assoc();
            $stmtU->close();

            if ($donor && !empty($donor["email"]) && $mailReady && function_exists("sendThankYouEmail")) {
                [$ok, $err] = sendThankYouEmail($donor["email"], $donor["name"], $item_id);
                $emailSent = $ok;
                $emailError = $ok ? null : $err;
            } elseif ($donor && !empty($donor["email"])) {
                $emailSent = false;
                $emailError = $mailBootstrapError ? ("mail disabled: " . $mailBootstrapError) : "mail not configured";
            }
        }
    } catch (Throwable $e) {
        $emailSent = false;
        $emailError = "Mail error: " . $e->getMessage();
    }

    echo json_encode([
        "success" => true,
        "message" => "Item posted successfully",
        "item_id" => $item_id,
        "image" => $imageName,
        "email_sent" => $emailSent,
        "email_error" => $emailError
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Insert failed",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();