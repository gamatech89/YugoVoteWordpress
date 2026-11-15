# YugoVote SEO Etapa 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Child-theme SEO module (titles, descriptions, OG/Twitter, lang, noindex, sitemap cleanup, SEO meta box), plus ~845 hand-written titles and descriptions with a safe import script.

**Architecture:** New folder `inc/seo/` in the child theme. Pure text helpers are kept apart from the WP-dependent code so they can be unit-tested with plain PHP CLI. One resolver function turns the current request into an SEO context array, and the head, robots and admin code all read from it. Content work happens outside the theme: export through WP-CLI (read-only) → JSON written in batches → Python validator → `wp eval-file` import that only fills empty fields.

**Tech Stack:** PHP 8.2 (prod/local container), WordPress 6.x core filters (`wp_robots`, `wp_sitemaps_*`, `pre_get_document_title`), WP-CLI 2.12 on prod, Docker local stack (`docker compose`, nginx :8080), Python 3 for validation.

**Spec:** `docs/superpowers/specs/2026-11-15-yugovote-seo-etapa1-design.md`

## Global Constraints

- Theme path: `wp-content/themes/hello-elementor-child/` (THEME below). Function prefix `ygv_seo_`.
- Meta keys (post meta and term meta): `_ygv_seo_title`, `_ygv_seo_desc`, `_ygv_seo_noindex` (`'1'` = hide).
- Title format `{naslov} | YugoVote`; separator `|`. Manual title stored WITHOUT the suffix.
- Description target 120–155 chars, hard max 155 (mb chars). Manual title target ≤ 50 chars.
- `lang="sr-Latn"` via `language_attributes`; WP locale stays `en_US`; `og:locale` = `sr_RS`.
- `og:type`: `website` for front page and archives, `article` for singular. `twitter:card` = `summary_large_image`.
- Fallback share image: THEME `assets/images/og-default.jpg`, 1200×630.
- Module outputs nothing if `WPSEO_VERSION` or `RANK_MATH_VERSION` is defined. Always sets `hello_elementor_description_meta_tag` → false.
- Noindex post types: `question`, `quiz_levels`, `post`. Noindex taxonomies: `question_category`, `category`. Also author, search, 404, lists with `_is_tournament_match`, any object with `_ygv_seo_noindex = '1'`, and page slugs: `test`, `test-vip`, `pocetna-old`, `moj-nalog`, `uredi-profil`, `prijava`, `registracija`, `zaboravljena-lozinka`, `kompletiranje-naloga`.
- Sitemap: drop post types `question`, `quiz_levels`, `post`; drop taxonomies `question_category`, `category`; drop the `users` provider; exclude noindexed posts and pages.
- Copy rules: latinica, ekavica, no emoji, no ALL CAPS, no AI clichés (`otkrijte`, `otkrij`, `fascinantn`, `zaronite`, `ultimativn`, `neverovatan svet`), no duplicates.
- Production: NO deploy and NO DB writes without explicit user OK. Read-only WP-CLI on prod is allowed.

## Review Focus

1. Post content with HTML entities, shortcodes, Elementor markup or `<!-- -->` comments → description is clean text, not double-encoded (`&#8211;` must become `–`), no `[shortcode]` left.
2. Titles and descriptions containing `"`, `&`, `<` → correctly escaped in `content=""` attributes, no broken HTML.
3. A list with no intro, no items or no votes → non-empty description with no dangling punctuation (`", ."`, `": "`).
4. Archive URLs with query params or pagination (`/kvizovi/?difficulty=hard`, `/page/2/`) → canonical points to the clean archive URL, robots unchanged.
5. A multibyte string with no spaces near the cut point (č, ć, ž, đ, or Cyrillic) → cut by mb chars, never a broken UTF-8 byte; ends with `…`.

---

### Task 1: Text helpers + test harness

**Files:**
- Create: THEME `inc/seo/seo-text.php`
- Create: `tests/seo/run-tests.php` (repo root, not deployed)

