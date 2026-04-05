<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");

include "db.php";

// Get item_id from query parameters
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($item_id === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Item ID is required"
    ]);
    exit;
}

// Fetch item details with category and donor information
$sql = "SELECT 
            i.item_id,
            i.title,
            i.description,
            i.images,
            i.pickup_location,
            i.delivery_available,
            i.status,
            i.donor_id as user_id,
            i.post_date as created_at,
            c.name as category_name,
            c.category_id,
            u.name as donor_name,
            u.email as donor_email
        FROM item i
        LEFT JOIN category c ON i.category_id = c.category_id
        LEFT JOIN user u ON i.donor_id = u.user_id
        WHERE i.item_id = ? AND i.status = 'available'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        "success" => true,
        "data" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Item not found or not available"
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
