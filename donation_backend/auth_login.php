<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  exit;
}

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
  echo json_encode(["success" => false, "message" => "email and password required"]);
  exit;
}

$stmt = mysqli_prepare($conn, "SELECT user_id, name, email, pass_hash, user_type FROM User WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {
  echo json_encode(["success" => false, "message" => "User not found"]);
  exit;
}

if (!password_verify($password, $user["pass_hash"])) {
  echo json_encode(["success" => false, "message" => "Wrong password"]);
  exit;
}

unset($user["pass_hash"]);

echo json_encode(["success" => true, "user" => $user]);
