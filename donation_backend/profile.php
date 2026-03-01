<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS (DEV)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];

if (in_array($origin, $allowed)) {
  header("Access-Control-Allow-Origin: $origin");
}
header("Vary: Origin");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

require_once __DIR__ . "/db.php";

function respond($code, $payload) {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

function json_input() {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

// ---------- GET ----------
if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
  if ($user_id <= 0) respond(400, ["message" => "user_id required"]);

  $stmt = $conn->prepare(
    "SELECT user_id, name, email, phone, address, user_type, profile_url, created_at
     FROM `user` WHERE user_id = ?"
  );
  if (!$stmt) respond(500, ["message" => "Prepare failed", "error" => $conn->error]);

  $stmt->bind_param("i", $user_id);
  if (!$stmt->execute()) respond(500, ["message" => "Execute failed", "error" => $stmt->error]);

  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user) respond(404, ["message" => "User not found"]);
  respond(200, $user);
}

// ---------- PUT (UPDATE) ----------
if ($_SERVER["REQUEST_METHOD"] === "PUT") {
  $data = json_input();
  if (!$data) respond(400, ["message" => "Invalid JSON"]);

  $user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
  $name = isset($data["name"]) ? trim($data["name"]) : "";
  $phone = array_key_exists("phone", $data) ? trim((string)$data["phone"]) : null;
  $address = array_key_exists("address", $data) ? trim((string)$data["address"]) : null;
  $profile_url = array_key_exists("profile_url", $data) ? trim((string)$data["profile_url"]) : null;

  if ($user_id <= 0) respond(400, ["message" => "user_id required"]);
  if ($name === "") respond(400, ["message" => "name required"]);

  $stmt = $conn->prepare("UPDATE `user` SET name=?, phone=?, address=?, profile_url=? WHERE user_id=?");
  if (!$stmt) respond(500, ["message" => "Prepare failed", "error" => $conn->error]);

  $stmt->bind_param("ssssi", $name, $phone, $address, $profile_url, $user_id);
  if (!$stmt->execute()) respond(500, ["message" => "Update failed", "error" => $stmt->error]);
  $stmt->close();

  // return updated user
  $stmt2 = $conn->prepare(
    "SELECT user_id, name, email, phone, address, user_type, profile_url, created_at
     FROM `user` WHERE user_id = ?"
  );
  $stmt2->bind_param("i", $user_id);
  $stmt2->execute();
  $user = $stmt2->get_result()->fetch_assoc();
  $stmt2->close();

  respond(200, ["message" => "Profile updated", "user" => $user]);
}

respond(405, ["message" => "Method not allowed"]);