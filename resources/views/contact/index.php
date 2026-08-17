<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$errorFor = static fn (string $field): ?string => is_string($errors[$field] ?? null) ? $errors[$field] : null;
?>
<div class="public-container contact-page">
    <nav class="breadcrumbs" aria-label="Putanja"><ol><li><a href="/">Početna</a></li><li><span aria-current="page">Kontakt</span></li></ol></nav>
    <header class="page-intro"><p class="eyebrow">Frigo Sistem</p><h1>Kontaktirajte nas</h1><p>Pošaljite upit o klima uređajima, ugradnji ili servisu. Odgovorićemo vam putem ostavljenog kontakta.</p></header>

    <?php if ($sent): ?><div class="form-notice form-notice--success" role="status"><strong>Poruka je uspešno poslata.</strong><span>Hvala što ste kontaktirali Frigo Sistem.</span></div><?php endif; ?>
    <?php if ($globalError !== null): ?><div class="form-notice form-notice--error" role="alert"><strong>Poruka nije poslata.</strong><span><?= $e($globalError) ?></span></div><?php endif; ?>

    <div class="contact-layout">
        <section class="contact-card" aria-labelledby="contact-form-title">
            <h2 id="contact-form-title"><?= $product === null ? 'Pošaljite upit' : 'Upit za proizvod' ?></h2>
            <?php if ($product !== null): ?>
                <div class="product-context" aria-label="Izabrani proizvod">
                    <strong><?= $e($product['name']) ?></strong>
                    <?php if (trim((string) ($product['code'] ?? '')) !== ''): ?><span>Model: <?= $e($product['code']) ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
            <p class="required-note"><span aria-hidden="true">*</span> Obavezna polja. Potrebno je navesti email ili telefon.</p>
            <form class="contact-form" action="/kontakt" method="post" novalidate>
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="_form_started" value="<?= $e($antiSpam['started']) ?>">
                <input type="hidden" name="_form_signature" value="<?= $e($antiSpam['signature']) ?>">
                <?php if ($product !== null): ?><input type="hidden" name="product" value="<?= $e($product['slug']) ?>"><?php endif; ?>
                <div class="honeypot" aria-hidden="true"><label for="website">Web-sajt</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

                <div class="field"><label for="name">Ime i prezime <span aria-hidden="true">*</span></label><input id="name" name="name" type="text" maxlength="120" autocomplete="name" required value="<?= $e($values['name']) ?>"<?= $errorFor('name') ? ' aria-invalid="true" aria-describedby="name-error"' : '' ?>><?php if ($errorFor('name')): ?><span class="field-error" id="name-error"><?= $e($errorFor('name')) ?></span><?php endif; ?></div>
                <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" value="<?= $e($values['email']) ?>"<?= $errorFor('email') ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>><?php if ($errorFor('email')): ?><span class="field-error" id="email-error"><?= $e($errorFor('email')) ?></span><?php endif; ?></div>
                <div class="field"><label for="phone">Telefon</label><input id="phone" name="phone" type="tel" maxlength="50" autocomplete="tel" value="<?= $e($values['phone']) ?>"<?= $errorFor('phone') ? ' aria-invalid="true" aria-describedby="phone-error"' : '' ?>><?php if ($errorFor('phone')): ?><span class="field-error" id="phone-error"><?= $e($errorFor('phone')) ?></span><?php endif; ?></div>
                <div class="field"><label for="message">Poruka <span aria-hidden="true">*</span></label><textarea id="message" name="message" minlength="10" maxlength="5000" required<?= $errorFor('message') ? ' aria-invalid="true" aria-describedby="message-error"' : '' ?>><?= $e($values['message']) ?></textarea><?php if ($errorFor('message')): ?><span class="field-error" id="message-error"><?= $e($errorFor('message')) ?></span><?php endif; ?></div>
                <button class="button button--primary" type="submit">Pošaljite poruku</button>
            </form>
        </section>
        <aside class="contact-aside" aria-labelledby="contact-help-title"><p class="eyebrow">Kako možemo da pomognemo</p><h2 id="contact-help-title">Recite nam šta vam je potrebno</h2><p>Opišite prostor, željeni uređaj ili servisni problem. Ne šaljite lozinke, podatke o plaćanju ili druge osetljive podatke.</p><a href="/klima-uredjaji">Pogledajte klima uređaje</a></aside>
    </div>
</div>
