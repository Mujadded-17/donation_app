<?php
require_once __DIR__ . "/config.php";

function sendResetPasswordEmail($toEmail, $toName, $resetLink) {
  $safeName = htmlspecialchars($toName ?: "User");
  $safeLink = htmlspecialchars($resetLink);

  $subject = "Reset your password";
  $body = "
    <div style='font-family:Arial,sans-serif;line-height:1.6'>
      <h2 style='margin:0 0 10px'>Password reset</h2>
      <p>Hi <b>{$safeName}</b>,</p>
      <p>We received a request to reset your password.</p>
      <p>
        <a href='{$safeLink}' style='display:inline-block;padding:10px 14px;background:#2f6cf6;color:#fff;text-decoration:none;border-radius:10px'>
          Reset Password
        </a>
      </p>
      <p>If the button doesn't work, copy and paste this link:</p>
      <p style='word-break:break-all'>{$safeLink}</p>
      <p style='color:#666'>This link expires in 1 hour.</p>
    </div>
  ";

  // Direct SMTP using fsockopen
  $smtp_host = SMTP_HOST;
  $smtp_port = SMTP_PORT;
  $smtp_user = SMTP_USER;
  $smtp_pass = SMTP_PASS;
  $from_email = SMTP_FROM;
  $from_name = SMTP_FROM_NAME;

  $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
  if (!$socket) return [false, "Connection failed: $errstr"];

  $response = fgets($socket, 1024);
  if (strpos($response, '220') === false) {
    fclose($socket);
    return [false, "SMTP greeting failed"];
  }

  // Send EHLO
  fwrite($socket, "EHLO localhost\r\n");
  $response = fgets($socket, 1024);

  // STARTTLS
  fwrite($socket, "STARTTLS\r\n");
  $response = fgets($socket, 1024);
  if (strpos($response, '220') === false) {
    fclose($socket);
    return [false, "STARTTLS failed"];
  }

  stream_context_set_params($socket, ["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
  stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

  // After TLS, send EHLO again
  fwrite($socket, "EHLO localhost\r\n");
  $response = fgets($socket, 1024);

  // AUTH LOGIN
  fwrite($socket, "AUTH LOGIN\r\n");
  $response = fgets($socket, 1024);

  fwrite($socket, base64_encode($smtp_user) . "\r\n");
  $response = fgets($socket, 1024);

  fwrite($socket, base64_encode($smtp_pass) . "\r\n");
  $response = fgets($socket, 1024);
  if (strpos($response, '235') === false) {
    fclose($socket);
    return [false, "Authentication failed"];
  }

  // MAIL FROM
  fwrite($socket, "MAIL FROM:<{$from_email}>\r\n");
  $response = fgets($socket, 1024);

  // RCPT TO
  fwrite($socket, "RCPT TO:<{$toEmail}>\r\n");
  $response = fgets($socket, 1024);

  // DATA
  fwrite($socket, "DATA\r\n");
  $response = fgets($socket, 1024);

  // Headers + body
  $headers = "From: {$from_name} <{$from_email}>\r\n";
  $headers .= "To: {$toName} <{$toEmail}>\r\n";
  $headers .= "Subject: {$subject}\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=utf-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";

  fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
  $response = fgets($socket, 1024);

  // QUIT
  fwrite($socket, "QUIT\r\n");
  fclose($socket);

  if (strpos($response, '250') !== false) {
    return [true, null];
  } else {
    return [false, "Email send failed"];
  }
}
