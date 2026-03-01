<?php
require_once __DIR__ . "/mail.php";

[$ok, $err] = sendThankYouEmail(
    "mujaddedc@gmail.com",
    "Test User",
    999
);

if ($ok) {
    echo "Email sent successfully!";
} else {
    echo "Email failed: " . $err;
}