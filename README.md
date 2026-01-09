# YugoVote local stack

## Prereqs
- Docker Desktop
- VS Code
- SSH access to prod host (set in .env)

## Setup
1. Copy `.env.example` to `.env` and fill values (REMOTE credentials, DB passwords, LOCAL_DOMAIN).
2. Add hosts entry: `127.0.0.1 yugovote.local` (or your domain).
3. Start stack: `docker compose up -d`.
4. Pull data:
   - `bash scripts/pull-db.sh`
   - `bash scripts/pull-uploads.sh`
5. Open http://yugovote.local (or your chosen domain).

## Notes
- `wp-content/uploads` is ignored by Git; synced via rsync.
- DB dumps stored in `.db/` locally; ignored by Git.
- PHP overrides in `php/uploads.ini`.
- Nginx config in `nginx/conf.d/site.conf`.
