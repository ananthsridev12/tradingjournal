<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$title = 'Trades';
$pdo = db();

$rows = $pdo->query("SELECT * FROM trades ORDER BY trade_date DESC, id DESC")->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1>Trades</h1>
  <div class="header-actions">
    <a class="button" href="trade_form.php">Add Trade</a>
    <a class="button" href="fyers_sync.php">Sync Fyers</a>
  </div>
</section>

<?php if (param('sync') === '1'): ?>
  <div class="alert">
    Fyers sync complete.
    Inserted: <?= h(param('inserted', 0)) ?>,
    Updated: <?= h(param('updated', 0)) ?>,
    Skipped: <?= h(param('skipped', 0)) ?>
  </div>
<?php elseif (param('sync') === '0' && param('error')): ?>
  <div class="alert">
    Fyers sync failed: <?= h(param('error')) ?>
  </div>
<?php endif; ?>

<table class="table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Symbol</th>
      <th>Side</th>
      <th>Qty</th>
      <th>Entry</th>
      <th>Exit</th>
      <th>Leverage</th>
      <th>P/L</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
      $pnl = compute_pnl($r);
      $pnlClass = ($pnl ?? 0) >= 0 ? 'pos' : 'neg';
    ?>
    <tr>
      <td><?= h($r['trade_date']) ?></td>
      <td><?= h($r['symbol']) ?></td>
      <td><?= h($r['side']) ?></td>
      <td><?= h($r['qty']) ?></td>
      <td><?= h($r['entry_price']) ?></td>
      <td><?= h($r['exit_price']) ?></td>
      <td><?= h($r['leverage']) ?>x</td>
      <td class="<?= $pnlClass ?>"><?= $pnl === null ? '-' : '₹' . number_format($pnl, 2) ?></td>
      <td><?= h($r['status']) ?></td>
      <td><a href="trade_form.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials/footer.php'; ?>
