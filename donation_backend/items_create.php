<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "db.php";
require_once "config.php";

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

// ✅ read form fields
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$pickup_location = trim($_POST["pickup_location"] ?? "");
$donor_id = (int)($_POST["donor_id"] ?? ($_POST["user_id"] ?? 0));
$category_id = (int)($_POST["category_id"] ?? 0);
$delivery_available = isset($_POST["delivery_available"]) ? (int)$_POST["delivery_available"] : 0;

if ($title === "" || $description === "" || $pickup_location === "" || $donor_id <= 0 || $category_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "user_id, title, description, and pickup_location are required"
    ]);
    exit;
}

// ✅ handle upload (key name must be "image" in frontend FormData)
$imageName = null;

if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
    $tmp = $_FILES["image"]["tmp_name"];
    $original = basename($_FILES["image"]["name"]);

    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image type. Allowed: jpg, jpeg, png, webp"
        ]);
        exit;
    }

    // ✅ safe unique name
    $safeBase = preg_replace("/[^a-zA-Z0-9._-]/", "_", pathinfo($original, PATHINFO_FILENAME));
    $imageName = time() . "_" . $safeBase . "." . $ext;

    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $dest = $uploadDir . $imageName;

    if (!move_uploaded_file($tmp, $dest)) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to save image on server"
        ]);
        exit;
    }
    
    // ✅ Store relative path with /uploads/ prefix
    $imageName = "uploads/" . $imageName;
}

// ✅ Insert item (store relative path)
$stmt = $conn->prepare(" 
  INSERT INTO item
        (title, description, images, status, delivery_available, pickup_location, donor_id, category_id)
  VALUES
        (?, ?, ?, 'pending', ?, ?, ?, ?)
");

if (!$stmt) {
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
    $imageName,          // ✅ full relative path (uploads/filename)
    $delivery_available,
    $pickup_location,
    $donor_id,
    $category_id
);

if ($stmt->execute()) {

    // ✅ Donation / Item ID
    $item_id = $conn->insert_id;

    // ✅ Send thank-you email (do NOT block success if email fails)
    $emailSent = false;
    $emailError = null;

    try {
        $stmtU = $conn->prepare("SELECT name, email FROM `user` WHERE user_id=?");
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
        // Email failed but don't block donation success
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
    echo json_encode([
        "success" => false,
        "message" => "Insert failed",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();