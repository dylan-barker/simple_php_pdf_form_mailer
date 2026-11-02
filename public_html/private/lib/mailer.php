<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(404);
    exit;
}

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Email the in-memory PDF as an attachment.
 *
 * @param array<string, mixed> $data   Used only for the plain-text body; the
 *                                     attachment already contains the details.
 * @param array<string, mixed> $config
 *
 * @throws RuntimeException
 */
function send_pdf_email(string $pdf, array $data, array $config): void
{
    $required = [
        'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass',
        'from_email', 'to_email',
    ];

    foreach ($required as $key) {
        if (!isset($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('Mail configuration is incomplete: ' . $key . ' is missing.');
        }
    }

    $secure = strtolower((string) $config['smtp_secure']);
    if (!in_array($secure, ['tls', 'ssl'], true)) {
        throw new RuntimeException('Refusing to send: SMTP encryption must be "tls" or "ssl".');
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = (string) $config['smtp_host'];
        $mail->Port = (int) $config['smtp_port'];
        $mail->SMTPAuth = true;
        $mail->Username = (string) $config['smtp_user'];
        $mail->Password = (string) $config['smtp_pass'];
        $mail->SMTPSecure = $secure === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 15;

        $debug = !empty($config['app_debug']);
        $mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        if ($debug) {
            $mail->Debugoutput = static function (string $line): void {
                app_log('SMTP: ' . $line);
            };
        }

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = PHPMailer::ENCODING_BASE64;

        $fromName = (string) ($config['from_name'] ?? '');
        $mail->setFrom((string) $config['from_email'], $fromName);
        $mail->addAddress((string) $config['to_email'], (string) ($config['to_name'] ?? ''));
        $mail->addReplyTo((string) $config['from_email'], $fromName);

        // Deliberately free of personal data so nothing sensitive appears in
        // inbox previews, subject lines or mail-server logs.
        $mail->Subject = 'New personal details submission';
        $mail->isHTML(true);
        $mail->Body = '<p>The submitted details are attached as a PDF.</p>'
            . '<p style="color:#64748b;font-size:12px">This message was generated automatically.</p>';
        $mail->AltBody = "The submitted details are attached as a PDF.\n\n"
            . "This message was generated automatically.";

        // addStringAttachment keeps the bytes in memory - no temp file is made.
        $mail->addStringAttachment(
            $pdf,
            'personal-details.pdf',
            PHPMailer::ENCODING_BASE64,
            'application/pdf'
        );

        $mail->send();
    } catch (MailException $e) {
        throw new RuntimeException('SMTP send failed: ' . $e->getMessage(), 0, $e);
    } finally {
        $mail->clearAttachments();
        $mail->smtpClose();
    }
}
