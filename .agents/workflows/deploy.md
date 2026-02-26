---
description: How to deploy changes to YugoVote production
---

# YugoVote Deployment Workflow

## IMPORTANT RULES
- **NEVER edit files directly on the production server** (no `sed`, `nano`, `vim` on production)
- **ALL changes must go: local edit → git commit → git push → deploy script**
- The SSH alias `yugovote` connects to the production server

## Project Structure
- **Local repo:** `/Users/bmarkovic/Documents/Projects/YugoVote`
- **Theme dir:** `wp-content/themes/hello-elementor-child/`
- **Branch:** `main`
- **Remote:** `origin` → GitHub (`gamatech89/YugoVoteWordpress`)

## Deploy Steps

// turbo-all

1. Make your changes locally in the theme directory
2. Commit and push:
```bash
cd /Users/bmarkovic/Documents/Projects/YugoVote
git add -A && git commit -m "your message" && git push origin main
```
3. Deploy to production:
```bash
ssh yugovote 'cd ~/yugovote-theme && ./deploy-theme.sh'
```

## Production Server Details
- **SSH:** `ssh yugovote` (alias configured in ~/.ssh/config)
- **User:** `u239567293`
- **WP root:** `~/domains/yugovote.com/public_html/`
- **Theme:** `~/domains/yugovote.com/public_html/wp-content/themes/hello-elementor-child/`
- **Deploy script:** `~/yugovote-theme/deploy-theme.sh` (git pull + copy to production)
- **mu-plugins:** `~/domains/yugovote.com/public_html/wp-content/mu-plugins/`

## Key Theme Files
- `inc/account/account-hooks.php` — Login/logout URL filters, login_redirect, registration
- `inc/account/ajax-login.php` — AJAX login handler + login.js enqueue
- `inc/account/templates/login-form.php` — Login form shortcode template
- `page-login.php` — Full page template for `/prijava/` (login page)
- `page-moj-nalog.php` — Account page template
- `js/login.js` — AJAX login JS (intercepts form, shows spinner/errors)

## Custom Login System
- Login page URL: `/prijava/` (page template: `page-login.php`)
- Form posts via AJAX (`login.js`) to `admin-ajax.php` → `ajax-login.php`
- Fallback: form has `action="wp-login.php"` for when JS is disabled
- `login_redirect` filter in `account-hooks.php`: admin → `/wp-admin/`, others → `/moj-nalog/`
- AJAX handler: same logic — admin → `admin_url()`, others → `/moj-nalog/`
- `wp_signon()` uses `is_ssl()` for secure cookies on HTTPS
- `admin_email_check_interval` disabled via mu-plugin to prevent redirect loops

## Page Slugs (defined in account-hooks.php)
- `CUSTOM_LOGIN_PAGE_SLUG` = `prijava`
- `CUSTOM_REGISTER_PAGE_SLUG` = `registracija`
- `CUSTOM_COMPLETE_PROFILE_PAGE_SLUG` = `kompletiranje-naloga`
- `CUSTOM_ACCOUNT_PAGE_SLUG` = `moj-nalog`
