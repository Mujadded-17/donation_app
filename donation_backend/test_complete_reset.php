<?php
require_once __DIR__ . "/db.php";

// Clear old test records
$conn->query("DELETE FROM password_resets WHERE user_id = 2");

// Create a fresh reset token
$user_id = 2;
$original_token = bin2hex(random_bytes(32));  // This is what gets sent in the email
$token_hash = hash("sha256", $original_token);  // This is what gets stored in DB
$expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

echo "=== TOKEN GENERATION ===\n";
echo "Original token (for email): $original_token\n";
echo "Hash for storage: $token_hash\n\n";

// Store in DB
$stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $token_hash, $expires_at);
$stmt->execute();
$stmt->close();

echo "=== STORED IN DB ===\n";
$result = $conn->query("SELECT * FROM password_resets WHERE user_id = 2");
$row = $result->fetch_assoc();
echo json_encode($row, JSON_PRETTY_PRINT) . "\n\n";

echo "=== PASSWORD RESET TEST ===\n";
echo "Testing reset with original token...\n";
$new_password = "newpassword123";
$reset_token_hash = hash("sha256", $original_token);

// This is what reset_password.php does:
$stmt_verify = $conn->prepare("SELECT id, user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
$stmt_verify->bind_param("s", $reset_token_hash);
$stmt_verify->execute();
$verify_row = $stmt_verify->get_result()->fetch_assoc();
$stmt_verify->close();

if ($verify_row) {
  echo "✓ Token verified! Found record: " . json_encode($verify_row) . "\n";
  
  // Update password
  $pass_hash = password_hash($new_password, PASSWORD_DEFAULT);
  $stmtUp = $conn->prepare("UPDATE user SET pass_hash = ? WHERE user_id = ?");
  $stmtUp->bind_param("si", $pass_hash, $verify_row["user_id"]);
  $stmtUp->execute();
  $stmtUp->close();
  
  // Delete token
  $stmtDel = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
  $stmtDel->bind_param("i", $verify_row["user_id"]);
  $stmtDel->execute();
  $stmtDel->close();
  
  echo "✓ Password updated and token deleted!\n";
} else {
  echo "✗ Token not found!\n";
}
?>