**Interfaces:**
- Produces:
  - `ygv_seo_clean(string $html): string`: strips shortcodes (regex `\[[^\]]*\]`), HTML comments and tags, decodes entities (`html_entity_decode(..., ENT_QUOTES|ENT_HTML5, 'UTF-8')`), collapses whitespace, trims.
  - `ygv_seo_truncate(string $text, int $max = 155): string`: returns as-is if `mb_strlen <= $max`. Otherwise cuts at the last space at or before `$max-1`. If that space falls before `$max*0.6`, cuts hard at `$max-1` instead. Strips trailing punctuation `,;:–-`, then appends `…`.
  - `ygv_seo_join_names(array $names): string`: drops empty values, then joins: `"A"`, `"A i B"`, `"A, B i C"`.
  - `ygv_seo_list_desc(string $intro, array $names, int $max = 155): string`: Builds `"{introPart} {Names}. Glasaj!"`. The `introPart` is the intro cut so that the whole string fits in `$max`. If the intro is empty, it is `"Rang lista: {Names}. Glasaj!"`. If names are empty, it is just the intro truncated to `$max`.
- No WordPress functions inside (pure PHP + mbstring).

- [ ] **Step 1: Write the failing tests** in `tests/seo/run-tests.php`: a tiny `t($name, $got, $want)` assert that prints PASS/FAIL and exits 1 on any failure. It `require`s THEME `inc/seo/seo-text.php` after `define('ABSPATH', '/')`. Cases:
  - `ygv_seo_clean('Rock &#8211; <b>pop</b> [gallery id="1"] <!-- x -->  ok')` === `'Rock – pop ok'`
  - `ygv_seo_truncate(str_repeat('ab ', 100), 155)`: `mb_strlen <= 155`, ends with `…`
  - `ygv_seo_truncate(str_repeat('ž', 200), 155)`: `mb_strlen === 155`, `mb_check_encoding(..., 'UTF-8')`, ends with `…` (Review Focus 5)
  - `ygv_seo_truncate('Kratko.', 155)` === `'Kratko.'`
  - `ygv_seo_join_names(['Savićević','Mijatović','Vučinić'])` === `'Savićević, Mijatović i Vučinić'`; `(['A',''])` === `'A'`; `([])` === `''`
  - `ygv_seo_list_desc('', [], 155)` === `''`; `ygv_seo_list_desc('', ['A','B'])` === `'Rang lista: A i B. Glasaj!'` (Review Focus 3)
  - `ygv_seo_list_desc(str_repeat('reč ', 80), ['Savićević','Mijatović','Vučinić'])`: `<= 155`, ends with `'Savićević, Mijatović i Vučinić. Glasaj!'`, contains no `' .'` and no `',.'`
- [ ] **Step 2: Run** `php tests/seo/run-tests.php`. Expected: fatal error / FAIL (file missing).
- [ ] **Step 3: Implement** `inc/seo/seo-text.php` with the four functions above (ABSPATH guard at top).
- [ ] **Step 4: Run** `php tests/seo/run-tests.php`. Expected: all PASS, exit 0.
- [ ] **Step 5: Commit** `git add tests/seo THEME/inc/seo/seo-text.php && git commit -m "SEO: tekst helperi + testovi"`

### Task 2: Context resolver + head output (title, description, OG, lang, canonical)

**Files:**
- Create: THEME `inc/seo/seo-init.php` (loader + plugin guard), `inc/seo/seo-context.php`, `inc/seo/seo-head.php`, `inc/seo/seo-static.php`
- Modify: THEME `inc/init.php` (add `require_once .../inc/seo/seo-init.php` after the helpers line)
- Create: `tests/seo/check-head.sh` (integration, curl-based)

**Interfaces:**
- Consumes: Task 1 functions.
- Produces:
  - `ygv_seo_static_copy(): array`, keyed `'home'`, `'archive_voting_list'`, `'archive_quiz'`, `'archive_yuv_showdown'`, `'archive_vip_person'` → `['title' => string, 'desc' => string]`. Claude writes the copy (Global Constraints copy rules apply).
  - `ygv_seo_list_top_names(int $list_id, int $n = 3): array`: item titles ordered by `SUM(vote_value)` from `{prefix}voting_list_votes` for this list, restricted to IDs in meta `_voting_items`; falls back to `_voting_items` order when there are no votes. Cached in transient `ygv_seo_top_{$list_id}` for 12 h, deleted on `save_post_voting_list`.
  - `ygv_seo_auto_desc_post(WP_Post $p): string`: per-type rules from the spec (list / item / quiz `_quiz_description` else `"Kviz: {title}. {N} pitanja. Proveri koliko znaš."` with N from `_num_questions` / poll content + first 3 answers from `_cs_poll_answers` / showdown title + item names from `_yuv_showdown_items` / page: cleaned content / fallback: site tagline copy `home.desc`).
  - `ygv_seo_auto_desc_term(WP_Term $t): string`: cleaned term description, else `"{name}: liste i kvizovi na YugoVote. Glasaj i reci svoje."`
  - `ygv_seo_context(): array`, keys `title` (full, with suffix), `desc`, `url`, `type` (`website|article`), `image` (`['url','width','height']`), `noindex` (bool; Task 3 fills it in, Task 2 returns false). Memoized per request in a static var.
