<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$hasImage = trim((string) ($product['image_path'] ?? '')) !== '';
$alt = trim((string) ($product['image_alt'] ?? '')) ?: (string) $product['name'] . ' klima uređaj';
?>
<article class="product-card">
    <div class="product-card__media">
        <?php if ($hasImage): ?>
            <img src="/<?= $e(ltrim((string) $product['image_path'], '/')) ?>" alt="<?= $e($alt) ?>"
                <?php if ((int) ($product['image_width'] ?? 0) > 0): ?>width="<?= (int) $product['image_width'] ?>"<?php endif; ?>
                <?php if ((int) ($product['image_height'] ?? 0) > 0): ?>height="<?= (int) $product['image_height'] ?>"<?php endif; ?> loading="lazy">
        <?php else: ?>
            <div class="product-card__placeholder" role="img" aria-label="Slika proizvoda nije dostupna">Frigo Sistem</div>
        <?php endif; ?>
    </div>
    <div class="product-card__body">
        <?php if ((int) $product['is_featured'] === 1): ?><span class="catalog-badge">Izdvojeno</span><?php endif; ?>
        <p class="product-card__taxonomy"><a href="/brend/<?= $e($product['brand_slug']) ?>"><?= $e($product['brand_name']) ?></a> · <a href="/kategorija/<?= $e($product['category_slug']) ?>"><?= $e($product['category_name']) ?></a></p>
        <h2><?= $e($product['name']) ?></h2>
        <?php if (trim((string) ($product['code'] ?? '')) !== ''): ?><p class="product-card__code">Model: <?= $e($product['code']) ?></p><?php endif; ?>
        <?php if (trim((string) ($product['short_description'] ?? '')) !== ''): ?><p class="product-card__description"><?= $e($product['short_description']) ?></p><?php endif; ?>
        <?php if ($product['price'] !== null): ?><p class="product-card__price"><?= number_format((float) $product['price'], 2, ',', '.') ?> RSD</p><?php endif; ?>
        <span class="button disabled" aria-disabled="true" title="Detaljna stranica biće dostupna u sledećoj fazi">Detaljnije — uskoro</span>
    </div>
</article>
