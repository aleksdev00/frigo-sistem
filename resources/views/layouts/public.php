<?php
/** @var string $content */
/** @var string $title */
/** @var string $appName */
$metaDescription = (string) ($metaDescription ?? 'Frigo Sistem Niš — prodaja, ugradnja i servis klima uređaja.');
$robots = (string) ($robots ?? 'index, follow');
$canonical = isset($canonical) ? (string) $canonical : null;
$openGraph = is_array($openGraph ?? null) ? $openGraph : [];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$catalogCurrent = $requestPath === '/klima-uredjaji'
    || str_starts_with($requestPath, '/klima-uredjaji/')
    || str_starts_with($requestPath, '/brend/')
    || str_starts_with($requestPath, '/kategorija/');
$contactCurrent = $requestPath === '/kontakt';
?>
<!doctype html>
<html lang="sr-Latn">
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
    <script src="/assets/js/public.js" defer></script>
</head>
<body class="public-site">
    <a class="skip-link" href="#main-content">Pređi na sadržaj</a>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="/" aria-label="Frigo Sistem — početna">
                <span class="brand__mark" aria-hidden="true">FS</span>
                <span class="brand__text">FRIGO SISTEM</span>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle>
                <span class="nav-toggle__icon" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="sr-only">Otvori glavni meni</span>
            </button>
            <nav class="primary-nav" id="primary-navigation" aria-label="Glavna navigacija" data-navigation>
                <a href="/"<?= $requestPath === '/' ? ' aria-current="page"' : '' ?>>Početna</a>
                <a href="/klima-uredjaji"<?= $catalogCurrent ? ' aria-current="page"' : '' ?>>Klima uređaji</a>
                <a href="/kontakt"<?= $contactCurrent ? ' aria-current="page"' : '' ?>>Kontakt</a>
            </nav>
        </div>
    </header>
    <main class="page" id="main-content" tabindex="-1"><?= $content ?></main>
    <footer class="site-footer">
        <div class="site-footer__inner">
            <div><a class="footer-brand" href="/">FRIGO SISTEM</a><p>Prodaja, ugradnja i servis klima uređaja.</p></div>
            <nav aria-label="Navigacija u podnožju"><h2>Navigacija</h2><a href="/">Početna</a><a href="/klima-uredjaji">Klima uređaji</a><a href="/kontakt">Kontakt</a></nav>
        </div>
        <div class="site-footer__bottom"><small>&copy; <?= date('Y') ?> Frigo Sistem</small></div>
    </footer>
    <?php if (isset($pageScript)): ?><script src="<?= $escape($pageScript) ?>" defer></script><?php endif; ?>
</body>
</html>
