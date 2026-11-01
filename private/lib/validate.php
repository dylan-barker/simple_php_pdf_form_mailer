<?php

declare(strict_types=1);

if (!defined('APP_RUNNING')) {
    http_response_code(404);
    exit;
}

/**
 * Validate the submitted form.
 *
 * @param array<string, mixed> $input
 * @return array{data: array<string, mixed>, errors: array<string, string>}
 */
function validate_submission(array $input): array
{
    $data = [
        'first_name'    => '',
        'surname'       => '',
        'date_of_birth' => '',
        'dob'           => null,
        'age'           => null,
    ];

    $errors = [];
    $namePattern = "/^[\p{L}\p{M}][\p{L}\p{M}' \-]*$/u";

    $fields = [
        'first_name' => ['label' => 'first name', 'max' => 60],
        'surname'    => ['label' => 'surname', 'max' => 60],
    ];

    foreach ($fields as $field => $rules) {
        $value = $input[$field] ?? '';
        $value = is_string($value) ? trim($value) : '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        if ($value === '') {
            $errors[$field] = 'Please enter your ' . $rules['label'] . '.';
            continue;
        }

        if (mb_strlen($value) > $rules['max']) {
            $errors[$field] = 'Please keep your ' . $rules['label'] . ' under '
                . $rules['max'] . ' characters.';
            continue;
        }

        if (preg_match($namePattern, $value) !== 1) {
            $errors[$field] = 'Please use letters, spaces, hyphens or apostrophes only.';
            continue;
        }

        $data[$field] = $value;
    }

    $rawDob = $input['date_of_birth'] ?? '';
    $rawDob = is_string($rawDob) ? trim($rawDob) : '';

    if ($rawDob === '') {
        $errors['date_of_birth'] = 'Please enter your date of birth.';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDob) !== 1) {
        $errors['date_of_birth'] = 'Please use the date picker to choose your date of birth.';
    } else {
        $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $rawDob);
        $parseErrors = DateTimeImmutable::getLastErrors();
        $malformed = is_array($parseErrors)
            && (($parseErrors['warning_count'] ?? 0) > 0 || ($parseErrors['error_count'] ?? 0) > 0);

        $earliest = new DateTimeImmutable('1900-01-01');
        $today = new DateTimeImmutable('today');

        if ($dob === false || $malformed || $dob->format('Y-m-d') !== $rawDob) {
            $errors['date_of_birth'] = 'That date does not exist. Please check it and try again.';
        } elseif ($dob < $earliest) {
            $errors['date_of_birth'] = 'Please enter a date on or after 1 January 1900.';
        } elseif ($dob > $today) {
            $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
        } else {
            $data['date_of_birth'] = $rawDob;
            $data['dob'] = $dob;
            $data['age'] = $dob->diff($today)->y;
        }
    }

    return ['data' => $data, 'errors' => $errors];
}

/** True when the hidden honeypot field was filled in. */
function honeypot_triggered(array $input): bool
{
    $value = $input['website'] ?? '';

    return is_string($value) && trim($value) !== '';
}
