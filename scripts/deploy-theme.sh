#!/bin/bash
# Deploy theme to production server
# Usage: bash scripts/deploy-theme.sh

set -e

# Load environment variables
if [ -f .env ]; then
    source .env
fi

# Server details (can override in .env)
REMOTE_USER="${REMOTE_USER:-u239567293}"
REMOTE_HOST="${REMOTE_HOST:-92.112.183.134}"
REMOTE_PATH="${REMOTE_PATH:-/home/u239567293/domains/yugovote.com/public_html/wp-content/themes/hello-elementor-child}"

# Local theme path
LOCAL_THEME="wp-content/themes/hello-elementor-child/"

echo "🚀 Deploying theme to production..."
echo "   Remote: ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}"

# Sync theme files (excluding dev files)
rsync -avz --delete \
    --exclude '.git' \
    --exclude '.github' \
    --exclude 'node_modules' \
    --exclude '.DS_Store' \
    --exclude '*.log' \
    "${LOCAL_THEME}" \
    "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/"

echo "✅ Theme deployed successfully!"
echo ""
echo "💡 Don't forget to clear cache on the server (LiteSpeed Cache plugin)"
