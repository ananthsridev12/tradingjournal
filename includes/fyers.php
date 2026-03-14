<?php
require_once __DIR__ . '/db.php';

function fyers_config(): array {
    global $config;
    return [
        'tradebook_url' => $config['fyers_tradebook_url'] ?? '',
        'app_id' => $config['fyers_app_id'] ?? '',
        'extra_headers' => $config['fyers_extra_headers'] ?? [],
    ];
}

function fyers_get_token(PDO $pdo): ?array {
    $stmt = $pdo->prepare('SELECT access_token, expires_at, updated_at FROM broker_tokens WHERE broker = ? LIMIT 1');
    $stmt->execute(['fyers']);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fyers_set_token(PDO $pdo, string $token, ?string $expiresAt): void {
    $stmt = $pdo->prepare('INSERT INTO broker_tokens (broker, access_token, expires_at) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE access_token=VALUES(access_token), expires_at=VALUES(expires_at)');
    $stmt->execute(['fyers', $token, $expiresAt]);
}

function fyers_build_headers(string $token, array $config): array {
    $headers = [
        'Content-Type: application/json',
        'version: 3',
    ];

    if (!empty($config['app_id'])) {
        $headers[] = 'Authorization: ' . $config['app_id'] . ':' . $token;
    } else {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $extra = $config['extra_headers'] ?? [];
    foreach ($extra as $k => $v) {
        if (is_int($k)) {
            $headers[] = $v;
            continue;
        }
        $headers[] = $k . ': ' . $v;
    }

    return $headers;
}

function fyers_http_get(string $url, array $headers): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'ok' => $err === '' && $status >= 200 && $status < 300,
        'status' => $status,
        'error' => $err ?: null,
        'body' => $body,
    ];
}

function fyers_extract_trade_items(array $payload): array {
    if (isset($payload['tradeBook']) && is_array($payload['tradeBook'])) {
        return $payload['tradeBook'];
    }
    if (isset($payload['tradebook']) && is_array($payload['tradebook'])) {
        return $payload['tradebook'];
    }
    if (isset($payload['data']) && is_array($payload['data'])) {
        if (isset($payload['data']['tradeBook']) && is_array($payload['data']['tradeBook'])) {
            return $payload['data']['tradeBook'];
        }
        if (isset($payload['data']['tradebook']) && is_array($payload['data']['tradebook'])) {
            return $payload['data']['tradebook'];
        }
        if (array_keys($payload['data']) === range(0, count($payload['data']) - 1)) {
            return $payload['data'];
        }
    }
    return [];
}

function fyers_map_side($value): string {
    if (is_numeric($value)) {
        return ((int)$value === 1) ? 'Buy' : 'Sell';
    }
    $v = strtoupper(trim((string)$value));
    if ($v === 'BUY' || $v === 'B' || $v === 'LONG') {
        return 'Buy';
    }
    return 'Sell';
}

function fyers_parse_datetime($value): array {
    if ($value === null || $value === '') {
        return [date('Y-m-d'), null];
    }
    if (is_numeric($value)) {
        $ts = (int)$value;
        if ($ts > 1000000000000) {
            $ts = (int)floor($ts / 1000);
        }
        return [date('Y-m-d', $ts), date('H:i:s', $ts)];
    }
    $ts = strtotime((string)$value);
    if ($ts === false) {
        return [date('Y-m-d'), null];
    }
    return [date('Y-m-d', $ts), date('H:i:s', $ts)];
}

