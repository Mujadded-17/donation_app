<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  exit;
}

include "db.php";
include "config.php";
include "auth_guard.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
  echo json_encode(["success" => false, "message" => "email and password required"]);
  exit;
}

$stmt = mysqli_prepare($conn, "SELECT user_id, name, email, pass_hash, user_type FROM user WHERE email = ?");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "DB prepare failed", "error" => mysqli_error($conn)]);
  exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);

if (!mysqli_stmt_execute($stmt)) {
  echo json_encode(["success" => false, "message" => "DB execute failed", "error" => mysqli_stmt_error($stmt)]);
  exit;
}

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

$isAdmin = strtolower(trim($user["email"])) === strtolower(ADMIN_EMAIL);
if ($isAdmin) {
  $user["user_type"] = "admin";
}

$tokenPayload = [
  "user_id" => (int) $user["user_id"],
  "email" => $user["email"],
  "role" => $isAdmin ? "admin" : $user["user_type"],
  "iat" => time(),
  "exp" => time() + (60 * 60 * 24)
];

$token = create_auth_token($tokenPayload);

unset($user["pass_hash"]);

echo json_encode([
  "success" => true,
  "user" => $user,
  "token" => $token,
  "is_admin" => $isAdmin
]);