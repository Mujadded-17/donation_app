<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

include "db.php";

// Support both ?id= and ?item_id=
$item_id = 0;
if (isset($_GET["id"])) {
    $item_id = (int)$_GET["id"];
} elseif (isset($_GET["item_id"])) {
    $item_id = (int)$_GET["item_id"];
}

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Valid item ID is required"
    ]);
    exit;
}

$sql = "SELECT 
            i.item_id,
            i.title,
            i.description,
            i.images,
            i.pickup_location,
            i.delivery_available,
            i.status,
            i.donor_id,
            i.post_date AS created_at,
            c.category_id,
            c.name AS category_name,
            u.name AS donor_name,
            u.email AS donor_email
        FROM item i
        LEFT JOIN category c ON i.category_id = c.category_id
        LEFT JOIN user u ON i.donor_id = u.user_id
        WHERE i.item_id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare query",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$item) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Item not found"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "item" => $item
]);
?>