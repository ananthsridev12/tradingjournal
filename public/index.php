<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$title = 'Dashboard';

$pdo = db();

$stats = [
    'total_trades' => 0,
    'closed_trades' => 0,
    'open_trades' => 0,
    'total_pnl' => 0,
    'wins' => 0,
    'losses' => 0,
];

$rows = $pdo->query("SELECT status, side, qty, entry_price, exit_price, fees, leverage FROM trades")->fetchAll();
$stats['total_trades'] = count($rows);
foreach ($rows as $r) {
    if ($r['status'] === 'Closed' && $r['exit_price'] !== null) {
        $stats['closed_trades']++;
        $pnl = compute_pnl($r);
        $stats['total_pnl'] += $pnl ?? 0;
        if (($pnl ?? 0) >= 0) {
            $stats['wins']++;
        } else {
            $stats['losses']++;
        }
    } else {
        $stats['open_trades']++;
    }
}

$win_rate = $stats['closed_trades'] > 0 ? ($stats['wins'] / $stats['closed_trades']) * 100 : 0;

$depositSum = $pdo->query("SELECT
    SUM(CASE WHEN type='Deposit' THEN amount ELSE 0 END) AS deposits,
    SUM(CASE WHEN type='Withdrawal' THEN amount ELSE 0 END) AS withdrawals
  FROM deposits")->fetch();
$balance = (float)($depositSum['deposits'] ?? 0) - (float)($depositSum['withdrawals'] ?? 0);

include __DIR__ . '/partials/header.php';
?>

<section class="grid">
  <div class="card">
    <div class="label">Total P/L</div>
    <div class="value <?= ($stats['total_pnl'] >= 0) ? 'pos' : 'neg' ?>">₹<?= number_format($stats['total_pnl'], 2) ?></div>
  </div>
  <div class="card">
    <div class="label">Win Rate</div>
    <div class="value"><?= number_format($win_rate, 1) ?>%</div>
  </div>
  <div class="card">
    <div class="label">Closed Trades</div>
    <div class="value"><?= $stats['closed_trades'] ?></div>
  </div>
  <div class="card">
    <div class="label">Open Trades</div>
    <div class="value"><?= $stats['open_trades'] ?></div>
  </div>
  <div class="card">
    <div class="label">Deposits Balance</div>
    <div class="value">₹<?= number_format($balance, 2) ?></div>
  </div>
</section>

<section class="actions">
  <a class="button" href="trade_form.php">Add Trade</a>
  <a class="button" href="trades.php">View Trades</a>
  <a class="button" href="summary.php">View Summary</a>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>