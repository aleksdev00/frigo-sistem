<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$queryUrl = static function (string $path, array $state): string {
    $state = array_filter($state, static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== 1);
    return $path . ($state === [] ? '' : '?' . http_build_query($state, '', '&', PHP_QUERY_RFC3986));
};
$breadcrumbMiddle = $type === 'brand' ? 'Brendovi' : ($type === 'category' ? 'Kategorije' : null);
$hasFilters = $type === 'catalog' && ($filters['q'] !== '' || $filters['brand'] !== '' || $filters['category'] !== '');
?>
<div class="public-container catalog-shell">
    <nav class="breadcrumbs" aria-label="Putanja">
        <ol><li><a href="/">Početna</a></li><?php if ($breadcrumbMiddle): ?><li><span><?= $e($breadcrumbMiddle) ?></span></li><?php endif; ?><li><span aria-current="page"><?= $e($heading) ?></span></li></ol>
    </nav>
    <header class="page-intro">
        <p class="eyebrow"><?= $type === 'catalog' ? 'Naša ponuda' : 'Klima uređaji' ?></p>
        <h1><?= $e($heading) ?></h1>
        <?php if ($type === 'catalog'): ?><p>Pronađite klima uređaj prema nazivu, brendu ili kategoriji.</p><?php endif; ?>
        <?php if ($type === 'category' && trim((string) ($taxonomy['description'] ?? '')) !== ''): ?><p><?= nl2br($e($taxonomy['description'])) ?></p><?php endif; ?>
    </header>

    <?php if ($type === 'catalog'): ?>
    <form class="catalog-filters" action="/klima-uredjaji" method="get" role="search">
        <label class="field field--search"><span>Pretraga</span><input type="search" name="q" maxlength="100" value="<?= $e($filters['q']) ?>" placeholder="Naziv, brend ili model"></label>
        <label class="field"><span>Brend</span><select name="brand"><option value="">Svi brendovi</option><?php foreach ($brands as $brand): ?><option value="<?= $e($brand['slug']) ?>"<?= $filters['brand'] === $brand['slug'] ? ' selected' : '' ?>><?= $e($brand['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Kategorija</span><select name="category"><option value="">Sve kategorije</option><?php foreach ($categories as $category): ?><option value="<?= $e($category['slug']) ?>"<?= $filters['category'] === $category['slug'] ? ' selected' : '' ?>><?= $e($category['name']) ?></option><?php endforeach; ?></select></label>
        <button class="button button--primary" type="submit">Prikaži</button>
        <?php if ($hasFilters): ?><a class="button button--ghost" href="/klima-uredjaji">Poništi filtere</a><?php endif; ?>
    </form>
    <?php endif; ?>

    <div class="catalog-results-heading"><h2><?= $type === 'catalog' ? 'Klima uređaji' : 'Proizvodi' ?></h2><p class="result-count" aria-live="polite"><?= (int) $result['total'] ?> <?= (int) $result['total'] === 1 ? 'proizvod' : 'proizvoda' ?></p></div>
    <?php if ($result['items'] === []): ?>
        <section class="empty-state"><span class="empty-state__icon" aria-hidden="true">⌕</span><h2>Nema pronađenih proizvoda</h2><p>Promenite pojam pretrage ili uklonite neki od filtera.</p><a class="button button--outline" href="/klima-uredjaji">Prikaži celu ponudu</a></section>
    <?php else: ?>
        <div class="product-grid"><?php foreach ($result['items'] as $product) require __DIR__ . '/_card.php'; ?></div>
    <?php endif; ?>

    <?php if ((int) $result['pages'] > 1): ?>
    <nav class="pagination" aria-label="Stranice kataloga">
        <?php $current=(int)$result['page']; $pages=(int)$result['pages']; $state=$type==='catalog'?$filters:[]; ?>
        <a class="pagination__direction<?= $current <= 1 ? ' is-disabled' : '' ?>"<?= $current > 1 ? ' rel="prev" href="'.$e($queryUrl($basePath, [...$state, 'page'=>$current-1])).'"' : ' aria-disabled="true"' ?>>Prethodna</a>
        <?php for ($number=max(1,$current-2); $number<=min($pages,$current+2); $number++): ?><a href="<?= $e($queryUrl($basePath, [...$state, 'page'=>$number])) ?>"<?= $number===$current?' class="current" aria-current="page"':'' ?>><?= $number ?></a><?php endfor; ?>
        <a class="pagination__direction<?= $current >= $pages ? ' is-disabled' : '' ?>"<?= $current < $pages ? ' rel="next" href="'.$e($queryUrl($basePath, [...$state, 'page'=>$current+1])).'"' : ' aria-disabled="true"' ?>>Sledeća</a>
    </nav>
    <?php endif; ?>
</div>
