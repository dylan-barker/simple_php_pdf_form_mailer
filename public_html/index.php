<?php

declare(strict_types=1);

define('APP_RUNNING', true);

require __DIR__ . '/private/lib/bootstrap.php';

send_security_headers();

$csrfToken = csrf_token();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="robots" content="noindex, nofollow">
    <title>Your personal details</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="page">
    <main class="card">
        <h1>Your personal details</h1>

        <noscript>
            <p class="status status-error">
                JavaScript is turned off. The form will still work, but you will be
                taken to a separate confirmation page.
            </p>
        </noscript>

        <form id="details-form" action="submit.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="field" data-field="first_name">
                <label for="first_name">First name</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    maxlength="60"
                    autocomplete="given-name"
                    autocapitalize="words"
                    spellcheck="false"
                    required
                    aria-describedby="first_name-error"
                >
                <p class="field-error" id="first_name-error" data-error-for="first_name"></p>
            </div>

            <div class="field" data-field="surname">
                <label for="surname">Surname</label>
                <input
                    type="text"
                    id="surname"
                    name="surname"
                    maxlength="60"
                    autocomplete="family-name"
                    autocapitalize="words"
                    spellcheck="false"
                    required
                    aria-describedby="surname-error"
                >
                <p class="field-error" id="surname-error" data-error-for="surname"></p>
            </div>

            <div class="field" data-field="date_of_birth">
                <label for="date_of_birth">Date of birth</label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    min="1900-01-01"
                    max="<?= e($today) ?>"
                    autocomplete="bday"
                    required
                    aria-describedby="date_of_birth-error"
                >
                <p class="field-error" id="date_of_birth-error" data-error-for="date_of_birth"></p>
            </div>

            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" class="btn" id="submit-button">
                <span class="btn-label">Send details</span>
                <span class="spinner" aria-hidden="true"></span>
            </button>

            <div class="status" id="form-status" role="status" aria-live="polite"></div>
        </form>

        <section class="success-panel" id="success-panel" hidden>
            <div class="success-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none"
                     stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
            </div>
            <h2 tabindex="-1" id="success-heading">Details sent</h2>
            <p id="success-message">
                Thank you. Your details have been emailed to the records team.
            </p>
            <button type="button" class="btn btn-secondary" id="reset-button">
                Submit another response
            </button>
        </section>
    </main>
</div>
</body>
</html>
