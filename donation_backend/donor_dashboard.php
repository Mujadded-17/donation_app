<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode([
        "success" => false,
        "message" => "Only GET method is allowed"
    ]);
    exit;
}

require_once "db.php";

$userId = isset($_GET["user_id"]) ? (int) $_GET["user_id"] : 0;
if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Valid user_id is required"
    ]);
    exit;
}

function fetch_one_assoc($conn, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [null, mysqli_error($conn)];
    }

    if ($types !== "" && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return [null, $err];
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return [$row, null];
}

function fetch_all_assoc($conn, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [null, mysqli_error($conn)];
    }

    if ($types !== "" && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return [null, $err];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    return [$rows, null];
}

function normalize_image_path($raw) {
    $raw = trim((string)$raw);
    if ($raw === "") return null;
    return $raw;
}

/* -----------------------------------------------------------
   1) USER / DONOR INFO
------------------------------------------------------------ */
list($donor, $err) = fetch_one_assoc(
    $conn,
    "SELECT user_id, name, email, phone, address, user_type, profile_url, created_at
     FROM user
     WHERE user_id = ?
     LIMIT 1",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch donor profile",
        "error" => $err
    ]);
    exit;
}

if (!$donor) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

/* -----------------------------------------------------------
   2) SUMMARY COUNTS
------------------------------------------------------------ */
list($summaryCounts, $err) = fetch_one_assoc(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM item WHERE donor_id = ?) AS total_items,
        (SELECT COUNT(*) FROM item WHERE donor_id = ? AND status = 'available') AS available_items,
        (SELECT COUNT(*) FROM item WHERE donor_id = ? AND status = 'pending') AS pending_items,
        (SELECT COUNT(*) FROM item WHERE donor_id = ? AND status = 'claimed') AS claimed_items,
        (SELECT COUNT(*) FROM item WHERE donor_id = ? AND status = 'declined') AS declined_items,
        (SELECT COUNT(*) FROM donation WHERE donor_id = ? AND status = 'completed') AS completed_donations,
        (SELECT COUNT(*) FROM donation WHERE donor_id = ?) AS total_requests_received,
        (SELECT COUNT(*) FROM donation WHERE receiver_id = ?) AS my_requests_count",
    "iiiiiiii",
    [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch dashboard summary",
        "error" => $err
    ]);
    exit;
}

list($favoriteCategoryRow, $err) = fetch_one_assoc(
    $conn,
    "SELECT c.category_id, c.name, COUNT(*) AS total
     FROM item i
     INNER JOIN category c ON c.category_id = i.category_id
     WHERE i.donor_id = ?
     GROUP BY c.category_id, c.name
     ORDER BY total DESC, c.name ASC
     LIMIT 1",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch favorite category",
        "error" => $err
    ]);
    exit;
}

list($latestPostRow, $err) = fetch_one_assoc(
    $conn,
    "SELECT post_date
     FROM item
     WHERE donor_id = ?
     ORDER BY post_date DESC
     LIMIT 1",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch latest post info",
        "error" => $err
    ]);
    exit;
}

$totalItems = (int)($summaryCounts["total_items"] ?? 0);
$availableItems = (int)($summaryCounts["available_items"] ?? 0);
$pendingItems = (int)($summaryCounts["pending_items"] ?? 0);
$claimedItems = (int)($summaryCounts["claimed_items"] ?? 0);
$declinedItems = (int)($summaryCounts["declined_items"] ?? 0);
$completedDonations = (int)($summaryCounts["completed_donations"] ?? 0);
$totalRequestsReceived = (int)($summaryCounts["total_requests_received"] ?? 0);
$myRequestsCount = (int)($summaryCounts["my_requests_count"] ?? 0);

$kindnessSpark =
    ($totalItems * 10) +
    ($completedDonations * 25) +
    ($totalRequestsReceived * 5);

$summary = [
    "total_items" => $totalItems,
    "available_items" => $availableItems,
    "pending_items" => $pendingItems,
    "claimed_items" => $claimedItems,
    "declined_items" => $declinedItems,
    "completed_donations" => $completedDonations,
    "total_requests_received" => $totalRequestsReceived,
    "my_requests_count" => $myRequestsCount,
    "kindness_spark" => $kindnessSpark,
    "favorite_category" => $favoriteCategoryRow["name"] ?? null,
    "latest_post_date" => $latestPostRow["post_date"] ?? null
];

/* -----------------------------------------------------------
   3) ALL DONOR ITEMS
------------------------------------------------------------ */
list($itemsRaw, $err) = fetch_all_assoc(
    $conn,
    "SELECT
        i.item_id,
        i.title,
        i.description,
        i.images,
        i.status,
        i.delivery_available,
        i.pickup_location,
        i.post_date,
        i.donor_id,
        i.category_id,
        c.name AS category_name,
        COALESCE(rc.total_requests, 0) AS request_count,
        lr.receiver_id AS latest_receiver_id,
        u.name AS latest_receiver_name,
        lr.request_status AS latest_request_status,
        lr.request_date AS latest_request_date
     FROM item i
     LEFT JOIN category c
        ON c.category_id = i.category_id
     LEFT JOIN (
        SELECT item_id, COUNT(*) AS total_requests
        FROM donation
        GROUP BY item_id
     ) rc
        ON rc.item_id = i.item_id
     LEFT JOIN (
        SELECT d1.item_id, d1.receiver_id, d1.status AS request_status, d1.request_date
        FROM donation d1
        INNER JOIN (
            SELECT item_id, MAX(request_date) AS max_request_date
            FROM donation
            GROUP BY item_id
        ) d2
          ON d1.item_id = d2.item_id
         AND d1.request_date = d2.max_request_date
     ) lr
        ON lr.item_id = i.item_id
     LEFT JOIN user u
        ON u.user_id = lr.receiver_id
     WHERE i.donor_id = ?
     ORDER BY i.post_date DESC",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch donor items",
        "error" => $err
    ]);
    exit;
}

