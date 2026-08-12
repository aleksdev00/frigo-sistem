<?php
/** @var string $content */
/** @var string $title */
/** @var string $appName */
?>
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <p class="brand"><?= htmlspecialchars($appName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    </header>
    <main class="page"><?= $content ?></main>
    <footer class="site-footer"><small>Frigo Sistem</small></footer>
</body>
</html>
