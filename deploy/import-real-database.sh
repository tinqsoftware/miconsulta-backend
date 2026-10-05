#!/usr/bin/env bash

# Importa un dump seleccionado explícitamente, dejando un backup recuperable
# antes de modificar la base de datos del prototipo.
set -euo pipefail

if [[ "${MI_CONSULTA_CONFIRM_IMPORT:-}" != "YES" ]]; then
  echo "Abortado: ejecuta con MI_CONSULTA_CONFIRM_IMPORT=YES para importar." >&2
  exit 64
fi

if [[ $# -ne 1 ]]; then
  echo "Uso: MI_CONSULTA_CONFIRM_IMPORT=YES $0 /ruta/miconsulta-production.sql" >&2
  exit 64
fi

dump_file="$1"
if [[ ! -f "$dump_file" || ! -r "$dump_file" ]]; then
  echo "No se puede leer el dump: $dump_file" >&2
  exit 66
fi

if ! grep -q '^CREATE TABLE' "$dump_file"; then
  echo "El archivo no parece contener un dump MySQL con tablas." >&2
  exit 65
fi

cd "$(dirname "$0")"
if [[ ! -f .env.prototype ]]; then
  echo "Falta deploy/.env.prototype." >&2
  exit 78
fi

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_dir="./backups"
backup_file="${backup_dir}/miconsulta-before-import-${timestamp}.sql"
mkdir -p "$backup_dir"

compose=(docker compose --env-file .env.prototype -f docker-compose.prototype.yml)
app_stopped=0

reset_database() {
  "${compose[@]}" exec -T database sh -lc \
    'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`$MYSQL_DATABASE\`; CREATE DATABASE \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
}

restore_previous_database() {
  echo "Restaurando el backup automático tras un error..." >&2
  reset_database || return 1
  "${compose[@]}" exec -T database sh -lc \
    'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < "$backup_file"
}

cleanup() {
  status=$?
  trap - EXIT

  if (( status != 0 && app_stopped == 1 )); then
    restore_previous_database || echo "La restauración automática falló; conserva ${backup_file}." >&2
  fi

  if (( app_stopped == 1 )); then
    "${compose[@]}" up -d --no-deps app >/dev/null || true
  fi

  exit "$status"
}

echo "Creando backup en ${backup_file}..."
"${compose[@]}" exec -T database sh -lc \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > "$backup_file"

if [[ ! -s "$backup_file" ]]; then
  echo "El backup quedó vacío; no se importó nada." >&2
  exit 74
fi

echo "Deteniendo temporalmente la aplicación durante el reemplazo..."
"${compose[@]}" stop app
app_stopped=1
trap cleanup EXIT

echo "Recreando la base de datos para el dump seleccionado..."
reset_database

echo "Importando ${dump_file}..."
"${compose[@]}" exec -T database sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < "$dump_file"

echo "Aplicando migraciones posteriores al dump..."
"${compose[@]}" run --rm --no-deps --entrypoint php app artisan migrate --force --no-interaction

"${compose[@]}" up -d --no-deps app
app_stopped=0
trap - EXIT

echo "Importación terminada. Backup recuperable: ${backup_file}"
