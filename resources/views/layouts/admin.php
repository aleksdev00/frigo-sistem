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
    <meta name="robots" content="noindex,nofollow">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — <?= htmlspecialchars($appName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/admin.js" defer></script>
</head>
<body>
    <?php if (!empty($showAdminNav)): ?>
    <header class="admin-header"><a class="admin-brand" href="/admin">Frigo Sistem</a><nav aria-label="Admin navigation"><a href="/admin">Dashboard</a><a href="/admin/products">Products</a><a href="/admin/brands">Brands</a><a href="/admin/categories">Categories</a></nav><form method="post" action="/admin/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><button class="link-button" type="submit">Logout</button></form></header>
    <?php endif; ?>
    <main class="<?= !empty($showAdminNav) ? 'admin-page' : 'page' ?>"><?= $content ?></main>
</body>
</html>
