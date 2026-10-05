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

echo "Creando backup en ${backup_file}..."
"${compose[@]}" exec -T database sh -lc \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > "$backup_file"

if [[ ! -s "$backup_file" ]]; then
  echo "El backup quedó vacío; no se importó nada." >&2
  exit 74
fi

echo "Importando ${dump_file}..."
"${compose[@]}" exec -T database sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < "$dump_file"

echo "Aplicando migraciones posteriores al dump..."
"${compose[@]}" exec -T app php artisan migrate --force --no-interaction
"${compose[@]}" exec -T app php artisan optimize

echo "Importación terminada. Backup recuperable: ${backup_file}"
