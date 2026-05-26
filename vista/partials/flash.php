<?php
$authMode = $_GET['auth'] ?? '';
$showFlash = !in_array($authMode, ['login', 'success'], true);
$flash = null;

if ($showFlash) {
    $flash = getFlash();
}
?>
<?php if ($flash): ?>
    <section style="padding:16px 80px;background:<?= $flash['type'] === 'error' ? '#ffe9e9' : '#e9f8ec' ?>;color:<?= $flash['type'] === 'error' ? '#9f1d1d' : '#1a6b2a' ?>;font-weight:600;">
        <?= e($flash['message']) ?>
    </section>
<?php endif; ?>
