#!/usr/bin/env bash

# Updates only the Laravel/Nginx application container. It deliberately keeps
# the MySQL volume and the Flutter Web files intact.
set -Eeuo pipefail

branch='ops/avatar-pilot-v4'
deploy_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repository_dir="$(cd -- "$deploy_dir/.." && pwd)"

cd "$repository_dir"

if [[ "$(git branch --show-current)" != "$branch" ]]; then
  echo "Este servidor debe estar en la rama $branch; no se cambió nada." >&2
  exit 1
fi

git fetch origin "$branch"
git pull --ff-only origin "$branch"

cd "$deploy_dir"
compose=(docker compose --env-file .env.prototype -f docker-compose.prototype.yml)

"${compose[@]}" up -d --build --no-deps app
"${compose[@]}" exec -T app php artisan migrate --force --no-interaction
"${compose[@]}" exec -T app php artisan optimize
"${compose[@]}" ps

# The certificate is private for the prototype, so this check intentionally
# reaches the local HTTPS endpoint without relying on public DNS.
curl --fail --silent --show-error --insecure https://127.0.0.1/up >/dev/null
echo 'Actualización terminada: https://127.0.0.1/up respondió correctamente.'
