<?php
/**
 * Email sending utility
 */

class Mailer
{
    /**
     * Send an email
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = false): bool
    {
        $fromEmail = config('MAIL_FROM');
        $fromName = config('MAIL_FROM_NAME');
        $smtpHost = config('SMTP_HOST');

        // If SMTP is configured, use it
        if (!empty($smtpHost)) {
            return self::sendViaSMTP($to, $subject, $body, $isHtml);
        }

        // Fall back to PHP mail()
        $headers = [
            'From' => "{$fromName} <{$fromEmail}>",
            'Reply-To' => $fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
        ];

        if ($isHtml) {
            $headers['MIME-Version'] = '1.0';
            $headers['Content-type'] = 'text/html; charset=UTF-8';
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $body, $headerString);
    }

    /**
     * Send via SMTP (basic implementation)
     */
    private static function sendViaSMTP(string $to, string $subject, string $body, bool $isHtml): bool
    {
        $host = config('SMTP_HOST');
        $port = config('SMTP_PORT', 587);
        $user = config('SMTP_USER');
        $pass = config('SMTP_PASS');
        $secure = config('SMTP_SECURE', 'tls');
        $from = config('MAIL_FROM');
        $fromName = config('MAIL_FROM_NAME');

        // For a production system, you'd want to use a proper SMTP library like PHPMailer
        // This is a simplified implementation that works for basic cases

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $socket = stream_socket_client(
            ($secure === 'ssl' ? 'ssl://' : '') . "{$host}:{$port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("SMTP connection failed: {$errstr} ({$errno})");
            return false;
        }

        try {
            // Read greeting
            self::smtpRead($socket);

            // EHLO
            self::smtpWrite($socket, "EHLO " . gethostname());
            self::smtpRead($socket);

            // STARTTLS if needed
            if ($secure === 'tls') {
                self::smtpWrite($socket, "STARTTLS");
                self::smtpRead($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                self::smtpWrite($socket, "EHLO " . gethostname());
                self::smtpRead($socket);
            }

            // AUTH LOGIN
            if ($user && $pass) {
                self::smtpWrite($socket, "AUTH LOGIN");
                self::smtpRead($socket);
                self::smtpWrite($socket, base64_encode($user));
                self::smtpRead($socket);
                self::smtpWrite($socket, base64_encode($pass));
                self::smtpRead($socket);
            }

            // MAIL FROM
            self::smtpWrite($socket, "MAIL FROM:<{$from}>");
            self::smtpRead($socket);

            // RCPT TO
            self::smtpWrite($socket, "RCPT TO:<{$to}>");
            self::smtpRead($socket);

            // DATA
            self::smtpWrite($socket, "DATA");
            self::smtpRead($socket);

            // Headers and body
            $contentType = $isHtml ? 'text/html' : 'text/plain';
            $message = "From: {$fromName} <{$from}>\r\n";
            $message .= "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body;
            $message .= "\r\n.";

            self::smtpWrite($socket, $message);
            self::smtpRead($socket);

            // QUIT
            self::smtpWrite($socket, "QUIT");
            self::smtpRead($socket);

            fclose($socket);
            return true;

        } catch (\Exception $e) {
            error_log("SMTP error: " . $e->getMessage());
            if ($socket) {
                fclose($socket);
            }
            return false;
        }
    }

    private static function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
    }

    private static function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordReset(string $to, string $username, string $token): bool
    {
        $resetUrl = config('APP_URL') . '/reset-password/' . $token;
        $appName = config('APP_NAME');

        $subject = "Reset your {$appName} password";

        $body = "Hi {$username},\n\n";
        $body .= "You requested a password reset for your {$appName} account.\n\n";
        $body .= "Click the link below to reset your password:\n";
        $body .= "{$resetUrl}\n\n";
        $body .= "This link will expire in 1 hour.\n\n";
        $body .= "If you didn't request this, you can safely ignore this email.\n\n";
        $body .= "- {$appName}";

        return self::send($to, $subject, $body);
    }

    /**
     * Send email verification for email change
     */
    public static function sendEmailVerification(string $to, string $username, string $token): bool
    {
        $verifyUrl = config('APP_URL') . '/verify-email/' . $token;
        $appName = config('APP_NAME');

        $subject = "Verify your new email address - {$appName}";

        $body = "Hi {$username},\n\n";
        $body .= "You requested to change your email address on {$appName}.\n\n";
        $body .= "Click the link below to verify this new email address:\n";
        $body .= "{$verifyUrl}\n\n";
        $body .= "This link will expire in 24 hours.\n\n";
        $body .= "If you didn't request this change, you can safely ignore this email.\n\n";
        $body .= "- {$appName}";

        return self::send($to, $subject, $body);
    }

    /**
     * Send notification about email change to old email (security alert)
     */
    public static function sendEmailChangedNotification(string $oldEmail, string $username, string $newEmail): bool
    {
        $appName = config('APP_NAME');

        $subject = "Your {$appName} email address was changed";

        $body = "Hi {$username},\n\n";
        $body .= "This is a security notification to let you know that the email address on your {$appName} account was changed.\n\n";
        $body .= "New email address: {$newEmail}\n\n";
        $body .= "If you made this change, no action is needed.\n\n";
        $body .= "If you did NOT make this change, please contact us immediately as your account may have been compromised.\n\n";
        $body .= "- {$appName}";

        return self::send($oldEmail, $subject, $body);
    }
}
