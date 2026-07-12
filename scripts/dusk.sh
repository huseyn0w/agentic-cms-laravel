#!/usr/bin/env bash
#
# Run Laravel Dusk (browser / e2e) tests against a DEDICATED database in the
# dockerised MySQL, served on the host at http://127.0.0.1:8000. Host Chrome
# drives it. The dev `agentic_cms_laravel` DB is never touched.
#
# Usage:
#   bash scripts/dusk.sh                 # run the whole browser suite
#   bash scripts/dusk.sh --filter=Login  # pass extra args through to `artisan dusk`
#
# Requirements: Docker stack up (`make up`), Google Chrome installed on the host,
# host PHP with pdo_mysql + imagick.
set -euo pipefail
cd "$(dirname "$0")/.."

DUSK_DB="${DUSK_DB:-agentic_cms_laravel_dusk}"
DUSK_PORT="${DUSK_PORT:-8000}"

# 1. Bootstrap the dusk env file from the example if it is missing.
if [ ! -f .env.dusk.local ]; then
    echo "→ creating .env.dusk.local from .env.dusk.local.example"
    cp .env.dusk.local.example .env.dusk.local
fi

# 2. Ensure the dedicated Dusk database + privileges exist (idempotent).
echo "→ ensuring database '${DUSK_DB}' exists"
docker compose exec -T mysql sh -c \
    "mysql -uroot -p\"\${MYSQL_ROOT_PASSWORD:-rootsecret}\" -e \
    'CREATE DATABASE IF NOT EXISTS ${DUSK_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
     GRANT ALL PRIVILEGES ON ${DUSK_DB}.* TO \"agentic_cms_laravel\"@\"%\"; FLUSH PRIVILEGES;'" 2>/dev/null

# 3. Match ChromeDriver to the installed Chrome, then fresh schema + seed.
php artisan dusk:chrome-driver --detect >/dev/null 2>&1 || true
echo "→ migrating + seeding ${DUSK_DB}"
php artisan migrate:fresh --seed --env=dusk.local --force >/dev/null

# 4. Serve the app (host) with the dusk env; stop it on exit.
echo "→ serving app on http://127.0.0.1:${DUSK_PORT}"
php artisan serve --env=dusk.local --port="${DUSK_PORT}" --no-reload >/tmp/agentic-cms-laravel-dusk-serve.log 2>&1 &
SERVE_PID=$!
trap 'kill "${SERVE_PID}" 2>/dev/null || true' EXIT

for _ in $(seq 1 30); do
    curl -sf -o /dev/null "http://127.0.0.1:${DUSK_PORT}" && break || sleep 0.5
done

# 5. Run the browser tests (pass through any extra args).
echo "→ running Dusk"
php artisan dusk "$@"
