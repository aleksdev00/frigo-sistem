<?php
/** @var string $content */
/** @var string $title */
/** @var string $appName */
$metaDescription = (string) ($metaDescription ?? 'Frigo Sistem Niš — prodaja, ugradnja i servis klima uređaja.');
$robots = (string) ($robots ?? 'index, follow');
$canonical = isset($canonical) ? (string) $canonical : null;
$openGraph = is_array($openGraph ?? null) ? $openGraph : [];
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
    <?php if ($openGraph !== []): ?>
    <meta property="og:type" content="product">
    <meta property="og:title" content="<?= $escape($openGraph['title'] ?? $title) ?>">
    <meta property="og:description" content="<?= $escape($openGraph['description'] ?? $metaDescription) ?>">
    <meta property="og:url" content="<?= $escape($openGraph['url'] ?? $canonical) ?>">
    <?php if (!empty($openGraph['image'])): ?><meta property="og:image" content="<?= $escape((string)($openGraph['baseUrl'] ?? '') . '/' . ltrim((string)$openGraph['image'], '/')) ?>"><?php endif; ?>
    <?php endif; ?>
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
    <?php if (isset($pageScript)): ?><script src="<?= $escape($pageScript) ?>" defer></script><?php endif; ?>
</body>
</html>
