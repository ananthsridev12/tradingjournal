<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$title = 'Deposits';
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deposit_date = trim(param('deposit_date'));
    $amount = trim(param('amount'));
    $type = param('type') ?: 'Deposit';
    $notes = trim(param('notes'));

    if ($deposit_date === '') { $errors[] = 'Date is required.'; }
    if ($amount === '' || !is_numeric($amount)) { $errors[] = 'Amount must be a number.'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO deposits (deposit_date, amount, type, notes) VALUES (?,?,?,?)');
        $stmt->execute([$deposit_date, $amount, $type, $notes]);
        header('Location: deposits.php');
        exit;
    }
}

$rows = $pdo->query("SELECT * FROM deposits ORDER BY deposit_date DESC, id DESC")->fetchAll();
$sum = $pdo->query("SELECT
    SUM(CASE WHEN type='Deposit' THEN amount ELSE 0 END) AS deposits,
    SUM(CASE WHEN type='Withdrawal' THEN amount ELSE 0 END) AS withdrawals
  FROM deposits")->fetch();
$balance = (float)($sum['deposits'] ?? 0) - (float)($sum['withdrawals'] ?? 0);

include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1>Deposits</h1>
</section>

<div class="card">
  <div class="label">Available Balance</div>
  <div class="value">₹<?= number_format($balance, 2) ?></div>
</div>

<?php if ($errors): ?>
  <div class="alert">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form class="form" method="post">
  <div class="form-grid">
    <label>
      Date
      <input type="date" name="deposit_date" value="<?= date('Y-m-d') ?>" required>
    </label>
    <label>
      Type
      <select name="type">
        <option value="Deposit">Deposit</option>
        <option value="Withdrawal">Withdrawal</option>
      </select>
    </label>
    <label>
      Amount
      <input type="number" step="0.01" name="amount" required>
    </label>
    <label>
      Notes
      <input type="text" name="notes">
    </label>
  </div>
  <button class="button primary" type="submit">Add</button>
</form>

<table class="table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Type</th>
      <th>Amount</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['deposit_date']) ?></td>
        <td><?= h($r['type']) ?></td>
        <td><?= h($r['amount']) ?></td>
        <td><?= h($r['notes']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials/footer.php'; ?>