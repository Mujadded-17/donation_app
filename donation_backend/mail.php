<?php
require_once __DIR__ . "/config.php";

require_once __DIR__ . "/lib/PHPMailer/src/Exception.php";
require_once __DIR__ . "/lib/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/lib/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function baseMailer()
{
    if (
        empty(SMTP_HOST) ||
        empty(SMTP_PORT) ||
        empty(SMTP_USER) ||
        empty(SMTP_PASS) ||
        empty(SMTP_FROM)
    ) {
        throw new Exception("SMTP is not configured correctly");
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->Port = (int) SMTP_PORT;
    $mail->CharSet = "UTF-8";

    if ((int) SMTP_PORT === 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->SMTPDebug = 0;
    $mail->setFrom(SMTP_FROM, defined("SMTP_FROM_NAME") ? SMTP_FROM_NAME : "WarmConnect");

    return $mail;
}

function sendThankYouEmail($toEmail, $toName, $donationId)
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid recipient email"];
    }

    try {
        $mail = baseMailer();

        $safeName = htmlspecialchars($toName ?: "Donor", ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName ?: "Donor");
        $mail->isHTML(true);
        $mail->Subject = "Thank you for your donation! (ID: {$donationId})";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height:1.6; max-width:600px; margin:0 auto;'>
                <div style='background:#f8f9fa; padding:20px; border-radius:10px;'>
                    <h2 style='margin:0 0 12px; color:#d97706;'>Thank you, {$safeName}!</h2>
                    <p>Your donation has been submitted successfully to <b>WarmConnect</b>.</p>

                    <div style='background:#fff; padding:15px; border-left:4px solid #d97706; margin:15px 0;'>
                        <p style='margin:6px 0;'><b>Donation ID:</b> {$donationId}</p>
                        <p style='margin:6px 0;'><b>Status:</b> Pending Admin Approval</p>
                    </div>

                    <p>We appreciate your generosity in helping the community.</p>
                    <p style='color:#666; font-size:13px; margin-top:20px;'>WarmConnect Team</p>
                </div>
            </div>
        ";

        $mail->AltBody =
            "Thank you, " . ($toName ?: "Donor") . "!\n\n" .
            "Your donation has been submitted successfully.\n" .
            "Donation ID: {$donationId}\n" .
            "Status: Pending Admin Approval\n\n" .
            "WarmConnect Team";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

function sendDonationApprovedEmail($toEmail, $toName, $donationId, $itemTitle = "")
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid recipient email"];
    }

    try {
        $mail = baseMailer();

        $safeName = htmlspecialchars($toName ?: "Donor", ENT_QUOTES, "UTF-8");
        $safeTitle = htmlspecialchars($itemTitle ?: "Your donation item", ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName ?: "Donor");
        $mail->isHTML(true);
        $mail->Subject = "Your donation has been approved! (ID: {$donationId})";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height:1.6; max-width:600px; margin:0 auto;'>
                <div style='background:#f8f9fa; padding:20px; border-radius:10px;'>
                    <h2 style='margin:0 0 12px; color:#16a34a;'>Good news, {$safeName}!</h2>
                    <p>Your donation has been <b>approved</b> by the admin.</p>

                    <div style='background:#fff; padding:15px; border-left:4px solid #16a34a; margin:15px 0;'>
                        <p style='margin:6px 0;'><b>Donation ID:</b> {$donationId}</p>
                        <p style='margin:6px 0;'><b>Item:</b> {$safeTitle}</p>
                        <p style='margin:6px 0;'><b>Status:</b> Available</p>
                    </div>

                    <p>Your donation is now visible for receivers in the platform.</p>
                    <p style='color:#666; font-size:13px; margin-top:20px;'>WarmConnect Team</p>
                </div>
            </div>
        ";

        $mail->AltBody =
            "Good news, " . ($toName ?: "Donor") . "!\n\n" .
            "Your donation has been approved by the admin.\n" .
            "Donation ID: {$donationId}\n" .
            "Item: " . ($itemTitle ?: "Your donation item") . "\n" .
            "Status: Available\n\n" .
            "WarmConnect Team";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

function sendDonationDeclinedEmail($toEmail, $toName, $donationId, $itemTitle = "")
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid recipient email"];
    }

    try {
        $mail = baseMailer();

        $safeName = htmlspecialchars($toName ?: "Donor", ENT_QUOTES, "UTF-8");
        $safeTitle = htmlspecialchars($itemTitle ?: "Your donation item", ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName ?: "Donor");
        $mail->isHTML(true);
        $mail->Subject = "Update on your donation (ID: {$donationId})";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height:1.6; max-width:600px; margin:0 auto;'>
                <div style='background:#f8f9fa; padding:20px; border-radius:10px;'>
                    <h2 style='margin:0 0 12px; color:#dc2626;'>Hello, {$safeName}</h2>
                    <p>Your donation could not be approved at this time.</p>

                    <div style='background:#fff; padding:15px; border-left:4px solid #dc2626; margin:15px 0;'>
                        <p style='margin:6px 0;'><b>Donation ID:</b> {$donationId}</p>
                        <p style='margin:6px 0;'><b>Item:</b> {$safeTitle}</p>
                        <p style='margin:6px 0;'><b>Status:</b> Declined</p>
                    </div>

                    <p>If needed, you can review the item details and submit again.</p>
                    <p style='color:#666; font-size:13px; margin-top:20px;'>WarmConnect Team</p>
                </div>
            </div>
        ";

        $mail->AltBody =
            "Hello, " . ($toName ?: "Donor") . "!\n\n" .
            "Your donation could not be approved at this time.\n" .
            "Donation ID: {$donationId}\n" .
            "Item: " . ($itemTitle ?: "Your donation item") . "\n" .
            "Status: Declined\n\n" .
            "WarmConnect Team";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

function sendItemRequestToDonorEmail(
    $toEmail,
    $toName,
    $receiverName,
    $receiverEmail,
    $itemTitle,
    $donationId
) {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid donor email"];
    }

    try {
        $mail = baseMailer();

        $safeDonorName = htmlspecialchars($toName ?: "Donor", ENT_QUOTES, "UTF-8");
        $safeReceiverName = htmlspecialchars($receiverName ?: "A receiver", ENT_QUOTES, "UTF-8");
        $safeReceiverEmail = htmlspecialchars($receiverEmail ?: "Not provided", ENT_QUOTES, "UTF-8");
        $safeItemTitle = htmlspecialchars($itemTitle ?: "your item", ENT_QUOTES, "UTF-8");

        $mail->addAddress($toEmail, $toName ?: "Donor");
        $mail->isHTML(true);
        $mail->Subject = "New request for your donation item";

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; line-height:1.6; max-width:600px; margin:0 auto;'>
                <div style='background:#f8f9fa; padding:20px; border-radius:10px;'>
                    <h2 style='margin:0 0 12px; color:#2563eb;'>Hello, {$safeDonorName}!</h2>
                    <p>You have received a new request for your donation item on <b>WarmConnect</b>.</p>

                    <div style='background:#fff; padding:15px; border-left:4px solid #2563eb; margin:15px 0;'>
                        <p style='margin:6px 0;'><b>Item:</b> {$safeItemTitle}</p>
                        <p style='margin:6px 0;'><b>Request ID:</b> {$donationId}</p>
                        <p style='margin:6px 0;'><b>Requested by:</b> {$safeReceiverName}</p>
                        <p style='margin:6px 0;'><b>Receiver Email:</b> {$safeReceiverEmail}</p>
                        <p style='margin:6px 0;'><b>Status:</b> Requested</p>
                    </div>

                    <p>Please log in to your account and check the request/chat section to continue.</p>
                    <p style='color:#666; font-size:13px; margin-top:20px;'>WarmConnect Team</p>
                </div>
            </div>
        ";

        $mail->AltBody =
            "Hello, " . ($toName ?: "Donor") . "!\n\n" .
            "You have received a new request for your donation item.\n" .
            "Item: " . ($itemTitle ?: "your item") . "\n" .
            "Request ID: {$donationId}\n" .
            "Requested by: " . ($receiverName ?: "A receiver") . "\n" .
            "Receiver Email: " . ($receiverEmail ?: "Not provided") . "\n" .
            "Status: Requested\n\n" .
            "Please log in to your account and check the request/chat section.\n\n" .
            "WarmConnect Team";

        $mail->send();
        return [true, null];
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}