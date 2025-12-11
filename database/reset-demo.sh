#!/bin/bash
# Reset demo database - run via daily cronjob to clear spam
# Example crontab entry: 0 3 * * * /path/to/realworld-ludicrous/database/reset-demo.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

# Copy empty database template (schema already applied)
cp database/database.empty.sqlite database/database.sqlite

# Seed with test data
php database/seed.php

echo "Database reset complete: $(date)"
