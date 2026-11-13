<!doctype html>
<html lang="<?= h($GLOBALS['language']) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($GLOBALS['settings']['name']) ?> · <?= h($public ? t('Dienstestatus') : 'Dashboard') ?></title>
<link rel="stylesheet" href="<?= h(appUrl()) ?>style.css?v=settings-menu-1">
<script src="<?= h(appUrl()) ?>dashboard.js?v=settings-menu-1" defer></script>
<style>:root { --accent: <?= h($GLOBALS["settings"]["accent"]) ?>; --mint: <?= h($GLOBALS["settings"]["accent"]) ?>; } .top,.primary,.brand svg { background: <?= h($GLOBALS["settings"]["accent"]) ?> !important; } .sidebar,.summary .panel,.primary,.monitor-link.selected { border-color: <?= h($GLOBALS["settings"]["accent"]) ?> !important; }</style></head>
<body <?= !$editing ? 'data-refresh' : '' ?>>
<header class="top"><div class="top-inner">
<a class="brand" href="<?= $public ? appUrl() . '?status' : appUrl() ?>"><svg viewBox="0 0 30 30" aria-hidden="true"><path d="M4 16h5l3-8 5 14 3-6h6" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round"/></svg> <?= h($GLOBALS['settings']['name']) ?></a>
<nav aria-label="<?= h(t("Hauptnavigation")) ?>">
<a class="nav-link" href="<?= h(appUrl()) ?>?status"><?= h(t("Statusseite")) ?></a><a class="nav-link <?= !$public ? 'active' : '' ?>" href="<?= h(appUrl()) ?>">Dashboard</a>
<button class="plain" type="button" id="theme" aria-label="<?= h(t("Farbschema wechseln")) ?>" title="<?= h(t("Farbschema wechseln")) ?>">◐</button>
<?php if (!$public): ?>
<details class="settings-menu">
<summary title="<?= h(t('Einstellungen')) ?>" aria-label="<?= h(t('Einstellungen')) ?>">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 3-.5 2-2 1L4.5 5.5 2.5 9l1.5 1.5v3L2.5 15l2 3.5L6.5 18l2 1L9 21h6l.5-2 2-1 2 .5 2-3.5-1.5-1.5v-3L21.5 9l-2-3.5-2 .5-2-1L15 3Z"/><circle cx="12" cy="12" r="3"/></svg>
</summary>
<div class="settings-popover">
<a class="settings-link" href="<?= h(appUrl()) ?>?settings"><?= h(t('Einstellungen')) ?></a>
<div class="language-options"><span class="muted"><?= h(t('Sprache')) ?></span><?= languageSwitch() ?></div>
</div>
</details>
<?php endif; ?></nav>
</div></header>
<?php if ($public): ?>
<main class="status-page">
<p class="overline"><?= h($GLOBALS["settings"]["name"]) ?></p><h1><?= h(t("Dienstestatus")) ?></h1>
<div class="panel banner <?= !$workerOnline && $monitors ? 'pending' : ($counts['down'] ? 'down' : ($counts['pending'] || $counts['paused'] || !$monitors ? 'pending' : 'up')) ?>"><strong><?= !$workerOnline && $monitors ? t('Prüfergebnisse möglicherweise veraltet') : ($counts['down'] ? t('Einige Dienste sind nicht erreichbar') : ($counts['up'] && !$counts['pending'] && !$counts['paused'] ? t('Alle Dienste sind erreichbar') : t('Prüfergebnisse stehen aus'))) ?></strong></div>
<div class="status-summary">
<?php foreach ($counts as $state => $count): ?>
<div><span class="dot <?= h($state) ?>"></span> <?= h(statusLabel($state)) ?><strong><?= $count ?></strong></div>
<?php endforeach; ?>
</div>
<?php foreach ($monitors as $mid => $item): ?>
<article class="panel"><div class="row"><h2><?= h($item['name']) ?></h2><?php stateBadge($item['state']); ?></div><?php $statusChecks = array_slice($histories[$mid], 0, 40); heartbeat($statusChecks); ?>
<div class="status-meta">
<span><?= $statusChecks ? number_format(100 * array_sum(array_column($statusChecks, 'up')) / count($statusChecks), 1) . ' ' . t('% erfolgreich · letzte') . ' ' . count($statusChecks) . ' ' . t('Prüfungen') : t('Erste Prüfung steht aus') ?></span>
<span><?= $item['checked_at'] ? t('Zuletzt geprüft') . ' ' . h(gmdate('d.m.Y H:i:s', $item['checked_at'])) . ' UTC' : t('Noch keine Prüfung') ?></span>
</div></article>
<?php endforeach; ?>
<?php if (!$monitors): ?><p class="muted"><?= h(t("Für diese Statusseite wurden noch keine Dienste ausgewählt.")) ?></p><?php endif; ?>
</main>
<?php else: ?>
<main class="workspace">
<aside class="panel sidebar">
<a class="button primary add" href="<?= h(appUrl()) ?>?view=new"><?= h(t("＋ Monitor hinzufügen")) ?></a>
<label class="search"><span>⌕</span><input id="search" type="search" placeholder="<?= h(t("Monitore suchen")) ?>" aria-label="<?= h(t("Monitore suchen")) ?>"></label>
<div class="aside-label"><?= h(t("MONITORE")) ?> <span><?= count($monitors) ?></span></div>
<?php foreach ($monitors as $mid => $item): ?>
<a class="monitor-link <?= $mid === $id ? 'selected' : '' ?>" data-monitor href="<?= h(appUrl()) ?>?id=<?= $mid ?>">
<span class="dot <?= $item['state'] ?>"></span><span class="monitor-name"><?= h($item['name']) ?><small><?= h(t($kinds[$item['kind']])) ?></small></span>
</a>
<?php endforeach; ?>
<?php if (!$monitors): ?><p class="muted small"><?= h(t("Hier erscheinen deine Monitore.")) ?></p><?php endif; ?>
</aside>
<section class="content">
<?php if (!$editing): ?>
<div class="panel worker <?= $workerOnline ? 'up' : 'pending' ?>">
<div class="row"><strong><span class="dot"></span> <?= $workerOnline ? t('Prüfdienst aktiv') : ($workerSeen ? t('Prüfdienst offline') : t('Prüfdienst noch nicht gestartet')) ?></strong>
<?php if ($workerSeen): ?><small class="muted"><?= h(t("Letztes Signal")) ?> <?= h(gmdate('H:i:s', $workerSeen)) ?> UTC</small><?php endif; ?></div>
<?php if (!$workerOnline): ?><p><?= h(t("Starte")) ?> <code>php worker.php</code> <?= h(t("im Projektordner. Der Prüfdienst muss für regelmäßige Prüfungen laufen.")) ?></p>
<?php elseif ($counts['pending']): ?><p><?= $counts['pending'] ?> <?= $counts['pending'] === 1 ? t('Monitor wartet') : t('Monitore warten') ?> <?= h(t("auf die erste Prüfung.")) ?></p><?php endif; ?>
</div>
<?php endif; ?>
<?php if ($editing):
    $form = $draft ?? ($mode === 'edit' ? $monitor : ['name'=>'', 'url'=>'', 'kind'=>'http', 'keyword'=>'', 'interval_seconds'=>60, 'listed'=>0]); ?>
