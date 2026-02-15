<?php
include "db.php";

$sql = "SELECT * FROM Item";
$result = mysqli_query($conn, $sql);

$items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $items
]);
?>