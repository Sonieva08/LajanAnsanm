#!/usr/bin/env bash
# Sauvegarde quotidienne de la base. Cron (tous les jours à 2h) :
#   0 2 * * * /var/www/lajanansanm/deploy/backup.sh >> /var/log/lajan-backup.log 2>&1
set -euo pipefail

DB_NAME="lajanansanm"
DB_USER="lajan_user"
BACKUP_DIR="/var/backups/lajanansanm"
KEEP_DAYS=30

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

# Le mot de passe est lu dans ~/.my.cnf ([client] user= / password=), jamais en ligne de commande
mysqldump --single-transaction --routines "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_$(date +%F).sql.gz"

find "$BACKUP_DIR" -name "${DB_NAME}_*.sql.gz" -mtime +$KEEP_DAYS -delete
echo "$(date -Is) sauvegarde OK"