function fyers_map_trade(array $item): array {
    $symbol = $item['symbol'] ?? $item['sym'] ?? $item['tradingsymbol'] ?? $item['tradingSymbol'] ?? '';
    $market = $item['exchange'] ?? $item['exchangeSegment'] ?? $item['exchangeCode'] ?? 'NSE';
    $qty = $item['qty'] ?? $item['quantity'] ?? $item['tradedQty'] ?? $item['filledQty'] ?? null;
    $price = $item['tradePrice'] ?? $item['price'] ?? $item['avgPrice'] ?? $item['averagePrice'] ?? null;
    $side = fyers_map_side($item['side'] ?? $item['buySell'] ?? $item['transactionType'] ?? 'Buy');
    $fees = $item['fees'] ?? $item['charges'] ?? $item['commission'] ?? 0;
    $leverage = $item['leverage'] ?? 1;

    $rawTime = $item['tradeTime'] ?? $item['trade_time'] ?? $item['timestamp'] ?? $item['tradeDateTime'] ?? $item['orderDateTime'] ?? null;
    [$tradeDate, $tradeTime] = fyers_parse_datetime($rawTime);

    $tradeType = 'Intraday';
    $product = strtoupper((string)($item['productType'] ?? $item['product'] ?? ''));
    if ($product && $product !== 'INTRADAY' && $product !== 'MIS') {
        $tradeType = 'Swing';
    }

    $instrumentType = 'Equity';
    $inst = strtoupper((string)($item['instrumentType'] ?? $item['instrument'] ?? ''));
    if ($inst === 'FUT' || $inst === 'FUTURES') {
        $instrumentType = 'Futures';
    } elseif ($inst === 'OPT' || $inst === 'OPTIONS') {
        $instrumentType = 'Options';
    } elseif ($inst && $inst !== 'EQ' && $inst !== 'EQUITY') {
        $instrumentType = 'Other';
    }

    $brokerTradeId = $item['tradeId'] ?? $item['trade_id'] ?? $item['id'] ?? null;
    $brokerOrderId = $item['orderId'] ?? $item['order_id'] ?? null;

    if (!$brokerTradeId) {
        $fingerprint = json_encode([
            'symbol' => $symbol,
            'qty' => $qty,
            'price' => $price,
            'side' => $side,
            'time' => $rawTime,
        ]);
        $brokerTradeId = substr(sha1($fingerprint), 0, 32);
    }

    return [
        'trade_date' => $tradeDate,
        'trade_time' => $tradeTime,
        'symbol' => strtoupper((string)$symbol),
        'market' => (string)$market,
        'instrument_type' => $instrumentType,
        'trade_type' => $tradeType,
        'side' => $side,
        'qty' => $qty,
        'entry_price' => $price,
        'exit_price' => null,
        'fees' => $fees ?: 0,
        'leverage' => $leverage ?: 1,
        'status' => 'Open',
        'strategy_tags' => '',
        'notes' => 'Imported from Fyers',
        'broker' => 'fyers',
        'broker_trade_id' => (string)$brokerTradeId,
        'broker_order_id' => $brokerOrderId ? (string)$brokerOrderId : null,
        'broker_payload' => json_encode($item),
    ];
}

function fyers_sync_trades(PDO $pdo): array {
    $config = fyers_config();
    $url = $config['tradebook_url'] ?? '';
    if (!$url) {
        return ['ok' => false, 'error' => 'Fyers tradebook URL is not set in config.php.'];
    }
    if (empty($config['app_id'])) {
        return ['ok' => false, 'error' => 'Fyers app id is not set in config.php.'];
    }

    $tokenRow = fyers_get_token($pdo);
    if (!$tokenRow || empty($tokenRow['access_token'])) {
        return ['ok' => false, 'error' => 'Fyers access token is missing. Update it in Fyers Settings.'];
    }

    $headers = fyers_build_headers($tokenRow['access_token'], $config);
    $resp = fyers_http_get($url, $headers);
    if (!$resp['ok']) {
        $msg = $resp['error'] ?: ('HTTP ' . $resp['status']);
        return ['ok' => false, 'error' => 'Fyers request failed: ' . $msg];
    }

    $data = json_decode($resp['body'], true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Fyers response was not valid JSON.'];
    }

    $items = fyers_extract_trade_items($data);
    if (!$items) {
        return ['ok' => false, 'error' => 'No trades found in Fyers response.'];
    }

    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    $sql = 'INSERT INTO trades (trade_date, trade_time, symbol, market, instrument_type, trade_type, side, qty, entry_price, exit_price, fees, leverage, status, strategy_tags, notes, broker, broker_trade_id, broker_order_id, broker_payload)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              trade_date=VALUES(trade_date),
              trade_time=VALUES(trade_time),
              symbol=VALUES(symbol),
              market=VALUES(market),
              instrument_type=VALUES(instrument_type),
              trade_type=VALUES(trade_type),
              side=VALUES(side),
              qty=VALUES(qty),
              entry_price=VALUES(entry_price),
              exit_price=VALUES(exit_price),
              fees=VALUES(fees),
              leverage=VALUES(leverage),
              status=VALUES(status),
              broker_order_id=VALUES(broker_order_id),
              broker_payload=VALUES(broker_payload),
              updated_at=CURRENT_TIMESTAMP';
    $stmt = $pdo->prepare($sql);

    foreach ($items as $item) {
        if (!is_array($item)) {
            $skipped++;
            continue;
        }
        $trade = fyers_map_trade($item);
        if ($trade['symbol'] === '' || $trade['qty'] === null || $trade['entry_price'] === null) {
            $skipped++;
            continue;
        }

        $params = [
            $trade['trade_date'],
            $trade['trade_time'],
            $trade['symbol'],
            $trade['market'],
            $trade['instrument_type'],
            $trade['trade_type'],
            $trade['side'],
            $trade['qty'],
            $trade['entry_price'],
            $trade['exit_price'],
            $trade['fees'],
            $trade['leverage'],
            $trade['status'],
            $trade['strategy_tags'],
            $trade['notes'],
            $trade['broker'],
            $trade['broker_trade_id'],
            $trade['broker_order_id'],
            $trade['broker_payload'],
        ];

        $stmt->execute($params);
        if ($stmt->rowCount() === 1) {
            $inserted++;
        } else {
            $updated++;
        }
    }

    return [
        'ok' => true,
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
    ];
}
