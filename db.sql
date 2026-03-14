CREATE DATABASE IF NOT EXISTS trading_journal
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE trading_journal;

CREATE TABLE IF NOT EXISTS trades (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  trade_date DATE NOT NULL,
  trade_time TIME NULL,
  symbol VARCHAR(32) NOT NULL,
  market VARCHAR(32) NOT NULL DEFAULT 'NSE',
  instrument_type ENUM('Equity','Futures','Options','Other') NOT NULL DEFAULT 'Equity',
  trade_type ENUM('Intraday','Swing') NOT NULL DEFAULT 'Intraday',
  side ENUM('Buy','Sell') NOT NULL DEFAULT 'Buy',
  qty DECIMAL(18,4) NOT NULL,
  entry_price DECIMAL(18,4) NOT NULL,
  exit_price DECIMAL(18,4) NULL,
  fees DECIMAL(18,4) NOT NULL DEFAULT 0,
  leverage DECIMAL(6,2) NOT NULL DEFAULT 1,
  status ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
  strategy_tags VARCHAR(255) NULL,
  notes TEXT NULL,
  broker VARCHAR(16) NULL,
  broker_trade_id VARCHAR(64) NULL,
  broker_order_id VARCHAR(64) NULL,
  broker_payload TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_broker_trade (broker, broker_trade_id),
  INDEX idx_trade_date (trade_date),
  INDEX idx_symbol (symbol),
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS deposits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deposit_date DATE NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  type ENUM('Deposit','Withdrawal') NOT NULL DEFAULT 'Deposit',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_deposit_date (deposit_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS broker_tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  broker VARCHAR(16) NOT NULL,
  access_token TEXT NOT NULL,
  expires_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_broker (broker)
) ENGINE=InnoDB;
