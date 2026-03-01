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
require_once "mail.php"; // ✅ ADD THIS

// ✅ read form fields
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$pickup_location = trim($_POST["pickup_location"] ?? "");
$donor_id = (int)($_POST["donor_id"] ?? 0);
$category_id = (int)($_POST["category_id"] ?? 0);
$delivery_available = isset($_POST["delivery_available"]) ? (int)$_POST["delivery_available"] : 0;

if ($title === "" || $donor_id <= 0 || $category_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields (title, donor_id, category_id)"
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
}

// ✅ Insert item (store ONLY filename)
$stmt = $conn->prepare("
  INSERT INTO item
    (title, description, images, status, delivery_available, pickup_location, donor_id, category_id)
  VALUES
    (?, ?, ?, 'available', ?, ?, ?, ?)
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
    $imageName,          // ✅ only filename saved
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

    $stmtU = $conn->prepare("SELECT name, email FROM `user` WHERE user_id=?");
    if ($stmtU) {
        $stmtU->bind_param("i", $donor_id);
        $stmtU->execute();
        $donor = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();

        if ($donor && !empty($donor["email"])) {
            [$ok, $err] = sendThankYouEmail($donor["email"], $donor["name"], $item_id);
            $emailSent = $ok;
            $emailError = $ok ? null : $err;
            // Optional: log failures
            // if (!$ok) error_log("Thank-you email failed: " . $err);
        }
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