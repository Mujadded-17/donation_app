<?php
// mail_simple.php - Simple SMTP implementation without PHPMailer dependency

// Load config if not already loaded
if (!defined("SMTP_HOST")) {
    $configPath = __DIR__ . "/config.php";
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Fallback defaults (should be overridden by config.php)
        define("SMTP_HOST", "smtp.gmail.com");
        define("SMTP_PORT", 587);
        define("SMTP_USER", "");
        define("SMTP_PASS", "");
        define("SMTP_FROM", "");
        define("SMTP_FROM_NAME", "WarmConnect");
    }
}

function sendThankYouEmail($toEmail, $toName, $donationId) {
    // Validate email
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, "Invalid recipient email"];
    }

    // Build email headers and body
    $subject = "Thank you for your donation! (ID: {$donationId})";
    $safeName = htmlspecialchars($toName ?: "Donor", ENT_QUOTES, 'UTF-8');
    
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; line-height:1.5; max-width:600px; margin:0 auto;'>
        <div style='background-color:#f8f9fa; padding:20px; border-radius:8px;'>
            <h2 style='margin:0 0 15px; color:#d97706;'>Thank you, {$safeName}!</h2>
            <p style='margin:10px 0; color:#333;'>Your donation has been submitted successfully to WarmConnect.</p>
            
            <div style='background-color:#fff; padding:15px; border-left:4px solid #d97706; margin:15px 0;'>
                <p style='margin:5px 0;'><b>Donation ID:</b> <code>{$donationId}</code></p>
                <p style='margin:5px 0;'><b>Status:</b> <span style='color:#f59e0b;'>Pending Admin Review</span></p>
                <p style='margin:5px 0; font-size:12px; color:#666;'>Your donation will appear on the Explore page once approved by our admin team.</p>
            </div>
            
            <p style='margin:15px 0; color:#666; font-size:14px;'>We appreciate your generosity in helping the community!</p>
            <p style='margin:15px 0 0; color:#999; font-size:12px; border-top:1px solid #ddd; padding-top:15px;'>WarmConnect Team</p>
        </div>
    </div>
    ";

    $textBody = "Thank you, {$toName}!\n\nYour donation has been submitted successfully.\nDonation ID: {$donationId}\nStatus: Pending Admin Review\n\nWe appreciate your generosity!";

    // Try direct SMTP connection
    try {
        return sendViaSMTPSocket($toEmail, $toName, $subject, $htmlBody, $textBody);
    } catch (Exception $e) {
        return [false, "Email send failed: " . $e->getMessage()];
    }
}

function sendViaSMTPSocket($to, $toName, $subject, $htmlBody, $textBody) {
    try {
        // Open connection to SMTP server with TLS
        $sock = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
        if (!$sock) {
            return [false, "Could not connect to SMTP server: {$errstr}"];
        }

        stream_set_timeout($sock, 5);

        // Read initial response
        $response = fgets($sock);
        if (strpos($response, '220') === false) {
            fclose($sock);
            return [false, "SMTP server not ready"];
        }

        // Send EHLO
        fputs($sock, "EHLO localhost\r\n");
        smtpReadResponse($sock);

        // Initiate TLS
        fputs($sock, "STARTTLS\r\n");
        smtpReadResponse($sock);
        
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($sock);
            return [false, "Failed to enable TLS"];
        }

        // Send EHLO again after TLS
        fputs($sock, "EHLO localhost\r\n");
        smtpReadResponse($sock);

        // Authenticate
        fputs($sock, "AUTH LOGIN\r\n");
        smtpReadResponse($sock);
        
        fputs($sock, base64_encode(SMTP_USER) . "\r\n");
        smtpReadResponse($sock);
        
        fputs($sock, base64_encode(SMTP_PASS) . "\r\n");
        $authResponse = smtpReadResponse($sock);
        
        if (strpos($authResponse, '235') === false) {
            fclose($sock);
            return [false, "Authentication failed"];
        }

        // Send email
        fputs($sock, "MAIL FROM: <" . SMTP_FROM . ">\r\n");
        smtpReadResponse($sock);
        
        fputs($sock, "RCPT TO: <{$to}>\r\n");
        smtpReadResponse($sock);
        
        fputs($sock, "DATA\r\n");
        smtpReadResponse($sock);

        // Build full email
        $email = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        $email .= "To: {$toName} <{$to}>\r\n";
        $email .= "Subject: {$subject}\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: multipart/alternative; boundary=\"boundary123\"\r\n";
        $email .= "\r\n";
        $email .= "--boundary123\r\n";
        $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $email .= "Content-Transfer-Encoding: 8bit\r\n";
        $email .= "\r\n";
        $email .= $textBody . "\r\n";
        $email .= "--boundary123\r\n";
        $email .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email .= "Content-Transfer-Encoding: 8bit\r\n";
        $email .= "\r\n";
        $email .= $htmlBody . "\r\n";
        $email .= "--boundary123--\r\n";

        fputs($sock, $email . "\r\n.\r\n");
        smtpReadResponse($sock);

        // Quit
        fputs($sock, "QUIT\r\n");
        fclose($sock);

        return [true, null];
        
    } catch (Exception $e) {
        return [false, $e->getMessage()];
    }
}

function smtpReadResponse($sock) {
    $response = '';
    while ($line = fgets($sock, 512)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    return $response;
}
?>