<p class="overline"><?= h(t("EINSTELLUNGEN")) ?></p>
<h1><?= $mode === 'edit' ? t('Monitor bearbeiten') : t('Monitor hinzufügen') ?></h1>
<form method="post" class="panel settings">
<?php if (isset($error)): ?><p class="error" role="alert"><?= h(t($error)) ?></p><?php endif; ?>
<input type="hidden" name="token" value="<?= h($token) ?>"><input type="hidden" name="id" value="<?= $mode === 'edit' ? $id : 0 ?>">
<label><?= h(t("Prüfart")) ?><select name="kind" id="kind"><?php foreach ($kinds as $key => $label): ?><option value="<?= $key ?>" <?= $form['kind'] === $key ? 'selected' : '' ?>><?= h(t($label)) ?></option><?php endforeach; ?></select></label>
<label><?= h(t("Bezeichnung")) ?><input name="name" maxlength="120" value="<?= h($form['name']) ?>" placeholder="<?= h(t("Unternehmenswebsite")) ?>" required></label>
<label><?= h(t("Ziel")) ?><input name="url" maxlength="2048" value="<?= h($form['url']) ?>" placeholder="https://example.com" required><small><?= h(t("HTTP(S)-Adresse oder Host:Port für TCP eingeben.")) ?></small></label>
<label id="keyword"><?= h(t("Suchtext")) ?><input name="keyword" maxlength="256" value="<?= h($form['keyword']) ?>"><small><?= h(t("Gesuchter Text in der Antwort. Groß- und Kleinschreibung beachten.")) ?></small></label>
<label><?= h(t("Prüfintervall (Sekunden)")) ?><input name="interval" type="number" min="10" max="86400" value="<?= h($form['interval_seconds']) ?>" required></label>
<label class="checkbox"><input type="checkbox" name="listed" <?= $form['listed'] ? 'checked' : '' ?>> <?= h(t("Auf der Statusseite anzeigen")) ?></label>
<div class="actions"><button class="primary" name="action" value="save"><?= h(t("Speichern")) ?></button><a class="button" href="<?= $id ? appUrl() . "?id=$id" : appUrl() ?>"><?= h(t("Abbrechen")) ?></a></div>
</form>
<?php elseif ($monitor):
    $checks = $histories[$id];
    $latest = $checks[0] ?? null;
    $uptime = $checks ? number_format(100 * array_sum(array_column($checks, 'up')) / count($checks), 1) . '%' : '—';
    $recent = array_reverse(array_slice($checks, 0, 40));
    $peak = max([1, ...array_column($recent, 'latency_ms')]); ?>
