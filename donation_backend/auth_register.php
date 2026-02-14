<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  exit; // preflight
}

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$phone = trim($data["phone"] ?? "");
$address = trim($data["address"] ?? "");
$user_type = trim($data["user_type"] ?? "receiver"); // default

if ($name === "" || $email === "" || $password === "") {
  echo json_encode(["success" => false, "message" => "name, email, password required"]);
  exit;
}

$pass_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn, "INSERT INTO User (name, email, pass_hash, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $pass_hash, $phone, $address, $user_type);

if (mysqli_stmt_execute($stmt)) {
  echo json_encode(["success" => true, "message" => "User registered"]);
} else {
  // duplicate email error etc.
  echo json_encode(["success" => false, "message" => "Registration failed", "error" => mysqli_error($conn)]);
}
