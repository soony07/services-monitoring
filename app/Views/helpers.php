<?php
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function heartbeat(array $checks): void
{
    echo '<div class="heartbeat" aria-label="' . h(t('Letzte Prüfungen, älteste zuerst')) . '">';
    foreach (array_reverse(array_slice($checks, 0, 40)) as $check) {
        $state = $check['up'] ? 'up' : 'down';
        $title = gmdate('M j H:i:s', $check['checked_at']) . ' UTC · ' . statusLabel($state) . ' · ' . $check['latency_ms'] . ' ms';
        echo '<span class="' . $state . '" title="' . h($title) . '"></span>';
    }
    if (!$checks) {
        echo '<small>' . h(t('Noch keine Prüfungen')) . '</small>';
    }
    echo '</div>';
}

function stateBadge(string $state): void
{
    echo '<span class="badge ' . h($state) . '">' . h(statusLabel($state)) . '</span>';
}


function statusLabel(string $state): string
{
    return t(['up' => 'Erreichbar', 'down' => 'Ausgefallen', 'pending' => 'Ausstehend', 'paused' => 'Pausiert'][$state] ?? $state);
}

