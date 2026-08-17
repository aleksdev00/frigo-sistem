<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$services = [
    ['cart', 'Prodaja', 'Izbor klima uređaja prema vašem prostoru.'],
    ['install', 'Montaža', 'Profesionalna ugradnja i puštanje u rad.'],
    ['tools', 'Servis', 'Provera, otklanjanje problema i servis.'],
    ['settings', 'Održavanje', 'Redovno čišćenje i održavanje uređaja.'],
];
$icon = static function (string $name): string {
    $paths = [
        'cart' => '<path d="M3 4h2l1.4 9h10.8l2-6H6M9 17h.01M16 17h.01"/>',
        'install' => '<path d="M3 5h18v8H3zM7 17l3-4m7 4-3-4M8 9h8"/>',
        'tools' => '<path d="m14 6 4-4 4 4-4 4M3 21l9-9m-7-7 14 14M3 3l4 1-3 3z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1A8 8 0 0 0 15 6l-.3-2.6h-4L10.4 6A8 8 0 0 0 8 7.1l-2.4-1-2 3.4 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.4-1A8 8 0 0 0 10.4 18l.3 2.6h4L15 18a8 8 0 0 0 1.5-1.1l2.4 1 2-3.4-2-1.5a7 7 0 0 0 .1-1z"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
};
?>
<div class="home-page">
    <section class="home-hero" aria-labelledby="page-title">
        <div class="public-container home-hero__grid">
            <div class="home-hero__copy">
                <p class="eyebrow">FRIGO SISTEM</p>
                <h1 id="page-title">Klima za vaš dom.<br>Ugradnja bez brige.</h1>
                <p>Kompletna usluga izbora, prodaje, ugradnje i servisa klima uređaja za vaš prostor.</p>
                <div class="home-actions"><a class="button button--primary" href="/klima-uredjaji">Pogledajte klima uređaje</a><a class="button button--outline" href="/kontakt">Kontaktirajte nas</a></div>
            </div>
            <div class="home-hero__visual">
                <img src="/assets/images/home/hero-klima.webp" width="1536" height="1024"
                    alt="Moderna zidna klima ugrađena u savremenom enterijeru" fetchpriority="high">
            </div>
        </div>
    </section>

    <section class="trust-strip" aria-label="Usluge Frigo Sistema"><div class="public-container trust-strip__grid">
        <?php foreach ($services as [$iconName, $name, $description]): ?><article><span class="line-icon"><?= $icon($iconName) ?></span><div><h2><?= $e($name === 'Prodaja' ? 'Prodaja klima' : ($name === 'Montaža' ? 'Stručna montaža' : $name)) ?></h2><p><?= $e($description) ?></p></div></article><?php endforeach; ?>
    </div></section>

    <section class="home-section public-container" aria-labelledby="featured-title">
        <div class="home-section__heading"><div><p class="eyebrow">Ponuda</p><h2 id="featured-title">Izdvojeni klima uređaji</h2></div><a href="/klima-uredjaji">Pogledajte sve <span aria-hidden="true">→</span></a></div>
        <?php if ($featuredProducts !== []): ?><div class="product-grid home-products"><?php foreach ($featuredProducts as $product) require __DIR__ . '/catalog/_card.php'; ?></div>
        <?php else: ?><p class="home-empty">Izdvojeni uređaji trenutno nisu dostupni. Pogledajte kompletnu ponudu klima uređaja.</p><?php endif; ?>
    </section>

    <section class="home-section home-why public-container" aria-labelledby="why-title">
        <div class="home-why__copy"><p class="eyebrow">Why FRIGO SISTEM</p><h2 id="why-title">Kompletno rešenje za klimatizaciju</h2><p>Od izbora odgovarajućeg uređaja do prodaje, montaže, servisa i redovnog održavanja.</p><ul><li>Izbor uređaja</li><li>Prodaja</li><li>Montaža</li><li>Servis</li><li>Održavanje</li></ul><a class="button button--primary" href="/kontakt">Kontaktirajte nas</a></div>
        <div class="home-why__visual"><img src="/assets/images/home/montaza-klime.webp" width="1536" height="1024"
            alt="Serviser klima uređaja tokom rada na zidnoj klimi" loading="lazy" decoding="async"></div>
    </section>

    <section class="home-section home-services public-container" aria-labelledby="services-title"><p class="eyebrow">Podrška za vaš prostor</p><h2 id="services-title">Naše usluge</h2><div class="service-cards">
        <?php foreach ($services as [$iconName, $name, $description]): ?><article><span class="line-icon"><?= $icon($iconName) ?></span><h3><?= $e($name) ?></h3><p><?= $e($description) ?></p></article><?php endforeach; ?>
    </div></section>

    <?php if ($brands !== []): ?><section class="home-section home-brands public-container" aria-labelledby="brands-title"><p class="eyebrow">Aktivna ponuda</p><h2 id="brands-title">Brendovi u ponudi</h2><div class="brand-list"><?php foreach ($brands as $brand): ?><a href="/brend/<?= $e(rawurlencode((string) $brand['slug'])) ?>"><?= $e($brand['name']) ?></a><?php endforeach; ?></div></section><?php endif; ?>

    <section class="home-section home-process public-container" aria-labelledby="process-title"><p class="eyebrow">Jednostavan proces</p><h2 id="process-title">Od izbora klime do montaže</h2><ol><li><span>01</span><div><h3>Izaberite uređaj</h3><p>Pregledajte ponudu klima uređaja.</p></div></li><li><span>02</span><div><h3>Pošaljite upit</h3><p>Kontaktirajte nas za informacije i dogovor.</p></div></li><li><span>03</span><div><h3>Dogovor i montaža</h3><p>Dogovaramo termin i realizaciju montaže.</p></div></li></ol></section>

    <section class="home-cta"><div class="public-container"><div><h2>Niste sigurni koja klima vam odgovara?</h2><p>Kontaktirajte nas i pomoći ćemo vam da izaberete uređaj koji odgovara vašem prostoru.</p></div><a class="button button--secondary" href="/kontakt">Kontaktirajte nas</a></div></section>
</div>
