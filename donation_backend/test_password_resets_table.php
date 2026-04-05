<?php
require_once __DIR__ . "/db.php";

// Check if password_resets table exists and show its structure
$result = $conn->query("DESCRIBE password_resets");
if ($result) {
  $columns = [];
  while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
  }
  echo json_encode([
    "exists" => true,
    "columns" => $columns
  ], JSON_PRETTY_PRINT);
} else {
  echo json_encode([
    "exists" => false,
    "error" => $conn->error
  ], JSON_PRETTY_PRINT);
}

// Try a test insert
$user_id = 1;
$token_hash = hash("sha256", "test_token_" . time());
$expires_at = date("Y-m-d H:i:s", strtotime("+1 hour"));

$stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
if (!$stmt) {
  echo json_encode(["insert_test" => false, "prepare_error" => $conn->error], JSON_PRETTY_PRINT);
} else {
  $stmt->bind_param("iss", $user_id, $token_hash, $expires_at);
  if ($stmt->execute()) {
    echo json_encode(["insert_test" => true, "message" => "Insert successful"], JSON_PRETTY_PRINT);
  } else {
    echo json_encode(["insert_test" => false, "execute_error" => $stmt->error], JSON_PRETTY_PRINT);
  }
  $stmt->close();
}
?>
