<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "db.php";

$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 8;
if ($limit <= 0) $limit = 8;

$sql = "
  SELECT
    i.item_id,
    i.title,
    i.description,
    i.images,
    i.pickup_location,
    i.status,
    i.post_date,
    i.delivery_available,
    i.donor_id,
    i.category_id,
    c.name AS category_name
  FROM item i
  JOIN category c ON c.category_id = i.category_id
  ORDER BY i.post_date DESC
  LIMIT $limit
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch items",
        "error" => mysqli_error($conn)
    ]);
    exit;
}

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $filename = trim($row["images"] ?? "");

    // ✅ Handle both styles:
    // 1) "jacket.jpg"
    // 2) "uploads/items/abc.jpg"
    // 3) "uploads/abc.jpg"
    if ($filename !== "") {
        if (strpos($filename, "uploads/") === 0) {
            // already starts with uploads/
            $row["image_url"] = "http://localhost/donation_backend/" . $filename;
        } else {
            // plain filename
            $row["image_url"] = "http://localhost/donation_backend/uploads/" . $filename;
        }
    } else {
        $row["image_url"] = null;
    }

    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $items
]);