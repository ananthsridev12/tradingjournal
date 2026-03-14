<?php
function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function param(string $key, $default = null) {
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

function compute_pnl(array $trade): ?float {
    if (($trade['status'] ?? '') !== 'Closed') {
        return null;
    }
    if (!isset($trade['exit_price']) || $trade['exit_price'] === null || $trade['exit_price'] === '') {
        return null;
    }

    $entry = (float)$trade['entry_price'];
    $exit = (float)$trade['exit_price'];
    $qty = (float)$trade['qty'];
    $fees = (float)($trade['fees'] ?? 0);
    $lev = (float)($trade['leverage'] ?? 1);
    $side = $trade['side'] ?? 'Buy';

    $perShare = ($side === 'Buy') ? ($exit - $entry) : ($entry - $exit);
    return ($perShare * $qty * $lev) - $fees;
}