$items = [
    "available" => [],
    "pending" => [],
    "claimed" => [],
    "declined" => [],
    "completed" => []
];

foreach ($itemsRaw as $row) {
    $row["request_count"] = (int)($row["request_count"] ?? 0);
    $row["delivery_available"] = (int)($row["delivery_available"] ?? 0);
    $row["image_path"] = normalize_image_path($row["images"] ?? null);

    $status = strtolower(trim((string)($row["status"] ?? "")));

    if ($status === "available") {
        $items["available"][] = $row;
    } elseif ($status === "pending") {
        $items["pending"][] = $row;
    } elseif ($status === "claimed") {
        $items["claimed"][] = $row;
    } elseif ($status === "declined") {
        $items["declined"][] = $row;
    }
}

/* -----------------------------------------------------------
   4) COMPLETED DONATIONS
------------------------------------------------------------ */
list($completedItems, $err) = fetch_all_assoc(
    $conn,
    "SELECT
        d.donation_id,
        d.status AS donation_status,
        d.request_date,
        d.receiver_id,
        i.item_id,
        i.title,
        i.description,
        i.images,
        i.pickup_location,
        i.delivery_available,
        i.post_date,
        i.status AS item_status,
        c.name AS category_name,
        u.name AS receiver_name,
        u.email AS receiver_email
     FROM donation d
     INNER JOIN item i
        ON i.item_id = d.item_id
     LEFT JOIN category c
        ON c.category_id = i.category_id
     LEFT JOIN user u
        ON u.user_id = d.receiver_id
     WHERE d.donor_id = ?
       AND d.status = 'completed'
     ORDER BY d.request_date DESC",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch completed donations",
        "error" => $err
    ]);
    exit;
}

foreach ($completedItems as &$row) {
    $row["delivery_available"] = (int)($row["delivery_available"] ?? 0);
    $row["image_path"] = normalize_image_path($row["images"] ?? null);
}
unset($row);

$items["completed"] = $completedItems;

/* -----------------------------------------------------------
   5) REQUESTS RECEIVED ON DONOR ITEMS
------------------------------------------------------------ */
list($requestsReceived, $err) = fetch_all_assoc(
    $conn,
    "SELECT
        d.donation_id,
        d.item_id,
        d.donor_id,
        d.receiver_id,
        d.request_date,
        d.status,
        i.title,
        i.description,
        i.images,
        i.pickup_location,
        i.status AS item_status,
        c.name AS category_name,
        u.name AS receiver_name,
        u.email AS receiver_email,
        u.phone AS receiver_phone,
        u.address AS receiver_address
     FROM donation d
     INNER JOIN item i
        ON i.item_id = d.item_id
     LEFT JOIN category c
        ON c.category_id = i.category_id
     LEFT JOIN user u
        ON u.user_id = d.receiver_id
     WHERE d.donor_id = ?
     ORDER BY d.request_date DESC",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch requests received",
        "error" => $err
    ]);
    exit;
}

foreach ($requestsReceived as &$row) {
    $row["image_path"] = normalize_image_path($row["images"] ?? null);
}
unset($row);

/* -----------------------------------------------------------
   6) MY REQUESTS (WHERE THIS USER IS RECEIVER)
------------------------------------------------------------ */
list($myRequests, $err) = fetch_all_assoc(
    $conn,
    "SELECT
        d.donation_id,
        d.item_id,
        d.donor_id,
        d.receiver_id,
        d.request_date,
        d.status,
        i.title,
        i.description,
        i.images,
        i.pickup_location,
        i.status AS item_status,
        c.name AS category_name,
        u.name AS donor_name,
        u.email AS donor_email,
        u.phone AS donor_phone,
        u.address AS donor_address
     FROM donation d
     INNER JOIN item i
        ON i.item_id = d.item_id
     LEFT JOIN category c
        ON c.category_id = i.category_id
     LEFT JOIN user u
        ON u.user_id = d.donor_id
     WHERE d.receiver_id = ?
     ORDER BY d.request_date DESC",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch my requests",
        "error" => $err
    ]);
    exit;
}

foreach ($myRequests as &$row) {
    $row["image_path"] = normalize_image_path($row["images"] ?? null);
}
unset($row);

/* -----------------------------------------------------------
   7) NOTIFICATIONS
------------------------------------------------------------ */
list($notifications, $err) = fetch_all_assoc(
    $conn,
    "SELECT
        notify_id,
        user_id,
        type,
        message,
        create_time
     FROM notification
     WHERE user_id = ?
     ORDER BY create_time DESC
     LIMIT 20",
    "i",
    [$userId]
);

if ($err) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch notifications",
        "error" => $err
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "donor" => $donor,
    "summary" => $summary,
    "items" => $items,
    "requests_received" => $requestsReceived,
    "my_requests" => $myRequests,
    "notifications" => $notifications
]);