<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only GET method is allowed"
    ]);
    exit;
}

include "db.php";
include "auth_guard.php";
include "chat_bootstrap.php";

$authUser = require_auth($conn);
$userId = (int)$authUser["user_id"];

if (!ensure_chat_table($conn)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to initialize chat storage",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

if (!ensure_chat_seen_table($conn)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to initialize chat seen storage",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

$sql = "SELECT
            d.donation_id,
            d.status AS donation_status,
            d.item_id,
            d.donor_id,
            d.receiver_id,
            i.title AS item_title,
            i.images AS item_image,
            i.status AS item_status,
            i.pickup_location,
            donor.name AS donor_name,
            donor.email AS donor_email,
            receiver.name AS receiver_name,
            receiver.email AS receiver_email,
            lm.message AS last_message,
            lm.created_at AS last_message_at,
            lm.sender_id AS last_sender_id,
                        lm.message_id AS last_message_id,
                        COALESCE(seen.last_seen_message_id, 0) AS my_last_seen_message_id,
                        (
                                SELECT COUNT(*)
                                FROM donation_message dm_unread
                                WHERE dm_unread.donation_id = d.donation_id
                                    AND dm_unread.sender_id != ?
                                    AND dm_unread.message_id > COALESCE(seen.last_seen_message_id, 0)
                        ) AS unread_count
        FROM donation d
        INNER JOIN item i ON i.item_id = d.item_id
        LEFT JOIN user donor ON donor.user_id = d.donor_id
        LEFT JOIN user receiver ON receiver.user_id = d.receiver_id
        INNER JOIN (
            SELECT dm1.donation_id, dm1.message_id, dm1.sender_id, dm1.message, dm1.created_at
            FROM donation_message dm1
            INNER JOIN (
                SELECT donation_id, MAX(message_id) AS max_message_id
                FROM donation_message
                GROUP BY donation_id
            ) dm2
              ON dm1.donation_id = dm2.donation_id
             AND dm1.message_id = dm2.max_message_id
        ) lm ON lm.donation_id = d.donation_id
                LEFT JOIN donation_chat_seen seen
                    ON seen.donation_id = d.donation_id
                 AND seen.user_id = ?
        WHERE d.donor_id = ? OR d.receiver_id = ?
        ORDER BY lm.message_id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare conversations query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $userId, $userId);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch conversations",
        "error" => mysqli_stmt_error($stmt)
    ]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $isDonor = (int)$row["donor_id"] === $userId;
    $row["peer_name"] = $isDonor ? ($row["receiver_name"] ?: "Receiver") : ($row["donor_name"] ?: "Donor");
    $row["peer_email"] = $isDonor ? ($row["receiver_email"] ?: "") : ($row["donor_email"] ?: "");
    $rows[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "data" => $rows,
    "count" => count($rows)
]);
