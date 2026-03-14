<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fyers.php';

session_start();

global $config;
$pdo = db();

$clientId = trim(param('client_id', $config['fyers_app_id'] ?? ''));
$redirectUri = trim(param('redirect_uri', $config['fyers_redirect_uri'] ?? ''));
$secretId = trim(param('secret_id', $config['fyers_secret_id'] ?? ''));

if (!isset($_SESSION['fyers_state'])) {
    $_SESSION['fyers_state'] = bin2hex(random_bytes(8));
}
$state = $_SESSION['fyers_state'];

$authCode = param('auth_code', param('code', ''));

$loginUrl = '';
if ($clientId && $redirectUri) {
    $loginUrl = 'https://api-t1.fyers.in/api/v3/generate-authcode?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'state' => $state,
    ]);
}

$exchangeResult = null;
$exchangeError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && param('action') === 'exchange') {
    $authCode = trim(param('auth_code'));
    $secretId = trim(param('secret_id'));

    if ($clientId === '' || $redirectUri === '') {
        $exchangeError = 'Client ID and Redirect URI are required.';
    } elseif ($authCode === '') {
        $exchangeError = 'Auth code is required.';
    } elseif ($secretId === '') {
        $exchangeError = 'Secret ID is required to generate access token.';
    } else {
        $hash = hash('sha256', $clientId . ':' . $secretId);
        $payload = json_encode([
            'grant_type' => 'authorization_code',
            'appIdHash' => $hash,
            'code' => $authCode,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api-t1.fyers.in/api/v3/validate-authcode',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $status < 200 || $status >= 300) {
            $exchangeError = $err ?: ('HTTP ' . $status);
        } else {
            $exchangeResult = json_decode($body, true);
            if (!is_array($exchangeResult)) {
                $exchangeError = 'Response was not valid JSON.';
            } else {
                $accessToken = $exchangeResult['access_token']
                    ?? $exchangeResult['accessToken']
                    ?? ($exchangeResult['data']['access_token'] ?? null)
                    ?? ($exchangeResult['data']['accessToken'] ?? null);
                if ($accessToken) {
                    fyers_set_token($pdo, $accessToken, null);
                }
            }
        }
    }
}

$title = 'Fyers Auth Code';
include __DIR__ . '/partials/header.php';
?>

<section class="header-row">
  <h1>Fyers Auth Code</h1>
  <a class="button" href="fyers_settings.php">Back to Fyers Settings</a>
</section>

<?php if ($exchangeError): ?>
  <div class="alert">Auth exchange failed: <?= h($exchangeError) ?></div>
<?php elseif ($exchangeResult): ?>
  <div class="alert">Auth exchange completed. If an access token was returned, it has been saved.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px;">
  <div class="label">Step 1</div>
  <div class="value">Generate Login URL</div>
  <form class="form" method="post" style="margin-top:12px;">
    <label>
      Client ID (App ID)
      <input type="text" name="client_id" value="<?= h($clientId) ?>" required>
    </label>
    <label>
      Redirect URI
      <input type="text" name="redirect_uri" value="<?= h($redirectUri) ?>" required>
    </label>
    <input type="hidden" name="action" value="build">
    <button class="button primary" type="submit">Build Login URL</button>
  </form>
  <?php if ($loginUrl): ?>
    <div style="margin-top:12px;">
      <div class="label">Login URL</div>
      <div style="word-break: break-all; margin-top:6px;"><?= h($loginUrl) ?></div>
      <div style="margin-top:8px;">
        <a class="button" href="<?= h($loginUrl) ?>" target="_blank" rel="noopener">Open Login</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="label">Step 2</div>
  <div class="value">Exchange Auth Code</div>
  <form class="form" method="post" style="margin-top:12px;">
    <label>
      Auth Code (from redirect URL)
      <textarea name="auth_code" rows="2" required><?= h($authCode) ?></textarea>
    </label>
    <label>
      Secret ID
      <input type="password" name="secret_id" value="<?= h($secretId) ?>" required>
    </label>
    <input type="hidden" name="client_id" value="<?= h($clientId) ?>">
    <input type="hidden" name="redirect_uri" value="<?= h($redirectUri) ?>">
    <input type="hidden" name="action" value="exchange">
    <button class="button primary" type="submit">Generate Access Token</button>
  </form>

  <?php if ($exchangeResult): ?>
    <pre style="white-space:pre-wrap; margin-top:12px;"><?= h(json_encode($exchangeResult, JSON_PRETTY_PRINT)) ?></pre>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
