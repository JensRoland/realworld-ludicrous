#!/bin/bash
# Reset demo database - run via daily cronjob to clear spam.
# Wipes all rows in-place (via SQL) and reseeds from YAML, so PHP-FPM
# workers holding persistent SQLite connections see the new state
# immediately — no file replacement, no stale WAL hazards.
# Example crontab entry: 0 3 * * * /path/to/realworld-ludicrous/database/reset-demo.sh

set -euo pipefail

# cron runs with a minimal PATH; make sure php and friends are findable.
export PATH="/usr/local/bin:/usr/bin:/bin:${PATH:-}"

# cPanel serves the domain under EasyApache PHP 8.4; the shell's bare `php`
# may be an older system build that fails composer's platform check. Pin to
# ea-php84 (override with PHP_BIN=/path/to/php if you move PHP versions).
PHP_BIN="${PHP_BIN:-ea-php84}"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "Using PHP: $("$PHP_BIN" -r 'echo PHP_BINARY . " (" . PHP_VERSION . ")";')"
"$PHP_BIN" database/seed.php --reset

echo "Database reset complete: $(date)"
