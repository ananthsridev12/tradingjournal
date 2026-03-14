# Trading Journal (V1)

Simple PHP + MySQL web app for tracking trades, P/L, strategy tags, summaries, charts, and deposits.

## Setup
1. Create a MySQL database and user on your hosting.
2. Update `config.php` with your DB credentials.
3. Import `db.sql` into your database.
4. Point your web root to `public` (recommended), or place the contents of `public` in your hosting root.

## Features (V1)
- Add/edit trades
- Auto P/L calculation for closed trades
- Strategy tags
- Intraday vs Swing
- Leverage input (1x, 2x, 5x)
- Daily & weekly summaries
- Charts
- Deposit tracking

## Next Steps
- Strategy library + per-strategy analytics
- Open trade unrealized P/L
- Import tools (CSV/Excel)
- Attach screenshots and notes
- Risk metrics and R-multiples

## Fyers Sync (Optional)
1. Add your Fyers Tradebook URL and app id (if required) in `config.php`.
2. Import the updated `db.sql` or run the schema changes in your database.
3. Open `Fyers` in the top navigation and paste your access token.
4. Use the `Sync Fyers` button on the Trades page.

## Fyers Auth Code (Helper Page)
1. Set `fyers_redirect_uri` in `config.php` (must match your Fyers app settings).
2. Open `Fyers` in the top nav, then click `Generate Auth Code`.
3. Build the login URL, login, and approve.
4. Paste the auth code, enter your secret id, and generate the access token.
