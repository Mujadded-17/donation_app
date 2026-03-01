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

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$token = trim($data["token"] ?? "");
$newPassword = (string)($data["new_password"] ?? "");

if ($token === "" || $newPassword === "") {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Token and new_password are required."]);
  exit;
}

// Basic password rules (adjust as you like)
if (strlen($newPassword) < 6) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Password must be at least 6 characters."]);
  exit;
}

$token_hash = hash("sha256", $token);

// Verify token exists and not expired
$stmt = $conn->prepare("
  SELECT id, user_id, expires_at
  FROM password_resets
  WHERE token_hash = ?
  LIMIT 1
");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
  exit;
}

if (strtotime($row["expires_at"]) < time()) {
  // Token expired → delete it
  $stmtDel = $conn->prepare("DELETE FROM password_resets WHERE id = ?");
  $stmtDel->bind_param("i", $row["id"]);
  $stmtDel->execute();
  $stmtDel->close();

  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
  exit;
}

// Hash the new password securely
$pass_hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update user password
$stmtUp = $conn->prepare("UPDATE `user` SET pass_hash = ? WHERE user_id = ?");
$stmtUp->bind_param("si", $pass_hash, $row["user_id"]);
$stmtUp->execute();
$stmtUp->close();

// Delete token after use
$stmtDel2 = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmtDel2->bind_param("i", $row["user_id"]);
$stmtDel2->execute();
$stmtDel2->close();

echo json_encode(["success" => true, "message" => "Password updated successfully. You can login now."]);