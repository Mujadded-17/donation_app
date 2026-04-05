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
require_once __DIR__ . "/auth_guard.php";

function respond($code, $payload)
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function json_input()
{
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

$authUser = require_auth($conn);
$user_id = (int)$authUser["user_id"];

// ---------- GET ----------
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $stmt = $conn->prepare(
        "SELECT user_id, name, email, phone, address, user_type, profile_url, created_at
         FROM `user`
         WHERE user_id = ?"
    );

    if (!$stmt) {
        respond(500, ["success" => false, "message" => "Prepare failed", "error" => $conn->error]);
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        respond(500, ["success" => false, "message" => "Execute failed", "error" => $stmt->error]);
    }

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        respond(404, ["success" => false, "message" => "User not found"]);
    }

    respond(200, ["success" => true, "user" => $user]);
}

// ---------- PUT ----------
if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $data = json_input();
    if (!$data) {
        respond(400, ["success" => false, "message" => "Invalid JSON"]);
    }

    $name = isset($data["name"]) ? trim($data["name"]) : "";
    $phone = array_key_exists("phone", $data) ? trim((string)$data["phone"]) : null;
    $address = array_key_exists("address", $data) ? trim((string)$data["address"]) : null;
    $profile_url = array_key_exists("profile_url", $data) ? trim((string)$data["profile_url"]) : null;

    if ($name === "") {
        respond(400, ["success" => false, "message" => "name required"]);
    }

    $stmt = $conn->prepare(
        "UPDATE `user`
         SET name = ?, phone = ?, address = ?, profile_url = ?
         WHERE user_id = ?"
    );

    if (!$stmt) {
        respond(500, ["success" => false, "message" => "Prepare failed", "error" => $conn->error]);
    }

    $stmt->bind_param("ssssi", $name, $phone, $address, $profile_url, $user_id);

    if (!$stmt->execute()) {
        respond(500, ["success" => false, "message" => "Update failed", "error" => $stmt->error]);
    }

    $stmt->close();

    $stmt2 = $conn->prepare(
        "SELECT user_id, name, email, phone, address, user_type, profile_url, created_at
         FROM `user`
         WHERE user_id = ?"
    );

    if (!$stmt2) {
        respond(500, ["success" => false, "message" => "Prepare failed", "error" => $conn->error]);
    }

    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $user = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    respond(200, [
        "success" => true,
        "message" => "Profile updated",
        "user" => $user
    ]);
}

respond(405, ["success" => false, "message" => "Method not allowed"]);