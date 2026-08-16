<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$json = static fn (array $value): string => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$mainImage = $images[0] ?? null;
$imageUrl = static fn (array $image): string => '/' . ltrim((string) $image['image_path'], '/');
$alt = static fn (array $image): string => trim((string) ($image['alt_text'] ?? ''));
?>
<div class="product-detail">
    <nav class="breadcrumbs" aria-label="Putanja"><ol>
        <li><a href="/">Početna</a></li><li><a href="/klima-uredjaji">Klima uređaji</a></li>
        <li><a href="/kategorija/<?= $e($product['category_slug']) ?>"><?= $e($product['category_name']) ?></a></li>
        <li aria-current="page"><?= $e($product['name']) ?></li>
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
            <?php else: ?><div class="product-gallery__placeholder" role="img" aria-label="Slika proizvoda nije dostupna">Frigo Sistem</div><?php endif; ?>
        </div>
        <div class="product-summary">
            <p class="product-summary__taxonomy"><a href="/brend/<?= $e($product['brand_slug']) ?>"><?= $e($product['brand_name']) ?></a> · <a href="/kategorija/<?= $e($product['category_slug']) ?>"><?= $e($product['category_name']) ?></a></p>
            <h1 id="product-title"><?= $e($product['name']) ?></h1>
            <?php if (trim((string)($product['code']??''))!==''): ?><p class="product-summary__code">Model: <?= $e($product['code']) ?></p><?php endif; ?>
            <?php if (trim((string)($product['short_description']??''))!==''): ?><p class="product-summary__intro"><?= nl2br($e($product['short_description'])) ?></p><?php endif; ?>
            <?php if ($product['price'] !== null): ?><p class="product-summary__price"><?= number_format((float)$product['price'], 2, ',', '.') ?> RSD</p><?php else: ?><p class="product-summary__price-note">Cena na upit</p><?php endif; ?>
            <div class="product-inquiry" id="upit"><h2>Informacije o proizvodu</h2><p>Pošaljite upit za <?= $e($product['name']) ?>. Proizvod će automatski biti prosleđen uz vaš upit.</p><a class="button" href="/kontakt?proizvod=<?= $e(rawurlencode((string)$product['slug'])) ?>">Pošaljite upit</a></div>
        </div>
    </section>
    <?php if (trim((string)($product['description']??''))!==''): ?><section class="product-section product-description" aria-labelledby="description-title"><h2 id="description-title">Opis proizvoda</h2><div><?= nl2br($e($product['description'])) ?></div></section><?php endif; ?>
    <?php if ($specifications !== []): ?><section class="product-section" aria-labelledby="specifications-title"><h2 id="specifications-title">Tehničke specifikacije</h2><dl class="specification-list"><?php foreach ($specifications as $specification): ?><div><dt><?= $e($specification['name']) ?></dt><dd><?= $e($specification['value']) ?></dd></div><?php endforeach; ?></dl></section><?php endif; ?>
    <?php if ($relatedProducts !== []): ?><section class="product-section related-products" aria-labelledby="related-title"><h2 id="related-title">Slični proizvodi</h2><div class="product-grid"><?php foreach ($relatedProducts as $product) require __DIR__ . '/_card.php'; ?></div></section><?php endif; ?>
</div>
<script type="application/ld+json"><?= $json($structuredProduct) ?></script>
<script type="application/ld+json"><?= $json(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>array_map(static fn(array $item,int $index):array=>['@type'=>'ListItem','position'=>$index+1,'name'=>$item['name'],'item'=>$item['url']],$structuredBreadcrumbs,array_keys($structuredBreadcrumbs))]) ?></script>
<?php $pageScript = '/assets/js/product-gallery.js'; ?>
