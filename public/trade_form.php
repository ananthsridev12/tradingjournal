<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$id = param('id');
$editing = $id !== null;

$trade = [
    'trade_date' => date('Y-m-d'),
    'trade_time' => '',
    'symbol' => '',
    'market' => 'NSE',
    'instrument_type' => 'Equity',
    'trade_type' => 'Intraday',
    'side' => 'Buy',
    'qty' => '',
    'entry_price' => '',
    'exit_price' => '',
    'fees' => 0,
    'leverage' => 1,
    'status' => 'Open',
    'strategy_tags' => '',
    'notes' => '',
];

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM trades WHERE id = ?');
    $stmt->execute([(int)$id]);
    $trade = $stmt->fetch() ?: $trade;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trade = [
        'trade_date' => trim(param('trade_date')),
        'trade_time' => trim(param('trade_time')),
        'symbol' => strtoupper(trim(param('symbol'))),
        'market' => trim(param('market')) ?: 'NSE',
        'instrument_type' => param('instrument_type') ?: 'Equity',
        'trade_type' => param('trade_type') ?: 'Intraday',
        'side' => param('side') ?: 'Buy',
        'qty' => trim(param('qty')),
        'entry_price' => trim(param('entry_price')),
        'exit_price' => trim(param('exit_price')),
        'fees' => trim(param('fees')),
        'leverage' => trim(param('leverage')),
        'status' => param('status') ?: 'Open',
        'strategy_tags' => trim(param('strategy_tags')),
        'notes' => trim(param('notes')),
    ];

    if ($trade['trade_date'] === '') { $errors[] = 'Trade date is required.'; }
    if ($trade['symbol'] === '') { $errors[] = 'Symbol is required.'; }
    if ($trade['qty'] === '' || !is_numeric($trade['qty'])) { $errors[] = 'Quantity must be a number.'; }
    if ($trade['entry_price'] === '' || !is_numeric($trade['entry_price'])) { $errors[] = 'Entry price must be a number.'; }
    if ($trade['exit_price'] !== '' && !is_numeric($trade['exit_price'])) { $errors[] = 'Exit price must be a number.'; }
    if ($trade['fees'] === '' || !is_numeric($trade['fees'])) { $trade['fees'] = 0; }
    if ($trade['leverage'] === '' || !is_numeric($trade['leverage'])) { $trade['leverage'] = 1; }

    if ($trade['exit_price'] !== '') {
        $trade['status'] = 'Closed';
    }

    if (!$errors) {
        if ($editing) {
            $sql = 'UPDATE trades SET trade_date=?, trade_time=?, symbol=?, market=?, instrument_type=?, trade_type=?, side=?, qty=?, entry_price=?, exit_price=?, fees=?, leverage=?, status=?, strategy_tags=?, notes=? WHERE id=?';
            $params = [
                $trade['trade_date'],
                $trade['trade_time'] ?: null,
                $trade['symbol'],
                $trade['market'],
                $trade['instrument_type'],
                $trade['trade_type'],
                $trade['side'],
                $trade['qty'],
                $trade['entry_price'],
                $trade['exit_price'] !== '' ? $trade['exit_price'] : null,
                $trade['fees'],
                $trade['leverage'],
                $trade['status'],
                $trade['strategy_tags'],
                $trade['notes'],
                (int)$id,
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = 'INSERT INTO trades (trade_date, trade_time, symbol, market, instrument_type, trade_type, side, qty, entry_price, exit_price, fees, leverage, status, strategy_tags, notes)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $params = [
                $trade['trade_date'],
                $trade['trade_time'] ?: null,
                $trade['symbol'],
                $trade['market'],
                $trade['instrument_type'],
                $trade['trade_type'],
                $trade['side'],
                $trade['qty'],
                $trade['entry_price'],
                $trade['exit_price'] !== '' ? $trade['exit_price'] : null,
                $trade['fees'],
                $trade['leverage'],
                $trade['status'],
                $trade['strategy_tags'],
                $trade['notes'],
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        header('Location: trades.php');
        exit;
    }
}

$title = $editing ? 'Edit Trade' : 'Add Trade';
include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1><?= h($title) ?></h1>
  <a class="button" href="trades.php">Back to Trades</a>
</section>

<?php if ($errors): ?>
  <div class="alert">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form class="form" method="post">
  <?php if ($editing): ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
  <?php endif; ?>

  <div class="form-grid">
    <label>
      Date
      <input type="date" name="trade_date" value="<?= h($trade['trade_date']) ?>" required>
    </label>
    <label>
      Time
      <input type="time" name="trade_time" value="<?= h($trade['trade_time']) ?>">
    </label>
    <label>
      Symbol
      <input type="text" name="symbol" value="<?= h($trade['symbol']) ?>" required>
    </label>
    <label>
      Market
      <input type="text" name="market" value="<?= h($trade['market']) ?>">
    </label>
    <label>
      Instrument
      <select name="instrument_type">
        <?php foreach (['Equity','Futures','Options','Other'] as $v): ?>
          <option value="<?= $v ?>" <?= $trade['instrument_type'] === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Trade Type
      <select name="trade_type">
        <?php foreach (['Intraday','Swing'] as $v): ?>
          <option value="<?= $v ?>" <?= $trade['trade_type'] === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Side
      <select name="side">
        <?php foreach (['Buy','Sell'] as $v): ?>
          <option value="<?= $v ?>" <?= $trade['side'] === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Quantity
      <input type="number" step="0.0001" name="qty" value="<?= h($trade['qty']) ?>" required>
    </label>
    <label>
      Entry Price
      <input type="number" step="0.0001" name="entry_price" value="<?= h($trade['entry_price']) ?>" required>
    </label>
    <label>
      Exit Price
      <input type="number" step="0.0001" name="exit_price" value="<?= h($trade['exit_price']) ?>">
    </label>
    <label>
      Fees
      <input type="number" step="0.0001" name="fees" value="<?= h($trade['fees']) ?>">
    </label>
    <label>
      Leverage (1x, 2x, 5x)
      <input type="number" step="0.01" name="leverage" value="<?= h($trade['leverage']) ?>">
    </label>
    <label>
      Status
      <select name="status">
        <?php foreach (['Open','Closed'] as $v): ?>
          <option value="<?= $v ?>" <?= $trade['status'] === $v ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>
      Strategy Tags (comma separated)
      <input type="text" name="strategy_tags" value="<?= h($trade['strategy_tags']) ?>">
    </label>
  </div>

  <label>
    Notes
    <textarea name="notes" rows="4"><?= h($trade['notes']) ?></textarea>
  </label>

  <button class="button primary" type="submit">Save Trade</button>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>