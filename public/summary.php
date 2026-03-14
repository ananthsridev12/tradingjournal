<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$title = 'Summary';
$pdo = db();

$pnlExpr = "(CASE WHEN side='Buy' THEN (exit_price-entry_price) ELSE (entry_price-exit_price) END) * qty * leverage - fees";

$daily = $pdo->query("SELECT trade_date,
    SUM(CASE WHEN status='Closed' AND exit_price IS NOT NULL THEN $pnlExpr ELSE 0 END) AS pnl,
    SUM(CASE WHEN status='Closed' THEN 1 ELSE 0 END) AS closed_trades
  FROM trades
  GROUP BY trade_date
  ORDER BY trade_date DESC")->fetchAll();

$weekly = $pdo->query("SELECT
    STR_TO_DATE(CONCAT(YEARWEEK(trade_date, 1), ' Monday'), '%X%V %W') AS week_start,
    SUM(CASE WHEN status='Closed' AND exit_price IS NOT NULL THEN $pnlExpr ELSE 0 END) AS pnl,
    SUM(CASE WHEN status='Closed' THEN 1 ELSE 0 END) AS closed_trades
  FROM trades
  GROUP BY YEARWEEK(trade_date, 1)
  ORDER BY week_start DESC")->fetchAll();

$chartLabels = array_reverse(array_map(fn($r) => $r['trade_date'], $daily));
$chartData = array_reverse(array_map(fn($r) => (float)$r['pnl'], $daily));

include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1>Summary</h1>
</section>

<div class="card">
  <canvas id="dailyChart" height="120"></canvas>
</div>

<h2>Daily</h2>
<table class="table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Closed Trades</th>
      <th>P/L</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($daily as $r): ?>
      <tr>
        <td><?= h($r['trade_date']) ?></td>
        <td><?= h($r['closed_trades']) ?></td>
        <td class="<?= ($r['pnl'] >= 0) ? 'pos' : 'neg' ?>">₹<?= number_format($r['pnl'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h2>Weekly</h2>
<table class="table">
  <thead>
    <tr>
      <th>Week Start</th>
      <th>Closed Trades</th>
      <th>P/L</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($weekly as $r): ?>
      <tr>
        <td><?= h($r['week_start']) ?></td>
        <td><?= h($r['closed_trades']) ?></td>
        <td class="<?= ($r['pnl'] >= 0) ? 'pos' : 'neg' ?>">₹<?= number_format($r['pnl'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const labels = <?= json_encode($chartLabels) ?>;
  const data = <?= json_encode($chartData) ?>;

  const ctx = document.getElementById('dailyChart');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Daily P/L',
        data,
        borderColor: '#1f6feb',
        backgroundColor: 'rgba(31, 111, 235, 0.15)',
        fill: true,
        tension: 0.2
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { ticks: { callback: value => '₹' + value } }
      }
    }
  });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>