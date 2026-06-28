#!/usr/bin/env bash
set -euo pipefail

# Backup nocturno de la base de datos de produccion: vuelca, comprime,
# copia al NAS por SSH/Tailscale y purga copias locales de mas de 15 dias.

APP_DIR="/opt/marpel-mvp"
LOCAL_BACKUP_DIR="/opt/backups"
NAS_HOST="100.113.26.66"
NAS_USER="admin"
NAS_PATH="/share/Backups-Marpel"
SSH_KEY="/root/.ssh/nas_backup_key"
RETENTION_DAYS=15

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
FILENAME="marpel_db_${TIMESTAMP}.sql.gz"

mkdir -p "$LOCAL_BACKUP_DIR"

cd "$APP_DIR"
docker compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' | gzip > "$LOCAL_BACKUP_DIR/$FILENAME"

scp -i "$SSH_KEY" -P 22 "$LOCAL_BACKUP_DIR/$FILENAME" "$NAS_USER@$NAS_HOST:$NAS_PATH/"

find "$LOCAL_BACKUP_DIR" -name 'marpel_db_*.sql.gz' -mtime "+${RETENTION_DAYS}" -delete

echo "Backup completado: $FILENAME"
