#!/usr/bin/env bash
set -euo pipefail

# Pulls a fresh DB dump from the remote host and imports into local MariaDB.
# Uses mysqldump over SSH (streams; no dump file left on remote).
# Requires .env with REMOTE_* values and local containers running.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ ! -f "$ROOT_DIR/.env" ]; then
  echo ".env not found; copy .env.example to .env and fill values." >&2
  exit 1
fi

# shellcheck disable=SC2046
export $(grep -v '^#' "$ROOT_DIR/.env" | xargs)

# Default SSH port
: "${REMOTE_SSH_PORT:=22}"

: "${REMOTE_SSH_HOST:?REMOTE_SSH_HOST not set}"
: "${REMOTE_WP_PATH:?REMOTE_WP_PATH not set}"
: "${REMOTE_DB_NAME:?REMOTE_DB_NAME not set}"
: "${REMOTE_DB_USER:?REMOTE_DB_USER not set}"
: "${REMOTE_DB_PASS:?REMOTE_DB_PASS not set}"

DUMP_NAME="yugovote-prod.sql"
LOCAL_DUMP_PATH="$ROOT_DIR/.db/$DUMP_NAME"

mkdir -p "$ROOT_DIR/.db"

echo "Streaming mysqldump from $REMOTE_SSH_HOST:$REMOTE_DB_NAME ..."

ssh -p "$REMOTE_SSH_PORT" "$REMOTE_SSH_HOST" \
  "mysqldump -u'$REMOTE_DB_USER' -p'$REMOTE_DB_PASS' '$REMOTE_DB_NAME'" \
  | tee "$LOCAL_DUMP_PATH" \
  | docker compose -f "$ROOT_DIR/docker-compose.yml" exec -T db mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"

echo "DB import complete; local copy at $LOCAL_DUMP_PATH"
