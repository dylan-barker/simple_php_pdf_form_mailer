<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(404);
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Render a template from templates/ with the given variables.
 *
 * @param array<string, mixed> $vars
 */
function render_template(string $name, array $vars): string
{
    $path = APP_PRIVATE . '/templates/' . $name;

    if (!is_file($path)) {
        throw new RuntimeException('Template not found: ' . $name);
    }

    extract($vars, EXTR_SKIP);

    ob_start();
    try {
        require $path;
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }

    return (string) ob_get_clean();
}

/**
 * Build the PDF and return it as a string. Nothing is written to disk: the
 * bytes live in this variable and are handed straight to PHPMailer.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed> $config
 */
function build_pdf(array $data, array $config): string
{
    $scratch = create_scratch_dir();

    $options = new Options();
    $options->setIsRemoteEnabled(false);
    $options->setIsPhpEnabled(false);
    $options->setIsJavascriptEnabled(false);
    // Subsetting keeps the attachment small (~20 KB) and, in testing, writes
    // nothing to the scratch directory. The scratch directory is still purged
    // as a precaution.
    $options->setIsFontSubsettingEnabled(true);
    $options->setChroot(APP_PRIVATE);
    $options->setDefaultFont('DejaVu Sans');
    $options->setDefaultMediaType('print');
    $options->setDpi(150);
    $options->setTempDir($scratch);
    $options->setDebugKeepTemp(false);

    $generatedAt = new DateTimeImmutable('now');

    $html = render_template('pdf.php', [
        'data'        => $data,
        'config'      => $config,
        'generatedAt' => $generatedAt,
    ]);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');

    // setPaper() rebuilds the canvas when the size changes, so encryption must
    // be applied afterwards. It is still set before render() so every content
    // stream is encrypted, not just the trailer.
    $password = $config['pdf_password'] ?? '';
    if (is_string($password) && $password !== '') {
        $dompdf->getCanvas()->get_cpdf()->setEncryption($password, $password, []);
    }

    $dompdf->render();

    $pdf = $dompdf->output();

    if (!is_string($pdf) || !str_starts_with($pdf, '%PDF-')) {
        throw new RuntimeException('PDF generation produced unexpected output.');
    }

    return $pdf;
}
