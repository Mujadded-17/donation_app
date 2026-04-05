<?php
require_once __DIR__ . "/db.php";

$token = "0530b97dfabb9e5bbf08e2f982dae617fab1bcd4242a74fd238314c68f844c3";
$token_hash = hash("sha256", $token);

echo json_encode([
  "original_token" => $token,
  "hashed_token" => $token_hash,
  "query" => "SELECT * FROM password_resets WHERE token = ?",
], JSON_PRETTY_PRINT);

// Try to find it
$stmt = $conn->prepare("SELECT id, user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
if (!$stmt) {
  echo json_encode(["error" => "Prepare failed: " . $conn->error], JSON_PRETTY_PRINT);
  exit;
}

$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo json_encode([
  "found" => $row ? true : false,
  "record" => $row
], JSON_PRETTY_PRINT);
?>