- Head output in `seo-head.php`: `pre_get_document_title` returns `ctx.title`; `document_title_separator` → `'|'`; `language_attributes` replaces `lang="…"` with `lang="sr-Latn"`; `wp_head` priority 1 echoes description, og:*, twitter:* (all values through `esc_attr`, URLs through `esc_url`); canonical only for `is_tax()`/`is_post_type_archive()`, built from `get_term_link` / `get_post_type_archive_link` (no query string, no `/page/N/`) (Review Focus 4).
- Image resolution: singular with thumbnail → `wp_get_attachment_image_src(thumb, 'large')`; voting_items without thumbnail → its `custom_image_url` from the pivot table is NOT used (external images, skip); otherwise `og-default.jpg` with 1200×630.

- [ ] **Step 1: Bring up local stack:** `open -a Docker`, wait for the daemon, `docker compose up -d`, then confirm `curl -s -o /dev/null -w '%{http_code}' -H "Host: $LOCAL_DOMAIN" http://localhost:8080/` returns 200 (LOCAL_DOMAIN from `.env`; check `siteurl` in the local DB and use that host).
- [ ] **Step 2: Write `tests/seo/check-head.sh BASE`.** It picks real local URLs (first list, item, quiz, poll, list category, `/kvizovi/`, home) from `BASE/wp-sitemap-posts-*-1.xml` and asserts per URL:
  - exactly one `<meta name="description"`, non-empty
  - `og:title`, `og:description`, `og:image`, `og:url`, `twitter:card` are present
  - `<html lang="sr-Latn"`
  - `<title>` ends with `| YugoVote`
  - `/kvizovi/?difficulty=hard` has `rel="canonical"` with no `?`

  Exits 1 on any failure.
- [ ] **Step 3: Run** it against local. Expected: FAIL (no description and no og tags).
- [ ] **Step 4: Implement** the four files and the init.php require.
- [ ] **Step 5: Run** `bash tests/seo/check-head.sh <local base>` → all PASS. Also run `php -l` on every new file and `php tests/seo/run-tests.php` → PASS.
- [ ] **Step 6: Escaping check (Review Focus 2):** temporarily set a local list's `_ygv_seo_title` to `Rock & "pop" <3` through the container. Confirm the HTML has `content="Rock &amp; &quot;pop&quot; &lt;3 | YugoVote"`, then delete the meta.
- [ ] **Step 7: Commit** `"SEO: meta opisi, OG/Twitter kartice, lang sr-Latn, naslovi"`

### Task 3: Noindex + sitemap cleanup

**Files:**
- Create: THEME `inc/seo/seo-robots.php`; Modify: `seo-context.php` (`noindex` key), `seo-init.php` (require)
- Modify: `tests/seo/check-head.sh` (add robots/sitemap assertions)

**Interfaces:**
- Consumes: `ygv_seo_context()`.
- Produces: `ygv_seo_is_noindex_post(WP_Post $p): bool` and `ygv_seo_is_noindex_term(WP_Term $t): bool`. They follow the Global Constraints rules; `ygv_seo_noindex_page_slugs(): array` returns the slug list. Hooks: `wp_robots` (adds `noindex => true, follow => true` when `ctx.noindex`), `wp_sitemaps_post_types`, `wp_sitemaps_taxonomies`, `wp_sitemaps_add_provider` (users → false), and `wp_sitemaps_posts_query_args` (adds a `meta_query` that excludes `_ygv_seo_noindex = '1'`; for `page` adds `post__not_in` = IDs of the slug list; for `voting_list` excludes `_is_tournament_match = '1'`).

