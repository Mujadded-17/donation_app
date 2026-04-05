<?php

function ensure_chat_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS donation_message (
        message_id INT AUTO_INCREMENT PRIMARY KEY,
        donation_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_donation_id (donation_id),
        INDEX idx_sender_id (sender_id),
        CONSTRAINT fk_dm_donation FOREIGN KEY (donation_id) REFERENCES donation(donation_id) ON DELETE CASCADE,
        CONSTRAINT fk_dm_sender FOREIGN KEY (sender_id) REFERENCES user(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return mysqli_query($conn, $sql);
}

function ensure_chat_seen_table($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS donation_chat_seen (
        donation_id INT NOT NULL,
        user_id INT NOT NULL,
        last_seen_message_id INT NOT NULL DEFAULT 0,
        seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (donation_id, user_id),
        INDEX idx_dcs_user (user_id),
        CONSTRAINT fk_dcs_donation FOREIGN KEY (donation_id) REFERENCES donation(donation_id) ON DELETE CASCADE,
        CONSTRAINT fk_dcs_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return mysqli_query($conn, $sql);
}

function upsert_seen_message($conn, $donationId, $userId, $lastSeenMessageId)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO donation_chat_seen (donation_id, user_id, last_seen_message_id, seen_at)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            last_seen_message_id = GREATEST(last_seen_message_id, VALUES(last_seen_message_id)),
            seen_at = NOW()"
    );

    if (!$stmt) {
        return [false, mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, "iii", $donationId, $userId, $lastSeenMessageId);
    $ok = mysqli_stmt_execute($stmt);
    $err = $ok ? null : mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    return [$ok, $err];
}

function get_seen_state($conn, $donationId, $userId)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT last_seen_message_id, seen_at
         FROM donation_chat_seen
         WHERE donation_id = ? AND user_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return [null, mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, "ii", $donationId, $userId);
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

function get_donation_for_user($conn, $donationId)
{
    $sql = "SELECT
                d.donation_id,
                d.item_id,
                d.donor_id,
                d.receiver_id,
                d.status AS donation_status,
                i.title AS item_title,
                i.images AS item_image,
                i.pickup_location,
                donor.name AS donor_name,
                receiver.name AS receiver_name
            FROM donation d
            INNER JOIN item i ON d.item_id = i.item_id
            LEFT JOIN user donor ON donor.user_id = d.donor_id
            LEFT JOIN user receiver ON receiver.user_id = d.receiver_id
            WHERE d.donation_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [null, mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, "i", $donationId);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return [null, $err];
    }

    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return [$row, null];
}
