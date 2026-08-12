<?php
/** @var string $csrfToken */
/** @var string $username */
?>
<section class="boot-card" aria-labelledby="admin-title">
    <p class="status">Admin authenticated</p>
    <h1 id="admin-title">Welcome, <?= htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
    <p>This temporary page verifies Phase 3 authentication. Catalog administration arrives in Phase 4.</p>
    <form method="post" action="/admin/logout">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <button type="submit">Log out</button>
    </form>
</section>
