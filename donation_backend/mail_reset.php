<?php
require_once __DIR__ . "/config.php";

function sendResetPasswordEmail($toEmail, $toName, $resetLink)
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid recipient email"];
    }

    $safeName = htmlspecialchars($toName ?: "User", ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    $subject = "Reset your password";

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; line-height:1.5; max-width:600px; margin:0 auto;'>
        <div style='background:#f8f9fa; padding:20px; border-radius:8px;'>
            <h2 style='margin:0 0 15px; color:#d97706;'>Password reset</h2>
            <p>Hi <b>{$safeName}</b>,</p>
            <p>We received a request to reset your password.</p>
            <p style='margin:20px 0;'>
                <a href='{$safeLink}' style='display:inline-block;padding:12px 18px;background:#2f6cf6;color:#fff;text-decoration:none;border-radius:10px;font-weight:bold;'>
                    Reset Password
                </a>
            </p>
            <p>If the button does not work, copy and paste this link into your browser:</p>
            <p style='word-break:break-all;'>{$safeLink}</p>
            <p style='color:#666;'>This link expires in 1 hour.</p>
            <p style='margin-top:20px;color:#999;font-size:12px;border-top:1px solid #ddd;padding-top:15px;'>WarmConnect Team</p>
        </div>
    </div>
    ";

    $textBody = "Hi {$toName},\n\n"
        . "We received a request to reset your password.\n\n"
        . "Open this link:\n{$resetLink}\n\n"
        . "This link expires in 1 hour.\n\n"
        . "WarmConnect Team";

    try {
        return sendResetViaSMTPSocket($toEmail, $toName, $subject, $htmlBody, $textBody);
    } catch (Throwable $e) {
        return [false, "Email send failed: " . $e->getMessage()];
    }
}

function sendResetViaSMTPSocket($to, $toName, $subject, $htmlBody, $textBody)
{
    $sock = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$sock) {
        return [false, "Could not connect to SMTP server: {$errstr}"];
    }

    stream_set_timeout($sock, 5);

    $response = smtpResetReadResponse($sock);
    if (strpos($response, '220') === false) {
        fclose($sock);
        return [false, "SMTP server not ready: " . trim($response)];
    }

    fputs($sock, "EHLO localhost\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, "STARTTLS\r\n");
    $response = smtpResetReadResponse($sock);
    if (strpos($response, '220') === false) {
        fclose($sock);
        return [false, "STARTTLS failed: " . trim($response)];
    }

    if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($sock);
        return [false, "Failed to enable TLS"];
    }

    fputs($sock, "EHLO localhost\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, "AUTH LOGIN\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, base64_encode(SMTP_USER) . "\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, base64_encode(SMTP_PASS) . "\r\n");
    $authResponse = smtpResetReadResponse($sock);

    if (strpos($authResponse, '235') === false) {
        fclose($sock);
        return [false, "Authentication failed: " . trim($authResponse)];
    }

    fputs($sock, "MAIL FROM: <" . SMTP_FROM . ">\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, "RCPT TO: <{$to}>\r\n");
    smtpResetReadResponse($sock);

    fputs($sock, "DATA\r\n");
    smtpResetReadResponse($sock);

    $boundary = "boundary_" . md5((string)microtime(true));

    $email = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $email .= "To: " . ($toName ?: "User") . " <{$to}>\r\n";
    $email .= "Subject: {$subject}\r\n";
    $email .= "MIME-Version: 1.0\r\n";
    $email .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $email .= "\r\n";
    $email .= "--{$boundary}\r\n";
    $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email .= "Content-Transfer-Encoding: 8bit\r\n";
    $email .= "\r\n";
    $email .= $textBody . "\r\n";
    $email .= "--{$boundary}\r\n";
    $email .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email .= "Content-Transfer-Encoding: 8bit\r\n";
    $email .= "\r\n";
    $email .= $htmlBody . "\r\n";
    $email .= "--{$boundary}--\r\n";

    fputs($sock, $email . "\r\n.\r\n");
    $response = smtpResetReadResponse($sock);

    fputs($sock, "QUIT\r\n");
    fclose($sock);

    if (strpos($response, '250') !== false) {
        return [true, null];
    }

    return [false, "Email send failed: " . trim($response)];
}

function smtpResetReadResponse($sock)
{
    $response = '';
    while ($line = fgets($sock, 512)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}