<?php
/**
 * Plain HTML response for browsers without JavaScript.
 *
 * @var bool                $ok
 * @var string              $message
 * @var array<string,string> $errors
 */

$title = $ok ? 'Details sent' : 'Submission problem';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
    <main class="card card-narrow">
        <p class="eyebrow"><?= $ok ? 'Complete' : 'Not sent' ?></p>
        <h1><?= e($title) ?></h1>

        <div class="status <?= $ok ? 'status-success' : 'status-error' ?>">
            <?= e($message) ?>
        </div>

        <?php if (!$ok && $errors !== []): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="privacy-note">
            <?php if ($ok): ?>
                Your details were generated in memory and emailed as an attachment.
                Nothing was saved on this server.
            <?php else: ?>
                No PDF was generated and no email was sent.
            <?php endif; ?>
        </p>

        <a class="btn btn-secondary btn-link" href="index.php">Back to the form</a>
    </main>
</div>
</body>
</html>
