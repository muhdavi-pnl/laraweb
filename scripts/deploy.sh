#!/usr/bin/env bash
# Runs ON the server from the application directory. Usage: scripts/deploy.sh [git-ref]
set -euo pipefail

# Everything lives in a function and the script exits explicitly: `git reset` below rewrites this very
# file, and bash must not keep reading a changed script from disk.
main() {
    local ref="${1:-origin/main}"
    cd "$(dirname "$0")/.."

    echo "==> Deploying ${ref}"
    php artisan down --retry=30 --refresh=15 || true
    trap 'php artisan up || true' EXIT

    git fetch --prune origin
    git reset --hard "${ref}"

    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan event:cache

    # Workers finish their current job, then supervisor restarts them on the new code.
    php artisan queue:restart

    echo "==> Done"
}

main "$@"
exit $?
