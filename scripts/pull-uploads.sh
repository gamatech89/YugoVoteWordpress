#!/usr/bin/env bash
set -euo pipefail

# Rsyncs uploads from remote into local wp-content/uploads.

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

LOCAL_UPLOADS="$ROOT_DIR/wp-content/uploads"
REMOTE_UPLOADS="$REMOTE_WP_PATH/wp-content/uploads/"

mkdir -p "$LOCAL_UPLOADS"

rsync -avz --progress --delete -e "ssh -p $REMOTE_SSH_PORT" "$REMOTE_SSH_HOST:$REMOTE_UPLOADS" "$LOCAL_UPLOADS/"

echo "Uploads sync complete to $LOCAL_UPLOADS"
