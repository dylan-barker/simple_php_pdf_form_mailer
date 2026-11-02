<?php
/**
 * PDF layout for dompdf.
 *
 * Inline CSS only: dompdf does not load external stylesheets, and its CSS
 * support is limited. Tables are used for alignment because flexbox and grid
 * are unreliable in the renderer.
 *
 * @var array<string, mixed> $data
 * @var array<string, mixed> $config
 * @var DateTimeImmutable $generatedAt
 */

$appName = (string) ($config['app_name'] ?? 'Personal Details');
$fullName = trim((string) $data['first_name'] . ' ' . (string) $data['surname']);
$dobLabel = $data['dob'] instanceof DateTimeImmutable ? $data['dob']->format('j F Y') : '';
$ageLabel = $data['age'] !== null ? (string) $data['age'] . ' years' : '';
$generatedLabel = $generatedAt->format('j F Y \a\t H:i T');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Personal details</title>
<style>
    @page {
        margin: 0;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 11pt;
        line-height: 1.5;
        color: #1e293b;
    }

    .sheet {
        padding: 24mm 20mm 18mm 20mm;
    }

    .masthead {
        width: 100%;
        border-collapse: collapse;
    }

    .masthead td {
        vertical-align: bottom;
        padding: 0;
    }

    .brand {
        font-size: 10pt;
        font-weight: bold;
        letter-spacing: 1.6pt;
        text-transform: uppercase;
        color: #0b2545;
    }

    .doc-type {
        font-size: 8pt;
        letter-spacing: 1.2pt;
        text-transform: uppercase;
        color: #94a3b8;
        text-align: right;
    }

    .rule {
        margin: 4mm 0 12mm 0;
        height: 2pt;
        background-color: #0b2545;
    }

    .eyebrow {
        margin: 0 0 3mm 0;
        font-size: 8pt;
        letter-spacing: 1.4pt;
        text-transform: uppercase;
        color: #64748b;
    }

    h1 {
        margin: 0 0 3mm 0;
        font-size: 22pt;
        line-height: 1.2;
        color: #0b2545;
    }

    .lede {
        margin: 0 0 10mm 0;
        font-size: 10.5pt;
        color: #475569;
    }

    table.details {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 10mm 0;
    }

    table.details th,
    table.details td {
        padding: 4.5mm 5mm;
        text-align: left;
        vertical-align: top;
        border-bottom: 0.6pt solid #e2e8f0;
    }

    table.details th {
        width: 38%;
        font-size: 8.5pt;
        font-weight: bold;
        letter-spacing: 1pt;
        text-transform: uppercase;
        color: #64748b;
        background-color: #f8fafc;
    }

    table.details td {
        font-size: 12pt;
        color: #0f172a;
    }

    .notice {
        padding: 5mm;
        border-left: 3pt solid #b45309;
        background-color: #fffbeb;
        font-size: 9pt;
        color: #78350f;
    }

    .footer {
        margin-top: 10mm;
        padding-top: 4mm;
        border-top: 0.6pt solid #e2e8f0;
        font-size: 8pt;
        color: #94a3b8;
    }

    .footer strong {
        color: #64748b;
    }
</style>
</head>
<body>
<div class="sheet">
    <table class="masthead">
        <tr>
            <td class="brand"><?= e($appName) ?></td>
            <td class="doc-type">Confidential record</td>
        </tr>
    </table>

    <div class="rule"></div>

    <p class="eyebrow">Online form submission</p>
    <h1>Personal details</h1>
    <p class="lede">
        The details below were submitted through the online form and are reproduced
        here for your records.
    </p>

    <table class="details">
        <tr>
            <th>First name</th>
            <td><?= e((string) $data['first_name']) ?></td>
        </tr>
        <tr>
            <th>Surname</th>
            <td><?= e((string) $data['surname']) ?></td>
        </tr>
        <tr>
            <th>Date of birth</th>
            <td><?= e($dobLabel) ?></td>
        </tr>
        <tr>
            <th>Age</th>
            <td><?= e($ageLabel) ?></td>
        </tr>
    </table>

    <div class="notice">
        This document contains personal information. Store it securely and handle it
        in line with your organisation's data protection policy.
    </div>

    <div class="footer">
        Generated automatically on <?= e($generatedLabel) ?> for
        <strong><?= e($fullName) ?></strong>.
        This file was not stored on the server.
    </div>
</div>
</body>
</html>
