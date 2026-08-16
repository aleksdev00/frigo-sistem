<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$hasImage = trim((string) ($product['image_path'] ?? '')) !== '';
$alt = trim((string) ($product['image_alt'] ?? '')) ?: (string) $product['name'] . ' klima uređaj';
$detailUrl = '/klima-uredjaji/' . rawurlencode((string) $product['slug']);
?>
<article class="product-card">
    <a class="product-card__media" href="<?= $e($detailUrl) ?>" tabindex="-1" aria-hidden="true">
        <?php if ($hasImage): ?>
            <img src="/<?= $e(ltrim((string) $product['image_path'], '/')) ?>" alt="<?= $e($alt) ?>"
                <?php if ((int) ($product['image_width'] ?? 0) > 0): ?>width="<?= (int) $product['image_width'] ?>"<?php endif; ?>
                <?php if ((int) ($product['image_height'] ?? 0) > 0): ?>height="<?= (int) $product['image_height'] ?>"<?php endif; ?> loading="lazy">
        <?php else: ?>
            <span class="image-placeholder image-placeholder--product" role="img" aria-label="Slika proizvoda nije dostupna"><span aria-hidden="true">❄</span><span>Slika uskoro</span></span>
        <?php endif; ?>
    </a>
    <div class="product-card__body">
        <div class="product-card__meta">
            <?php if ((int) $product['is_featured'] === 1): ?><span class="badge badge--accent">Izdvojeno</span><?php endif; ?>
            <a class="product-card__brand" href="/brend/<?= $e(rawurlencode((string) $product['brand_slug'])) ?>"><?= $e($product['brand_name']) ?></a>
        </div>
        <h2><a href="<?= $e($detailUrl) ?>"><?= $e($product['name']) ?></a></h2>
        <?php if (trim((string) ($product['code'] ?? '')) !== ''): ?><p class="product-card__code">Model: <?= $e($product['code']) ?></p><?php endif; ?>
        <?php if (trim((string) ($product['short_description'] ?? '')) !== ''): ?><p class="product-card__description"><?= $e($product['short_description']) ?></p><?php endif; ?>
        <div class="product-card__footer">
            <p class="product-card__price"><?= $product['price'] !== null ? number_format((float) $product['price'], 2, ',', '.') . ' RSD' : 'Cena na upit' ?></p>
            <a class="button button--outline" href="<?= $e($detailUrl) ?>" aria-label="Detaljnije o proizvodu <?= $e($product['name']) ?>">Detaljnije</a>
        </div>
    </div>
</article>
