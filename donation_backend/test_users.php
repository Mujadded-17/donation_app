<?php
require_once __DIR__ . "/db.php";

$users = $conn->query("SELECT user_id, name, email, user_type FROM user");
$user_list = [];
while ($row = $users->fetch_assoc()) {
  $user_list[] = $row;
}

echo json_encode([
  "success" => true,
  "users" => $user_list
], JSON_PRETTY_PRINT);
?>
