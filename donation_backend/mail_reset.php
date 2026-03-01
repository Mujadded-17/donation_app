<?php
require_once __DIR__ . "/config.php";

require_once __DIR__ . "/lib/PHPMailer/src/Exception.php";
require_once __DIR__ . "/lib/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/lib/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetPasswordEmail($toEmail, $toName, $resetLink) {
  $mail = new PHPMailer(true);

  try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->Subject = "Reset your password";

    $safeName = htmlspecialchars($toName ?: "User");
    $safeLink = htmlspecialchars($resetLink);

    $mail->Body = "
      <div style='font-family:Arial,sans-serif;line-height:1.6'>
        <h2 style='margin:0 0 10px'>Password reset</h2>
        <p>Hi <b>{$safeName}</b>,</p>
        <p>We received a request to reset your password.</p>
        <p>
          <a href='{$safeLink}' style='display:inline-block;padding:10px 14px;background:#2f6cf6;color:#fff;text-decoration:none;border-radius:10px'>
            Reset Password
          </a>
        </p>
        <p>If the button doesn’t work, copy and paste this link:</p>
        <p style='word-break:break-all'>{$safeLink}</p>
        <p style='color:#666'>This link expires in 1 hour.</p>
      </div>
    ";

    $mail->AltBody = "Reset your password: $resetLink (expires in 1 hour)";
    $mail->send();
    return [true, null];
  } catch (Exception $e) {
    return [false, $mail->ErrorInfo];
  }
}