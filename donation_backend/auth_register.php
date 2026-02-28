<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  exit; // preflight
}

include "db.php";
include "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$phone = trim($data["phone"] ?? "");
$address = trim($data["address"] ?? "");
$requestedType = trim($data["user_type"] ?? "receiver");

$isAdminEmail = strtolower($email) === strtolower(ADMIN_EMAIL);
$user_type = "receiver";

if ($isAdminEmail) {
  $user_type = "admin";
} elseif ($requestedType === "donor") {
  $user_type = "donor";
}

if ($name === "" || $email === "" || $password === "") {
  echo json_encode(["success" => false, "message" => "name, email, password required"]);
  exit;
}

$pass_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn, "INSERT INTO user (name, email, pass_hash, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
  echo json_encode(["success" => false, "message" => "DB prepare failed", "error" => mysqli_error($conn)]);
  exit;
}

mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $pass_hash, $phone, $address, $user_type);

if (mysqli_stmt_execute($stmt)) {
  echo json_encode(["success" => true, "message" => "User registered", "user_type" => $user_type]);
} else {
  $errNo = mysqli_errno($conn);
  $err = mysqli_error($conn);

  if ($errNo === 1062) {
    echo json_encode(["success" => false, "message" => "Email already registered. Please login instead.", "error" => $err]);
  } else {
    echo json_encode(["success" => false, "message" => "Registration failed", "error" => $err]);
  }
}