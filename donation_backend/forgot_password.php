<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];

if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Vary: Origin");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/mail_reset.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$email = strtolower(trim($data["email"] ?? ""));

$generic = [
    "success" => true,
    "message" => "If the email exists, a reset link has been sent."
];

if ($email === "") {
    echo json_encode($generic);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, name, email FROM `user` WHERE email = ? LIMIT 1");
if (!$stmt) {
    error_log("forgot_password prepare failed: " . $conn->error);
    echo json_encode($generic);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode($generic);
    exit;
}

try {
    $token = bin2hex(random_bytes(32));
} catch (Throwable $e) {
    error_log("forgot_password random_bytes failed: " . $e->getMessage());
    echo json_encode($generic);
    exit;
}

$token_hash = hash("sha256", $token);
$expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));
$user_id = (int)$user["user_id"];

$stmtDel = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
if ($stmtDel) {
    $stmtDel->bind_param("i", $user_id);
    $stmtDel->execute();
    $stmtDel->close();
}

$stmtIns = $conn->prepare("
    INSERT INTO password_resets (user_id, token, expires_at)
    VALUES (?, ?, ?)
");

if (!$stmtIns) {
    error_log("forgot_password insert prepare failed: " . $conn->error);
    echo json_encode($generic);
    exit;
}

$stmtIns->bind_param("iss", $user_id, $token_hash, $expires_at);

if (!$stmtIns->execute()) {
    error_log("forgot_password insert execute failed: " . $stmtIns->error);
    $stmtIns->close();
    echo json_encode($generic);
    exit;
}
$stmtIns->close();

$resetLink = "http://localhost:5173/reset-password?token=" . urlencode($token);

[$ok, $err] = sendResetPasswordEmail($user["email"], $user["name"], $resetLink);

if (!$ok) {
    error_log("Reset email failed for {$user["email"]}: " . $err);
}

echo json_encode($generic);