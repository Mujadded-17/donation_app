<?php
require_once __DIR__ . "/db.php";

$result = $conn->query("SELECT * FROM password_resets ORDER BY created_at DESC LIMIT 1");
$row = $result->fetch_assoc();

echo json_encode([
  "success" => true,
  "latest_reset" => $row,
  "message" => "Reset token created successfully"
], JSON_PRETTY_PRINT);
?>
