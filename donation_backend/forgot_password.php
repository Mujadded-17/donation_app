<?php
// CORS (DEV)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed)) header("Access-Control-Allow-Origin: $origin");
header("Vary: Origin");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit; }

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/mail_reset.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$email = strtolower(trim($data["email"] ?? ""));

// Always return a generic message (prevents email enumeration)
$generic = ["success" => true, "message" => "If the email exists, a reset link has been sent."];

if ($email === "") {
  echo json_encode($generic);
  exit;
}

// Find user
$stmt = $conn->prepare("SELECT user_id, name, email FROM `user` WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  echo json_encode($generic);
  exit;
}

// Create token + hash
$token = bin2hex(random_bytes(32)); // 64 chars
$token_hash = hash("sha256", $token);
$expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Optional: delete old reset tokens for this user
$stmtDel = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmtDel->bind_param("i", $user["user_id"]);
$stmtDel->execute();
$stmtDel->close();

// Save new reset request
$stmtIns = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
$stmtIns->bind_param("iss", $user["user_id"], $token_hash, $expires_at);
$stmtIns->execute();
$stmtIns->close();

// Build reset link (frontend page)
$resetLink = "http://localhost:5173/reset-password?token=" . urlencode($token);

// Send email
[$ok, $err] = sendResetPasswordEmail($user["email"], $user["name"], $resetLink);
// Do not reveal if email failed
// if (!$ok) error_log("Reset email failed: " . $err);

echo json_encode($generic);