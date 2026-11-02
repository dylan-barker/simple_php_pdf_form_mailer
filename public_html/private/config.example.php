<?php
/**
 * Copy this file to config.php in this same folder and fill in your values.
 * The private/ directory lives inside public_html but is blocked from HTTP
 * access by its own .htaccess. Never commit config.php - it contains your SMTP
 * password.
 */
return [
    // ---- SMTP -------------------------------------------------------------
    // Ask your mail provider for these. Port 587 with "tls" or 465 with "ssl".
    // Plaintext port 25 is deliberately unsupported.
    'smtp_host'   => 'smtp.example.com',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',          // "tls" (STARTTLS) or "ssl" (SMTPS)
    'smtp_user'   => 'mailer@example.com',
    'smtp_pass'   => 'CHANGE_ME',

    // ---- Envelope ---------------------------------------------------------
    // Must be a mailbox your SMTP provider lets you send as.
    'from_email'  => 'mailer@example.com',
    'from_name'   => 'Personal Details Form',
    'to_email'    => 'recipient@example.com',
    'to_name'     => 'Records Team',

    // ---- Presentation -----------------------------------------------------
    'app_name'    => 'Personal Details',
    'timezone'    => 'Europe/London',

    // Optional: password-protect the PDF attachment. Leave empty to disable.
    // Anyone opening the attachment must enter this password.
    'pdf_password' => '',

    // ---- Diagnostics ------------------------------------------------------
    // Set true only while debugging locally. Never leave it on in production:
    // it turns on verbose SMTP output.
    'app_debug'   => false,

    // Optional override for the scratch directory used by dompdf.
    // Defaults to private/tmp, which is blocked from HTTP access.
    'storage_dir' => null,
];
