<?php

declare(strict_types=1);

define('APP_RUNNING', true);

require __DIR__ . '/private/lib/bootstrap.php';
require APP_PRIVATE . '/lib/validate.php';
require APP_PRIVATE . '/lib/pdf.php';
require APP_PRIVATE . '/lib/mailer.php';

send_security_headers();

/**
 * Send a response as JSON for fetch/XHR callers, or as a page otherwise.
 *
 * @param array<string, string> $errors
 * @param array<string, mixed>  $extra
 */
function respond(bool $ok, string $message, array $errors = [], int $status = 200, array $extra = []): never
{
    http_response_code($status);

    if (wants_json()) {
        header('Content-Type: application/json; charset=utf-8');

        $payload = array_merge(['ok' => $ok, 'message' => $message], $extra);
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');

    $template = APP_PRIVATE . '/templates/response.php';
    if (!is_file($template)) {
        echo '<!DOCTYPE html><p>' . e($message) . '</p>';
        exit;
    }

    require $template;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(false, 'This page only accepts form submissions.', [], 405);
}

// Honeypot: behave exactly like a success without sending anything.
if (honeypot_triggered($_POST)) {
    respond(true, 'Thank you. Your details have been sent.');
}

$submittedToken = $_POST['csrf_token'] ?? null;
if (!csrf_valid(is_string($submittedToken) ? $submittedToken : null)) {
    respond(
        false,
        'Your session has expired. Please reload the page and try again.',
        [],
        419
    );
}

$result = validate_submission($_POST);
$data = $result['data'];
$errors = $result['errors'];

if ($errors !== []) {
    respond(false, 'Please check the highlighted fields and try again.', $errors, 422);
}

try {
    // The PDF exists only as this string. No path, no temp PDF, no cleanup.
    $pdf = build_pdf($data, $config);

    send_pdf_email($pdf, $data, $config);

    // Free the bytes as soon as they are no longer needed.
    unset($pdf);

    $newToken = csrf_rotate();

    respond(true, 'Thank you. Your details have been emailed to the records team.', [], 200, [
        'token' => $newToken,
    ]);
} catch (Throwable $e) {
    app_log_exception($e, [
        (string) $data['first_name'],
        (string) $data['surname'],
        (string) $data['date_of_birth'],
    ]);

    respond(
        false,
        'Sorry, we could not send your details just now. Please try again in a few minutes.',
        [],
        500
    );
}
