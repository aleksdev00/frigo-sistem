<?php
/** @var string $csrfToken */
/** @var string|null $error */
/** @var string $username */
?>
<section class="boot-card" aria-labelledby="login-title">
    <h1 id="login-title">Administrator login</h1>
    <?php if ($error !== null): ?>
        <p role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/login">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <p><label for="username">Username</label><br><input id="username" name="username" type="text" required maxlength="100" autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></p>
        <p><label for="password">Password</label><br><input id="password" name="password" type="password" required maxlength="1024" autocomplete="current-password"></p>
        <button type="submit">Log in</button>
    </form>
</section>
