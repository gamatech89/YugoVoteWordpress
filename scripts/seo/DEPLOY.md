# SEO etapa 1: puštanje na produkciju

Sve komande se kucaju iz korena repoa (`~/Documents/Projects/YugoVote`), na grani `seo-etapa1`.

## 0. Provera pre puštanja (već urađeno 2026-11-15)

- Probni rsync (`-n --delete --checksum`) pokazao je da se produkciona tema razlikuje od `main` **samo** po
  novim/izmenjenim SEO fajlovima. Deploy ne briše ništa što postoji samo na serveru.
- Lokalno prolaze svi testovi (komande u koraku 5).

## 1. Spoji granu i pošalji na GitHub

```bash
git checkout main && git merge --ff-only seo-etapa1 && git push origin main
```

## 2. Deploy teme

```bash
bash scripts/deploy-theme.sh
```

## 3. Očisti keš

```bash
ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp litespeed-purge all'
```

## 4. Provera na živom sajtu

```bash
bash tests/seo/check-head.sh https://yugovote.com
```

Očekuje se `SVE PASS`: lang sr-Latn, naslovi `… | YugoVote`, opisi, OG/Twitter, noindex na pitanjima i
prijavi, čist sitemap.

## 5. Import ručno pisanih naslova i opisa

```bash
python3 -I scripts/seo/validate-meta.py scripts/seo/meta-etapa1.json <(ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file -' < scripts/seo/export-content.php)
python3 -I scripts/seo/bundle-import.py scripts/seo/meta-etapa1.json > /tmp/seo-import-bundle.php

# probni krug: ništa ne upisuje, samo izveštaj
ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file - dry-run' < /tmp/seo-import-bundle.php

# pravi upis (samo prazna polja; ručne izmene ostaju)
ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file -' < /tmp/seo-import-bundle.php

ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp litespeed-purge all'
```

Validator na prvoj liniji može da prijavi `nedostaje` za liste dodate posle 15.11. One nemaju ručni opis i
dobijaju automatski. To nije greška.

## 6. Share kartice

Otvori i klikni "Scrape Again":
- https://developers.facebook.com/tools/debug/?q=https%3A%2F%2Fyugovote.com%2Flista%2Fnajbolji-crnogorski-fudbaleri%2F
- https://developers.facebook.com/tools/debug/?q=https%3A%2F%2Fyugovote.com%2F

## Povratak (ako nešto ne valja)

- Kod: `git revert <merge commit>` → `git push` → `bash scripts/deploy-theme.sh` → purge.
- Podaci: brisanje svih uvezenih polja (ručne izmene urednika bi takođe nestale):
  ```bash
  ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval "global \$wpdb; \$wpdb->query(\"DELETE FROM {\$wpdb->postmeta} WHERE meta_key IN (\\\"_ygv_seo_title\\\",\\\"_ygv_seo_desc\\\",\\\"_ygv_seo_noindex\\\")\"); \$wpdb->query(\"DELETE FROM {\$wpdb->termmeta} WHERE meta_key IN (\\\"_ygv_seo_title\\\",\\\"_ygv_seo_desc\\\",\\\"_ygv_seo_noindex\\\")\");"'
  ```

## Lokalni testovi

```bash
php tests/seo/run-tests.php
bash tests/seo/check-head.sh http://yugovote.local:8080
docker compose cp tests/seo/test-admin-save.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-admin-save.php
docker compose cp tests/seo/test-context.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-context.php
docker compose cp scripts/seo/import-meta.php wordpress:/tmp/ && docker compose cp tests/seo/test-import.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/test-import.php
```
