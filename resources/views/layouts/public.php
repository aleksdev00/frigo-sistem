<?php
/** @var string $content */
/** @var string $title */
/** @var string $appName */
$metaDescription = (string) ($metaDescription ?? 'Frigo Sistem Niš — prodaja, ugradnja i servis klima uređaja.');
$robots = (string) ($robots ?? 'index, follow');
$canonical = isset($canonical) ? (string) $canonical : null;
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($title) ?></title>
    <meta name="description" content="<?= $escape($metaDescription) ?>">
    <meta name="robots" content="<?= $escape($robots) ?>">
    <?php if ($canonical !== null): ?><link rel="canonical" href="<?= $escape($canonical) ?>"><?php endif; ?>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/"><?= $escape($appName) ?></a>
        <nav aria-label="Glavna navigacija">
            <a href="/">Početna</a>
            <a href="/klima-uredjaji">Klima uređaji</a>
        </nav>
    </header>
    <main class="page"><?= $content ?></main>
    <footer class="site-footer"><small>Frigo Sistem</small></footer>
</body>
</html>
