<!doctype html>
<html lang="<?= h($GLOBALS['language']) ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>IT Services</title><link rel="stylesheet" href="<?= h(appUrl()) ?>style.css?v=centered-settings-1"></head>
<body><main class="settings-page">
<?= languageSwitch() ?>
<h1><?= $setup ? t('Ersteinrichtung') : t('Einstellungen') ?></h1>
<?php if (!$setup): ?><p><a href="<?= h(appUrl()) ?>">Dashboard</a></p><?php endif; ?>
<form method="post" class="panel settings">
<?php if (isset($error)): ?><p role="alert" class="error"><?= h(t($error)) ?></p><?php endif; ?>
<input type="hidden" name="token" value="<?= h($_SESSION['token']) ?>">
<label><?= h(t("Name der Instanz")) ?><input name="name" maxlength="100" required value="<?= h($values['name'] ?? '') ?>"></label>
<label><?= h(t("Standardsprache")) ?><select name="language"><option value="de" <?= ($values['language'] ?? '') === 'de' ? 'selected' : '' ?>>Deutsch</option><option value="en" <?= ($values['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option></select></label>
<label><?= h(t("Akzentfarbe")) ?><input id="accent" type="color" name="accent" value="<?= h(preg_match('/^#[0-9a-f]{6}$/i', $values['accent'] ?? '') ? $values['accent'] : '#336b81') ?>"></label>
<div id="preview" style="padding:20px;color:white;background:<?= h(preg_match('/^#[0-9a-f]{6}$/i', $values['accent'] ?? '') ? $values['accent'] : '#336b81') ?>"><?= h(t("Farbvorschau")) ?></div>
<button class="primary"><?= h(t("Speichern")) ?></button>
</form></main><script>
document.querySelector('#accent').addEventListener('input', event => {
    document.querySelector('#preview').style.background = event.target.value;
});
</script></body></html>

