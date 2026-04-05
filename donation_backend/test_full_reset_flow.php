<?php
require_once __DIR__ . "/db.php";

// First, request a password reset for a test user
$email = "karim@gmail.com";
$stmt = $conn->prepare("SELECT user_id, name FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  echo json_encode(["error" => "User not found"]);
  exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$token_hash = hash("sha256", $token);
$expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Delete old resets
$stmtDel = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmtDel->bind_param("i", $user["user_id"]);
$stmtDel->execute();
$stmtDel->close();

// Store new reset
$stmtIns = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmtIns->bind_param("iss", $user["user_id"], $token_hash, $expires_at);
$stmtIns->execute();
$stmtIns->close();

// Now test reset with original token
$new_password = "testpass456";
$token_hash_for_check = hash("sha256", $token);

$stmt_verify = $conn->prepare("SELECT id, user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
$stmt_verify->bind_param("s", $token_hash_for_check);
$stmt_verify->execute();
$row = $stmt_verify->get_result()->fetch_assoc();
$stmt_verify->close();

echo json_encode([
  "token_generated" => $token,
  "token_hash_stored" => $token_hash,
  "token_found" => $row ? true : false,
  "reset_record" => $row
], JSON_PRETTY_PRINT);
?>
