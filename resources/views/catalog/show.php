<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$mainImage = $images[0] ?? null;
$imageUrl = static fn (array $image): string => '/' . ltrim((string) $image['image_path'], '/');
$alt = static fn (array $image): string => trim((string) ($image['alt_text'] ?? ''));
?>
<div class="public-container product-detail">
    <nav class="breadcrumbs" aria-label="Putanja"><ol>
        <li><a href="/">Početna</a></li><li><a href="/klima-uredjaji">Klima uređaji</a></li>
        <li><a href="/kategorija/<?= $e(rawurlencode((string) $product['category_slug'])) ?>"><?= $e($product['category_name']) ?></a></li>
        <li><span aria-current="page"><?= $e($product['name']) ?></span></li>
    </ol></nav>
    <section class="product-hero" aria-labelledby="product-title">
        <div class="product-gallery" data-gallery>
            <?php if ($mainImage !== null): ?>
                <div class="product-gallery__main"><img data-gallery-main src="<?= $e($imageUrl($mainImage)) ?>" alt="<?= $e($alt($mainImage) ?: $product['name'] . ' klima uređaj') ?>"<?php if ((int)($mainImage['width'] ?? 0)>0): ?> width="<?= (int)$mainImage['width'] ?>"<?php endif; ?><?php if ((int)($mainImage['height'] ?? 0)>0): ?> height="<?= (int)$mainImage['height'] ?>"<?php endif; ?>></div>
                <?php if (count($images) > 1): ?><div class="product-gallery__thumbs" role="group" aria-label="Izaberite sliku proizvoda">
                    <?php foreach ($images as $index => $image): $imageAlt=$alt($image) ?: $product['name'] . ' — slika ' . ($index + 1); ?>
                        <button type="button" class="product-gallery__thumb<?= $index===0?' is-active':'' ?>" data-gallery-thumb data-src="<?= $e($imageUrl($image)) ?>" data-alt="<?= $e($imageAlt) ?>"<?php if ((int)($image['width']??0)>0): ?> data-width="<?= (int)$image['width'] ?>"<?php endif; ?><?php if ((int)($image['height']??0)>0): ?> data-height="<?= (int)$image['height'] ?>"<?php endif; ?> aria-label="Prikaži sliku <?= $index+1 ?>" aria-pressed="<?= $index===0?'true':'false' ?>"><img src="<?= $e($imageUrl($image)) ?>" alt=""<?php if ((int)($image['width']??0)>0): ?> width="<?= (int)$image['width'] ?>"<?php endif; ?><?php if ((int)($image['height']??0)>0): ?> height="<?= (int)$image['height'] ?>"<?php endif; ?> loading="lazy"></button>
                    <?php endforeach; ?>
                </div><?php endif; ?>
            <?php else: ?><div class="image-placeholder product-gallery__placeholder" role="img" aria-label="Slika proizvoda nije dostupna"><span aria-hidden="true">❄</span><span>Slika uskoro</span></div><?php endif; ?>
        </div>
        <div class="product-summary">
            <a class="product-summary__brand" href="/brend/<?= $e(rawurlencode((string) $product['brand_slug'])) ?>"><?= $e($product['brand_name']) ?></a>
            <h1 id="product-title"><?= $e($product['name']) ?></h1>
            <p class="product-summary__category"><a href="/kategorija/<?= $e(rawurlencode((string) $product['category_slug'])) ?>"><?= $e($product['category_name']) ?></a></p>
            <?php if (trim((string)($product['code']??''))!==''): ?><p class="product-summary__code">Model: <strong><?= $e($product['code']) ?></strong></p><?php endif; ?>
            <?php if (trim((string)($product['short_description']??''))!==''): ?><p class="product-summary__intro"><?= nl2br($e($product['short_description'])) ?></p><?php endif; ?>
            <p class="product-summary__price"><?= $product['price'] !== null ? number_format((float)$product['price'], 2, ',', '.') . ' RSD' : 'Cena na upit' ?></p>
            <p class="product-summary__price-caption">Za dodatne informacije o uređaju obratite se Frigo Sistemu.</p>
            <a class="button button--primary" href="/kontakt?product=<?= $e(rawurlencode((string) $product['slug'])) ?>">Pošaljite upit</a>
        </div>
    </section>
    <?php if (trim((string)($product['description']??''))!==''): ?><section class="content-section product-description" aria-labelledby="description-title"><div class="section-heading"><p class="eyebrow">Detalji</p><h2 id="description-title">Opis proizvoda</h2></div><div class="prose"><?= nl2br($e($product['description'])) ?></div></section><?php endif; ?>
    <?php if ($specifications !== []): ?><section class="content-section" aria-labelledby="specifications-title"><div class="section-heading"><p class="eyebrow">Karakteristike</p><h2 id="specifications-title">Tehničke specifikacije</h2></div><dl class="specification-list"><?php foreach ($specifications as $specification): ?><div><dt><?= $e($specification['name']) ?></dt><dd><?= $e($specification['value']) ?></dd></div><?php endforeach; ?></dl></section><?php endif; ?>
    <aside class="cta-panel" id="informacije" aria-labelledby="cta-title"><div><p class="eyebrow">Frigo Sistem</p><h2 id="cta-title">Zainteresovani ste za ovaj uređaj?</h2><p>Pošaljite nam upit i navedite šta vam je potrebno. Podaci o izabranom proizvodu biće automatski pridruženi poruci.</p></div><a class="button button--secondary" href="/kontakt?product=<?= $e(rawurlencode((string) $product['slug'])) ?>">Pošaljite upit</a></aside>
    <?php if ($relatedProducts !== []): ?><section class="content-section related-products" aria-labelledby="related-title"><div class="section-heading"><p class="eyebrow">Izdvajamo</p><h2 id="related-title">Slični proizvodi</h2></div><div class="product-grid"><?php foreach ($relatedProducts as $product) require __DIR__ . '/_card.php'; ?></div></section><?php endif; ?>
</div>
