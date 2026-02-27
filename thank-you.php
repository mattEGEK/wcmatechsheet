<?php
$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : require __DIR__ . '/config.example.php';
$siteName = $config['site_name'] ?? 'NASCC Tech Sheet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="assets/css/form.css">
    <style>
        .thank-you { text-align: center; padding: 3rem 1.5rem; }
        .thank-you h1 { color: #0a0; margin-bottom: 1rem; }
        .thank-you p { font-size: 1.1rem; }
        .thank-you a { display: inline-block; margin-top: 1.5rem; padding: 0.6rem 1.2rem; background: #c00; color: #fff; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="thank-you">
        <h1>Thank You</h1>
        <p>Your tech sheet has been submitted successfully.</p>
        <p>A copy has been sent to the tech director and to your email.</p>
        <a href="index.php">Submit Another Form</a>
    </div>
</body>
</html>