<p class="overline"><?= h(t($kinds[$monitor['kind']])) ?> MONITOR</p>
<div class="row detail-title"><div><h1><?= h($monitor['name']) ?></h1><p class="muted target"><?= h($monitor['url']) ?></p></div><?php stateBadge($monitor['state']); ?></div>
<form class="actions" method="post">
<input type="hidden" name="token" value="<?= h($token) ?>"><input type="hidden" name="id" value="<?= $id ?>">
<a class="button primary" href="<?= h(appUrl()) ?>?id=<?= $id ?>&amp;view=edit"><?= h(t("Bearbeiten")) ?></a>
<button name="action" value="toggle"><?= $monitor['paused'] ? t('Fortsetzen') : t('Pausieren') ?></button>
<?php if (!$monitor['paused']): ?><button name="action" value="check"><?= h(t("Jetzt prüfen")) ?></button><?php endif; ?>
<button class="danger" name="action" value="delete" onclick="return confirm(<?= h(json_encode(t('Diesen Monitor und seinen Verlauf löschen?'))) ?>)"><?= h(t("Löschen")) ?></button>
</form>
<?php if ($notice): ?><p class="muted" role="status"><?= h(t($notice)) ?></p><?php endif; ?>
<div class="panel"><div class="row"><h2><?= h(t("Prüfverlauf")) ?></h2><small class="muted"><?= h(t("Alle")) ?> <?= h($monitor['interval_seconds']) ?> <?= h(t("Sekunden")) ?></small></div><?php heartbeat($checks); ?>
<div class="metrics">
<div><span><?= h(t("Antwortzeit")) ?></span><strong><?= $latest ? h($latest['latency_ms']) . ' ms' : '—' ?></strong></div>
<div><span><?= h(t("Verfügbarkeit ·")) ?> <?= count($checks) ?> <?= h(t("Prüfungen")) ?></span><strong><?= h($uptime) ?></strong></div>
<div><span><?= h(t("Zuletzt geprüft · UTC")) ?></span><strong><?= $latest ? h(gmdate('H:i:s', $latest['checked_at'])) : '—' ?></strong></div>
</div></div>
<div class="panel"><div class="row"><h2><?= h(t("Antwortzeit")) ?></h2><small class="muted"><?= h(t("Letzte")) ?> <?= count($recent) ?> <?= h(t("Prüfungen")) ?></small></div>
<?php if ($recent): ?>
<div class="chart" role="group" aria-label="<?= h(t("Letzte Antwortzeiten in Millisekunden")) ?>">
<?php foreach ($recent as $check): ?><span class="<?= $check['up'] ? 'up' : 'down' ?>" style="--height:<?= max(3, 100 * $check['latency_ms'] / $peak) ?>%" title="<?= h($check['latency_ms']) ?> ms"></span><?php endforeach; ?>
</div><div class="row muted small"><span><?= h(gmdate('H:i:s', $recent[0]['checked_at'])) ?> UTC</span><span><?= h($peak) ?> <?= h(t("ms Höchstwert")) ?></span><span><?= h(gmdate('H:i:s', $latest['checked_at'])) ?> UTC</span></div>
<?php else: ?><p class="muted"><?= h(t("Antwortzeiten erscheinen nach der ersten Prüfung.")) ?></p><?php endif; ?></div>
<div class="panel"><h2><?= h(t("Letzte Prüfungen")) ?></h2>
<?php if ($checks): ?><div class="table-scroll"><table><thead><tr><th><?= h(t("Zeit · UTC")) ?></th><th>Status</th><th><?= h(t("Antwortzeit")) ?></th><th><?= h(t("Meldung")) ?></th></tr></thead><tbody>
<?php foreach (array_slice($checks, 0, 20) as $check): ?><tr><td><?= h(gmdate('d.m.Y H:i:s', $check['checked_at'])) ?></td><td><?php stateBadge($check['up'] ? 'up' : 'down'); ?></td><td><?= h($check['latency_ms']) ?> ms</td><td><?= h(t($check['error'] ?: ($check['status_code'] ? 'HTTP ' . $check['status_code'] : t('Verbunden')))) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><p class="muted"><?= h(t("Noch keine Prüfungen vorhanden.")) ?></p><?php endif; ?></div>
<?php else: ?>
<p class="overline"><?= h(t("ÜBERSICHT")) ?></p><h1>Dashboard</h1><p class="muted intro"><?= h(t("Verfügbarkeit und Antwortzeiten deiner Dienste.")) ?></p>
<div class="summary"><?php foreach ($counts as $state => $count): ?><div class="panel"><span class="dot <?= h($state) ?>"></span> <?= h(statusLabel($state)) ?><strong><?= $count ?></strong></div><?php endforeach; ?></div>
<div class="panel"><div class="row"><h2><?= h(t("Dienste im Überblick")) ?></h2><small class="muted"><?= h(t("Aktualisierung alle 15 Sekunden")) ?></small></div>
<?php if (!$monitors): ?><div class="empty"><strong><?= h(t("Noch keine Monitore")) ?></strong><p class="muted"><?= h(t("Füge einen Dienst hinzu, um seine Verfügbarkeit zu prüfen.")) ?></p><a class="button primary" href="<?= h(appUrl()) ?>?view=new"><?= h(t("＋ Monitor hinzufügen")) ?></a></div><?php endif; ?>
<?php foreach ($monitors as $mid => $item): ?><a class="overview-monitor" href="<?= h(appUrl()) ?>?id=<?= $mid ?>"><div class="row"><strong><?= h($item['name']) ?></strong><?php stateBadge($item['state']); ?></div><?php heartbeat(array_slice($histories[$mid], 0, 40)); ?><small class="muted"><?= $item['checked_at'] ? t('Letzte Prüfung') . ' ' . h(gmdate('H:i:s', $item['checked_at'])) . ' UTC' : t('Noch keine Prüfungen vorhanden') ?></small></a><?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</main>
<?php endif; ?>
</body>
</html>













