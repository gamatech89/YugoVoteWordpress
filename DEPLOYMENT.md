# Deployment Guide

## Quick Deploy

1. **Push changes to Git:**
   ```bash
   git add -A && git commit -m "your message" && git push
   ```

2. **SSH to server:**
   ```bash
   ssh u239567293@92.112.183.134
   ```

3. **Run deploy script:**
   ```bash
   cd ~/yugovote-theme && ./deploy-theme.sh
   ```

4. **Clear LiteSpeed Cache** in WordPress admin (optional but recommended)

## Server Structure

- **Git repo location:** `~/yugovote-theme/`
- **Production theme:** `/home/u239567293/domains/yugovote.com/public_html/wp-content/themes/hello-elementor-child/`
- **Deploy script:** `~/yugovote-theme/deploy-theme.sh` (pulls from git and copies to production)

## What Gets Deployed

The deploy script:
1. Pulls latest from `origin/main`
2. Copies theme files to production `/wp-content/themes/hello-elementor-child/`

## Notes

- Database changes are NOT deployed via git (use WP admin or direct SQL)
- Uploads folder is NOT in git (synced separately if needed)
- Always clear cache after deploying CSS/JS changes
