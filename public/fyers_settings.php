<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fyers.php';

$pdo = db();
$tokenRow = fyers_get_token($pdo);

$errors = [];
$saved = false;
$expiresValue = '';
if ($tokenRow && !empty($tokenRow['expires_at'])) {
    $expiresValue = str_replace(' ', 'T', substr($tokenRow['expires_at'], 0, 16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accessToken = trim(param('access_token'));
    $expiresAt = trim(param('expires_at'));
    if ($expiresAt === '') {
        $expiresAt = null;
    } else {
        $expiresAt = str_replace('T', ' ', $expiresAt);
        if (strlen($expiresAt) === 16) {
            $expiresAt .= ':00';
        }
    }

    if ($accessToken === '') {
        $errors[] = 'Access token is required.';
    }

    if (!$errors) {
        fyers_set_token($pdo, $accessToken, $expiresAt);
        $tokenRow = fyers_get_token($pdo);
        $expiresValue = '';
        if ($tokenRow && !empty($tokenRow['expires_at'])) {
            $expiresValue = str_replace(' ', 'T', substr($tokenRow['expires_at'], 0, 16));
        }
        $saved = true;
    }
}

$title = 'Fyers Settings';
include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1>Fyers Settings</h1>
  <div class="header-actions">
    <a class="button" href="trades.php">Back to Trades</a>
    <a class="button" href="fyers_auth.php">Generate Auth Code</a>
  </div>
</section>

<?php if ($saved): ?>
  <div class="alert">
    Access token saved.
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form class="form" method="post">
  <label>
    Access Token
    <textarea name="access_token" rows="3" required><?= h($tokenRow['access_token'] ?? '') ?></textarea>
  </label>
  <label>
    Expires At (optional)
    <input type="datetime-local" name="expires_at" value="<?= h($expiresValue) ?>">
  </label>
  <button class="button primary" type="submit">Save Token</button>
</form>

<?php if ($tokenRow): ?>
  <div class="card" style="margin-top:16px;">
    <div><strong>Last Updated:</strong> <?= h($tokenRow['updated_at']) ?></div>
    <div><strong>Expires At:</strong> <?= h($tokenRow['expires_at'] ?? '-') ?></div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