- [ ] **Step 1: Add failing assertions** to `check-head.sh`:
  - a `/question/…` URL and `/prijava/` contain `noindex`
  - list, item and quiz pages do NOT contain `noindex`
  - `/wp-sitemap.xml` does not contain `posts-question`, `posts-quiz_levels`, `posts-post-`, `users-`, `taxonomies-question_category`, `taxonomies-category`
  - `wp-sitemap-posts-page-1.xml` does not contain `/prijava/`
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement.** For the question URL in the test, read one from the local `wp-sitemap-posts-question-1.xml` before implementing, and hardcode it into the script as a fallback.
- [ ] **Step 4: Run** → PASS; `php -l`; unit tests PASS.
- [ ] **Step 5: Commit** `"SEO: noindex za prazne/tehničke stranice + čišćenje sitemap-a"`

### Task 4: SEO meta box (posts) + term fields

**Files:**
- Create: THEME `inc/seo/seo-admin.php`, `css/seo-admin.css` (small, enqueued only on post.php/post-new.php/term.php for the covered screens)
- Create: `tests/seo/test-admin-save.php` (run inside the container: `docker compose exec wordpress php /var/www/html/wp-content/../tests…`; the repo root is NOT mounted, so copy it in with `docker compose cp`, or mount through `wp-content` temporarily. Executor's choice.)

**Interfaces:**
- Consumes: `ygv_seo_auto_desc_post`, `ygv_seo_auto_desc_term`, the context title logic (expose `ygv_seo_auto_title_post(WP_Post $p): string` and `ygv_seo_auto_title_term(WP_Term $t): string` from seo-context.php, both without suffix).
- Produces: meta box id `ygv_seo_box`, `side` context, on post types `voting_list, voting_items, quiz, voting_poll, yuv_showdown, vip_person, page`. Field names `ygv_seo_title`, `ygv_seo_desc`, `ygv_seo_noindex`; nonce `ygv_seo_nonce` / action `ygv_seo_save`. Save: `save_post` with nonce + `current_user_can('edit_post')` + not autosave/revision; `sanitize_text_field`; empty → `delete_post_meta`. Term fields on `voting_list_category, quiz_category, voting_item_category` (`{tax}_edit_form_fields`, `edited_{tax}`, `current_user_can('edit_term')`). The UI shows live counters (title target 50, desc 120–155, red above 155) and a preview of the auto title/desc (inline JS, vanilla, ~20 lines).

- [ ] **Step 1: Write the failing test `test-admin-save.php`** (loads `wp-load.php`, picks a list ID, sets `$_POST` with a valid nonce while logged in as admin through `wp_set_current_user(1)`, and calls the save handler `ygv_seo_save_post($post_id)`). Asserts:
  - meta saved
  - an empty field deletes the meta
  - a bad nonce changes nothing
  - the same for a term through `ygv_seo_save_term($term_id)`
- [ ] **Step 2: Run** → FAIL (handlers undefined).
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** → PASS. Then load the list edit screen in the local admin (curl with an auth cookie, or the browser). Confirm the box renders with the preview text.
- [ ] **Step 5: Commit** `"SEO: SEO kutija u adminu za postove i kategorije"`

### Task 5: Default share image

**Files:** Create THEME `assets/images/og-default.jpg` (1200×630, < 200 KB).

- [ ] **Step 1:** Build it with Python/PIL from the site logo (look in `wp-content/uploads` for the logo used in the header, or `assets/images/mascot.svg` rasterized). Background in the site brand colors (read from THEME `css/` variables). No drawn text unless a font with diacritics is available locally.
- [ ] **Step 2:** Verify with `python3 -c "from PIL import Image; im=Image.open(p); print(im.size)"` → `(1200, 630)` and check the file size.
- [ ] **Step 3: Commit** `"SEO: podrazumevana share slika"`

### Task 6: Content export (prod, read-only)

**Files:** Create `scripts/seo/export-content.php` (run with `ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp eval-file -' < scripts/seo/export-content.php > SCRATCH/export.json`)

**Interfaces:**
- Produces: a JSON array of `{id, kind: post|term, type, title, url, intro (cleaned, ≤ 600 chars), top (≤ 8 item titles by points; lists/showdowns), answers (polls), num_questions, difficulty, quiz_desc, term_desc, count}`. It covers the published `voting_list`, `quiz`, `voting_poll`, `yuv_showdown`, `vip_person` posts, the public pages from the spec, and the terms of `voting_list_category` and `quiz_category`.

- [ ] **Step 1:** Write the script (no writes, only `get_posts`/`$wpdb->get_results` SELECT).
- [ ] **Step 2:** Run it. Verify the counts with `python3 -c` → voting_list 616, quiz 152, voting_poll 28, yuv_showdown 8, vip_person 2, terms 26 + 6, plus the pages.
- [ ] **Step 3: Commit** only the script (the export stays in the scratchpad).

### Task 7: Write titles and descriptions

**Files:** Create `scripts/seo/meta-etapa1.json` (array of `{id, kind, type, title, desc}`; `title` without suffix, `"noindex": true` optional), `scripts/seo/validate-meta.py`, `scripts/seo/STIL.md` (voice guide + 20 approved-by-Claude examples; the user reviews it in the morning).

- [ ] **Step 1: Validator first** — `validate-meta.py meta.json export.json` checks:
  - every export id is covered exactly once
  - desc is 120–155 chars
  - title is ≤ 50 chars (lists allowed ≤ 55 if the original name is longer; report it, not fail)
  - no Cyrillic
  - no `ije`/`je` ijekavian markers from the forbidden list (`lijep`, `bije`, `vrijem`, `mjest`, `pjesm`, `djet`, `rijeke` etc. — a warning list, manually triaged since some proper names contain them)
  - no banned phrases from Global Constraints
  - no emoji
  - no duplicate desc
  - no desc equal to the intro start

  Exits 1 on any hard failure.
- [ ] **Step 2:** Run it on an empty JSON → FAIL (coverage).
- [ ] **Step 3:** Write `STIL.md` with ~20 samples across types (Claude decides the voice because the user delegated decisions; the user reviews it before the import).
- [ ] **Step 4:** Write the rest in parallel batches with subagents (lists split by category, ≤ 110 per agent). Each agent gets `STIL.md`, its export slice and the rules, and returns a JSON slice; then merge.
- [ ] **Step 5:** Run the validator → PASS. Fix the failing entries and re-run.
- [ ] **Step 6:** Spot-read 40 random entries for tone/accuracy (names must actually be on that list).
- [ ] **Step 7: Commit** `"SEO: ručno pisani naslovi i opisi za etapu 1"`

### Task 8: Import script with dry-run

**Files:** Create `scripts/seo/import-meta.php` (WP-CLI eval-file; reads the JSON path from `$args[0]`, or the JSON embedded after a `__DATA__` marker when piped through stdin; `$args`/env `YGV_SEO_DRY=1` = dry-run).

**Interfaces:**
- Consumes: `meta-etapa1.json` format from Task 7, meta keys from Global Constraints.
- Behavior: for each entry it checks that the post or term exists and its type matches, else reports `missing`. It writes `_ygv_seo_title` / `_ygv_seo_desc` only if the current value is empty (else reports `kept`), and `_ygv_seo_noindex` only when the entry has `noindex: true`. It prints the counts `written / kept / missing / type_mismatch` and the first 10 of each.

- [ ] **Step 1:** Run against the LOCAL stack in dry-run → the report shows writes for the IDs that exist locally (the January DB has fewer posts → expect some `missing`).
- [ ] **Step 2:** Run locally for real. Re-run → everything `kept` (idempotent). Set one meta manually, re-run → that one stays `kept`.
- [ ] **Step 3:** `bash tests/seo/check-head.sh` locally → PASS. One list page shows the hand-written description.
- [ ] **Step 4:** Production dry-run (read-only) → save the report to the scratchpad for the user.
- [ ] **Step 5: Commit** `"SEO: import skripta za meta podatke (samo prazna polja, dry-run)"`

### Task 9: Whole-branch review + morning handoff

- [ ] **Step 1:** Dispatch one fresh code reviewer on the branch diff vs `main` (superpowers:requesting-code-review). Fix the findings that hold up.
- [ ] **Step 2:** Write `scripts/seo/DEPLOY.md` with the exact morning commands: merge/push, `deploy-theme.sh`, LiteSpeed purge, `check-head.sh https://yugovote.com`, the import dry-run, the real import, a second purge, then the Facebook Sharing Debugger URLs.
- [ ] **Step 3:** Final unit + integration run; summary for the user.
