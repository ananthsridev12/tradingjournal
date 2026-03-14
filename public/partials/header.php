<?php
require_once __DIR__ . '/../../includes/helpers.php';
$title = $title ?? 'Trading Journal';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">Trading Journal</div>
    <nav class="nav">
      <a href="index.php">Dashboard</a>
      <a href="trades.php">Trades</a>
      <a href="trade_form.php">Add Trade</a>
      <a href="deposits.php">Deposits</a>
      <a href="summary.php">Summary</a>
      <a href="fyers_settings.php">Fyers</a>
    </nav>
  </header>
  <main class="container">
