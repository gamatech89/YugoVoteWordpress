#!/bin/bash
# Deploy theme to production server
# Usage: bash scripts/deploy-theme.sh

set -e

# Load environment variables
if [ -f .env ]; then
    source .env
fi

# Server details - uses SSH alias 'yugovote' from ~/.ssh/config
# (Host: 82.25.98.202, Port: 65002, User: u239567293)
REMOTE_HOST="yugovote"
REMOTE_PATH="${REMOTE_PATH:-/home/u239567293/domains/yugovote.com/public_html/wp-content/themes/hello-elementor-child}"

# Local theme path
LOCAL_THEME="wp-content/themes/hello-elementor-child/"

# Ne šaljemo nesačuvane izmene (npr. css/category-colors.css koji generator prepiše iz lokalne baze).
if [ "${ALLOW_DIRTY:-0}" != "1" ] && [ -n "$(git status --porcelain -- "${LOCAL_THEME}")" ]; then
    echo "❌ Tema ima izmene koje nisu u git-u — deploy prekinut:"
    git status --porcelain -- "${LOCAL_THEME}"
    echo "   Commit-uj ih ili vrati (git checkout -- <fajl>). Svesno preskakanje: ALLOW_DIRTY=1 bash scripts/deploy-theme.sh"
    exit 1
fi

echo "🚀 Deploying theme to production..."
echo "   Remote: ${REMOTE_HOST}:${REMOTE_PATH}"

# Sync theme files (excluding dev files)
rsync -avz --delete \
    --exclude '.git' \
    --exclude '.github' \
    --exclude 'node_modules' \
    --exclude '.DS_Store' \
    --exclude '*.log' \
    "${LOCAL_THEME}" \
    "${REMOTE_HOST}:${REMOTE_PATH}/"

echo "✅ Theme deployed successfully!"
echo ""
echo "💡 Don't forget to clear cache on the server (LiteSpeed Cache plugin)"
