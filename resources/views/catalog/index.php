<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$queryUrl = static function (string $path, array $state): string {
    $state = array_filter($state, static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== 1);
    return $path . ($state === [] ? '' : '?' . http_build_query($state, '', '&', PHP_QUERY_RFC3986));
};
$breadcrumbMiddle = $type === 'brand' ? 'Brend' : ($type === 'category' ? 'Kategorija' : null);
?>
<div class="catalog-shell">
    <nav class="breadcrumbs" aria-label="Putanja">
        <ol><li><a href="/">Početna</a></li><?php if ($breadcrumbMiddle): ?><li><?= $e($breadcrumbMiddle) ?></li><?php endif; ?><li aria-current="page"><?= $e($heading) ?></li></ol>
    </nav>
    <header class="catalog-heading">
        <p class="eyebrow"><?= $type === 'catalog' ? 'Ponuda' : 'Klima uređaji' ?></p>
        <h1><?= $e($heading) ?></h1>
        <?php if ($type === 'category' && trim((string) ($taxonomy['description'] ?? '')) !== ''): ?><p><?= nl2br($e($taxonomy['description'])) ?></p><?php endif; ?>
    </header>

    <?php if ($type === 'catalog'): ?>
    <form class="catalog-filters" action="/klima-uredjaji" method="get" role="search">
        <label>Pretraga <input type="search" name="q" maxlength="100" value="<?= $e($filters['q']) ?>" placeholder="Naziv, brend ili model"></label>
        <label>Brend <select name="brand"><option value="">Svi brendovi</option><?php foreach ($brands as $brand): ?><option value="<?= $e($brand['slug']) ?>"<?= $filters['brand'] === $brand['slug'] ? ' selected' : '' ?>><?= $e($brand['name']) ?></option><?php endforeach; ?></select></label>
        <label>Kategorija <select name="category"><option value="">Sve kategorije</option><?php foreach ($categories as $category): ?><option value="<?= $e($category['slug']) ?>"<?= $filters['category'] === $category['slug'] ? ' selected' : '' ?>><?= $e($category['name']) ?></option><?php endforeach; ?></select></label>
        <button type="submit">Prikaži</button><a class="filter-reset" href="/klima-uredjaji">Poništi</a>
    </form>
    <?php endif; ?>

    <p class="result-count"><?= (int) $result['total'] ?> <?= (int) $result['total'] === 1 ? 'proizvod' : 'proizvoda' ?></p>
    <?php if ($result['items'] === []): ?>
        <section class="catalog-empty"><h2>Nema pronađenih proizvoda</h2><p>Promenite pojam pretrage ili uklonite neki od filtera.</p><a href="/klima-uredjaji">Prikaži celu ponudu</a></section>
    <?php else: ?>
        <div class="product-grid"><?php foreach ($result['items'] as $product) require __DIR__ . '/_card.php'; ?></div>
    <?php endif; ?>

    <?php if ((int) $result['pages'] > 1): ?>
    <nav class="catalog-pagination" aria-label="Stranice kataloga">
        <?php $current=(int)$result['page']; $pages=(int)$result['pages']; $state=$type==='catalog'?$filters:[]; ?>
        <?php if ($current > 1): ?><a rel="prev" href="<?= $e($queryUrl($basePath, [...$state, 'page'=>$current-1])) ?>">Prethodna</a><?php endif; ?>
        <?php for ($number=max(1,$current-2); $number<=min($pages,$current+2); $number++): ?><a href="<?= $e($queryUrl($basePath, [...$state, 'page'=>$number])) ?>"<?= $number===$current?' class="current" aria-current="page"':'' ?>><?= $number ?></a><?php endfor; ?>
        <?php if ($current < $pages): ?><a rel="next" href="<?= $e($queryUrl($basePath, [...$state, 'page'=>$current+1])) ?>">Sledeća</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
