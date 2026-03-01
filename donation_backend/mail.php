<?php
// mail.php
require_once __DIR__ . "/config.php";

// PHPMailer includes (zip/manual install)
require_once __DIR__ . "/lib/PHPMailer/src/Exception.php";
require_once __DIR__ . "/lib/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/lib/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendThankYouEmail($toEmail, $toName, $donationId) {
  $mail = new PHPMailer(true);

  try {
    // SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    // Sender + recipient
    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Thank you for your donation! (ID: {$donationId})";

    $safeName = htmlspecialchars($toName ?: "Donor");
    $mail->Body = "
      <div style='font-family: Arial, sans-serif; line-height:1.5'>
        <h2 style='margin:0 0 10px'>Thank you, {$safeName}!</h2>
        <p>Your donation has been submitted successfully.</p>
        <p><b>Donation ID:</b> {$donationId}</p>
        <p>Status: <b>Pending Admin Approval</b></p>
        <p style='color:#666'>WarmConnect Team</p>
      </div>
    ";

    $mail->AltBody = "Thank you, {$toName}! Your donation is submitted. Donation ID: {$donationId}. Status: Pending Admin Approval.";

    $mail->send();
    return [true, null];
  } catch (Exception $e) {
    return [false, $mail->ErrorInfo];
  }
}