<?php
include_once "config.php";

function base64url_encode_custom($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode_custom($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function create_auth_token($payload)
{
    $header = ["alg" => "HS256", "typ" => "JWT"];
    $segments = [
        base64url_encode_custom(json_encode($header)),
        base64url_encode_custom(json_encode($payload))
    ];

    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, APP_TOKEN_SECRET, true);
    $segments[] = base64url_encode_custom($signature);

    return implode('.', $segments);
}

function verify_auth_token($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerB64, $payloadB64, $sigB64] = $parts;

    $signingInput = $headerB64 . '.' . $payloadB64;
    $expectedSig = base64url_encode_custom(
        hash_hmac('sha256', $signingInput, APP_TOKEN_SECRET, true)
    );

    if (!hash_equals($expectedSig, $sigB64)) {
        return null;
    }

    $payload = json_decode(base64url_decode_custom($payloadB64), true);
    if (!$payload || !is_array($payload)) {
        return null;
    }

    if (isset($payload["exp"]) && time() > (int)$payload["exp"]) {
        return null;
    }

    return $payload;
}

function get_bearer_token()
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }

    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function require_auth($conn = null)
{
    $token = get_bearer_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Missing authorization token"
        ]);
        exit;
    }

    $payload = verify_auth_token($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired token"
        ]);
        exit;
    }

    $userId = (int)($payload["user_id"] ?? 0);
    $email = trim($payload["email"] ?? "");
    $role = trim($payload["role"] ?? "");

    if ($userId <= 0 || $email === "") {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid token payload"
        ]);
        exit;
    }

    if ($conn) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, email, user_type FROM user WHERE user_id = ? LIMIT 1"
        );

        if (!$stmt) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "DB prepare failed",
                "error" => mysqli_error($conn)
            ]);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);
            exit;
        }

        return [
            "user_id" => (int)$user["user_id"],
            "email" => $user["email"],
            "role" => strtolower(trim($user["email"])) === strtolower(ADMIN_EMAIL)
                ? "admin"
                : $user["user_type"]
        ];
    }

    return [
        "user_id" => $userId,
        "email" => $email,
        "role" => $role
    ];
}

function require_admin_auth($conn)
{
    $auth = require_auth($conn);

    if (
        strtolower(trim($auth["email"])) !== strtolower(ADMIN_EMAIL) ||
        trim($auth["role"]) !== "admin"
    ) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Admin access denied"
        ]);
        exit;
    }

    return $auth;
}