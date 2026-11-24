# YugoVote SEO etapa 2 — plan izrade

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dodati prave stranice kviza i ankete (izgled B), jedan H1 na svakoj vrsti stranice i JSON-LD (WebSite, Organization, BreadcrumbList), a da arhiva i slajder i dalje odmah otvaraju kviz u prozoru.

**Architecture:**
- Novi šabloni teme `single-quiz.php` i `single-voting_poll.php` crtaju zajednički okvir „landing“ (`css/landing.css`) iz pomoćnih funkcija u `inc/quizzes/quiz-landing.php` i `inc/polls/poll-landing.php`.
- Kartica kviza postaje jedan partial (`inc/quizzes/quiz-card.php`) za AJAX filter, slajder i „Još kvizova“.
- JSON-LD i putanje su u `inc/seo/seo-schema.php`, a H1 filteri za Hello arhive u `inc/seo/seo-h1.php`.

**Tech Stack:** WordPress (PHP 8.2), Hello Elementor child tema, vanilla JS, CSS. Testovi:
- PHP skripte u Docker kontejneru;
- Python HTTP provera (`tests/seo/check_head.py`), koja radi i protiv produkcije.

**Spec:** `docs/superpowers/specs/2026-11-24-yugovote-seo-etapa2-design.md`

## Global Constraints

- THEME = `wp-content/themes/hello-elementor-child/`.
- Postojeći tekstovi se ne menjaju. Novi vidljivi tekstovi su tačno ovi:
  - „Igraš odmah, bez registracije“
  - „Još kvizova“
  - „Još anketa“
  - „Započni kviz“
  - H1 za `/liste/` („Sve rang liste za glasanje“) i `/vip-osobe/` („VIP liste poznatih ličnosti“), iz `ygv_seo_static_copy()`.
- Arhiva `/kvizovi/` i slajder: običan klik i dalje odmah otvara prozor kviza.
- Stranica kviza se ne otvara sama u prozoru.
- U HTML za goste ne ide lično stanje. Prijavljeni nisu u javnom kešu.
- JSON-LD putanja je jednaka vidljivoj.
- JSON se kodira sa `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP`.
- Tekstovi u JSON-LD (`name`) prolaze kroz `ygv_seo_clean()`.
- Novi CSS dobija verziju `filemtime`.
- Svaki commit se završava redom `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- **PHP testovi:**
  - pokreću se sa `docker compose cp tests/seo/<f>.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/<f>.php`, iz korena repoa;
  - prolaze samo kad ispišu „SVE PASS“;
  - obrazac je kao `tests/seo/test-context.php`: shutdown guard, `WP_DISABLE_FATAL_ERROR_HANDLER`, `t()`;
  - privremeni postovi se brišu u shutdown funkciji.
- **HTTP provera:** `bash tests/seo/check-head.sh http://yugovote.local:8080 --sections <s>`. Bez `--sections` pokreće sve sekcije.

## Review Focus

1. Kviz bez slike, kategorije, težine i opisa: stranica se crta sa placeholder-ima i bez PHP upozorenja (test u zadatku 4).
2. Naslov sa `"`, `&` i `</script>`:
   - JSON-LD je ispravan i daje tačan naslov;
   - H1 je escapovan tačno jednom (zadatak 2 i 4).
3. Gost i prijavljen:
   - HTML za gosta nema „Igraj ponovo“;
   - stanje dugmeta je ispravno za sva tri slučaja (zadatak 4).
4. Kategorija sa manje od 3 druga kviza: „Još kvizova“ se dopunjuje, bez duplikata i bez trenutnog kviza (zadatak 4).
5. Telefon na 390 px:
   - stranica nema horizontalni skrol;
   - „Još kvizova“ se prevlači;
   - dugme je na prvom ekranu (zadatak 6).

---

### Task 1: Jedan H1 na svakoj vrsti stranice

**Files:**
- Modify: `tests/seo/check_head.py`. Nove stranice u `pages` i sekcija `h1`; podrazumevane sekcije postaju `head,robots,h1,schema,landing`.
- Create: `THEME/inc/seo/seo-h1.php` (učitava se iz `THEME/inc/seo/seo-init.php`).
- Modify: `THEME/template-parts/header/header-custom.php:214-232` (logo).
- Modify: `THEME/css/templates.css` (posle `.ygv-header__logo`).
- Modify: `THEME/inc/voting/showdown/shortcodes/showdown-shortcodes.php:66`.
- Modify: `THEME/css/showdown.css:239` (`.showdown-title`).

**Interfaces:**
- Produces:
  - `ygv_seo_h1_archive_context(): string` vraća jedno od `'voting_list'|'vip_person'|'quiz_category'|'voting_item_category'|''`;
  - u `check_head.py`, funkcije `h1s(body) -> list[str]` (tekst H1, gde `<img alt>` važi kao tekst) i `crumbs(body, cls) -> list[str]`.

- [ ] **Step 1: Proširi `check_head.py`**
  - Dodaj u `pages`:
    - `'kategorija kvizova': first_loc('wp-sitemap-taxonomies-quiz_category-1.xml')`
    - `'kategorija stavki': first_loc('wp-sitemap-taxonomies-voting_item_category-1.xml')`
    - `'vip arhiva': BASE + '/vip-osobe/'`
    - `'dvoboji': BASE + '/showdown/'`
  - Sekcija `h1`: za svaku stranicu `ok(f'{name}: tačno jedan H1', len(h1s(b)) == 1 and h1s(b)[0] != '')`, a uz to:

```python
expect = {'home': 'YugoVote', 'arhiva lista': 'Sve rang liste za glasanje',
          'vip arhiva': 'VIP liste poznatih ličnosti', 'dvoboji': '1 na 1',
          'arhiva kvizova': 'Pokaži šta znaš'}
for name, want in expect.items(): ok(f'{name}: H1 = {want}', h1s(get(pages[name])[1]) == [want])
```

- [ ] **Step 2: Proveri da pada**
  - Run: `bash tests/seo/check-head.sh http://yugovote.local:8080 --sections h1`
  - Expected: FAIL za home, kviz, anketa, showdown, kategorija kvizova, kategorija stavki, arhiva lista i vip arhiva. PASS za lista, stavka, kategorija liste, arhiva kvizova i dvoboji (one već imaju jedan H1).
- [ ] **Step 3: Logo kao H1 na početnoj**
  - U `header-custom.php` je `<a class="ygv-header__logo">`; kad je `is_front_page()`, umotaj ga u `<h1 class="ygv-header__logo-h1">…</h1>`.
  - CSS: `.ygv-header__logo-h1 { margin:0; padding:0; font-size:inherit; line-height:0; display:flex; flex-shrink:0; }`.
- [ ] **Step 4: Dvoboj**
  - Na liniji 66, tag je `is_singular('yuv_showdown') ? 'h1' : 'h2'`, a druga `.showdown-title` (linija 165) ostaje `h2`.
  - U `.showdown-title` dodaj `margin-top: .83em; margin-bottom: .83em;`.
- [ ] **Step 5: `seo-h1.php`**
  - Filter `hello_elementor_page_title` (prioritet 20): `true` kad `ygv_seo_h1_archive_context() !== ''`, inače nepromenjena vrednost.
  - U istom kontekstu:
    - `get_the_archive_title_prefix` vraća `''`;
    - `get_the_archive_title` za `voting_list` i `vip_person` vraća `ygv_seo_static_copy()['archive_'.$pt]['title']`.
- [ ] **Step 6: Proveri da prolazi**
  - Run: `bash tests/seo/check-head.sh http://yugovote.local:8080 --sections h1`
  - Expected: PASS za sve osim `kviz` i `anketa`, koji čekaju zadatke 4 i 5.
- [ ] **Step 7: Commit** — `git add` navedene fajlove; poruka „SEO etapa 2: jedan H1 na početnoj, dvoboju i Hello arhivama“.

### Task 2: JSON-LD i putanje

**Files:**
- Create: `THEME/inc/seo/seo-schema.php` (učitava se iz `seo-init.php`).
- Create: `tests/seo/test-etapa2.php`.
- Modify: `tests/seo/check_head.py`, sekcija `schema`.

**Interfaces:**
- Produces:
  - `ygv_breadcrumb_trail_for(object $obj): array` vraća `list<array{name:string,url:string}>`:
    - za `WP_Post` tipa `voting_list`, `quiz`, `voting_poll` i za `WP_Term` iz `voting_list_category`;
    - inače `[]`.
  - `ygv_breadcrumb_trail(): array`: isto za `get_queried_object()`, ali samo kad je `is_singular()` ili `is_tax()`.
  - `ygv_schema_graph(): array`: lista čvorova za trenutni upit.
  - `ygv_schema_json(array $graph): string`: JSON `{"@context":"https://schema.org","@graph":[...]}`.
  - `ygv_schema_breadcrumb_node(array $trail): array`: `{'@type':'BreadcrumbList','itemListElement':[{'@type':'ListItem','position':i+1,'name':…,'item':…}]}`.
  - `ygv_quiz_category_url(WP_Term $t): string` vraća `add_query_arg('cat', $t->slug, get_post_type_archive_link('quiz'))`.

- [ ] **Step 1: Napiši test (`test-etapa2.php`)**
  - Privremeni podaci:
    - kategorije lista `P` (parent) i `C` (child od `P`);
    - lista L1 u `C`, lista L2 samo u `P`;
    - `quiz_category` K;
    - kviz Q1 u K, sa naslovom `Kviz "A&B" </script>`;
    - kviz Q2 bez kategorije;
    - anketa A.
  - Asercije:

```php
$home = ['name' => 'Početna', 'url' => home_url('/')];
t('lista u podkategoriji', ygv_breadcrumb_trail_for(get_post($L1)), [$home, ['name'=>'P','url'=>get_term_link($P)], ['name'=>'C','url'=>get_term_link($C)], ['name'=>'L1','url'=>get_permalink($L1)]]);
t('lista u nadkategoriji', array_column(ygv_breadcrumb_trail_for(get_post($L2)), 'name'), ['Početna','P','L2']);
t('podkategorija', array_column(ygv_breadcrumb_trail_for(get_term($C)), 'name'), ['Početna','P','C']);
t('nadkategorija', array_column(ygv_breadcrumb_trail_for(get_term($P)), 'name'), ['Početna','P']);
t('kviz', ygv_breadcrumb_trail_for(get_post($Q1)), [['name'=>'Kvizovi','url'=>get_post_type_archive_link('quiz')], ['name'=>'K','url'=>ygv_quiz_category_url(get_term($K))], ['name'=>'Kviz "A&B" </script>','url'=>get_permalink($Q1)]]);
t('kviz bez kategorije', array_column(ygv_breadcrumb_trail_for(get_post($Q2)), 'name'), ['Kvizovi', 'Q2']);
t('anketa', array_column(ygv_breadcrumb_trail_for(get_post($A)), 'url')[0], get_permalink(get_page_by_path('anketa')));
$j = ygv_schema_json([ygv_schema_breadcrumb_node(ygv_breadcrumb_trail_for(get_post($Q1)))]);
t('JSON bez </script>', stripos($j, '</script') === false, true);
t('JSON se dekodira na tačan naslov', json_decode($j, true)['@graph'][0]['itemListElement'][2]['name'], 'Kviz "A&B" </script>');
t('pozicije od 1', array_column(json_decode($j, true)['@graph'][0]['itemListElement'], 'position'), [1,2,3]);
```

  - Logika izbora kategorije liste (prva sa `parent !== 0`, inače prva) je tačno kao u `voting-list-fullpage.php:56-83`.
- [ ] **Step 2: Proveri da pada**
  - Run: test u kontejneru.
  - Expected: FAIL / „test se nije završio“ (nedefinisana funkcija).
- [ ] **Step 3: Implementiraj `seo-schema.php`**
  - Graf:
    - **početna:** `WebSite {@id home#website, url, name 'YugoVote', inLanguage 'sr-Latn', publisher {@id home#organization}}` i `Organization {@id home#organization, name 'YugoVote', url, logo ImageObject {url: THEME_URI/assets/images/logo-schema.png, width 512, height 512}, sameAs ['https://www.facebook.com/profile.php?id=61591842951679','https://www.instagram.com/yugo_vote/']}`;
    - **inače:** `[ygv_schema_breadcrumb_node($trail)]` kad trail nije prazan.
  - Ispis: `wp_head` prioritet 2, samo kad graf nije prazan i nije admin ni feed. Jedan `<script type="application/ld+json">`.
- [ ] **Step 4: Sekcija `schema` u `check_head.py`**
  - `lds(body)` vraća listu parsiranih JSON blokova; neuspešan `json.loads` je FAIL.
  - Za `home`: tačno jedan blok, a `@type` skup je `{'WebSite','Organization'}`.
  - Za `lista`: `ld_names == crumbs(b, 'vlp-breadcrumb')`. Za `kategorija liste`: `ld_names == crumbs(b, 'ygv-cat-hero__breadcrumb')`.
  - Za `kviz` i `anketa`: tačno jedan `BreadcrumbList`, a poslednji `item` je jednak `og:url`.
  - Sve `item` adrese počinju sa `BASE`.
  - Za `stavka`, `showdown`, `arhiva kvizova` i `arhiva lista`: nema ld+json.
- [ ] **Step 5: Proveri da prolazi**
  - Run: PHP test i `check-head.sh … --sections schema`.
  - Expected: SVE PASS.
- [ ] **Step 6: Commit** — poruka „SEO etapa 2: JSON-LD (WebSite, Organization, BreadcrumbList)“.

### Task 3: Kartica kviza kao partial + naslov kao link

**Files:**
- Create: `tests/seo/snapshot-cards.php` (argument `save` ili `compare`).
- Create: `THEME/inc/quizzes/quiz-card.php` (učitava se iz `THEME/inc/quizzes/quizzes-init.php`).
- Modify:
  - `THEME/inc/quizzes/api/quiz-archive-ajax.php:53-181`;
  - `THEME/inc/quizzes/templates/quiz-slider.php:34-197`;
  - `THEME/archive-quiz.php:311`;
  - `THEME/js/quizzes/main.js` (posle globalnog handler-a, oko linije 676);
  - `THEME/css/templates.css` (kod `.ygv-quiz-card__title`).

**Interfaces:**
- Produces:
  - `ygv_quiz_difficulty_color(string $slug): string`: mapa iz AJAX kopije, uključujući `tesko`/`teško`; podrazumevano `'#6366f1'`.
  - `ygv_quiz_progress(int $user_id, int $quiz_id): array{best_percent:int, attempts:int}`.
  - `ygv_render_quiz_card(int $quiz_id, int $user_id): void`: ispisuje `<article class="ygv-quiz-card …">` kao današnja AJAX kopija, a u `h3.ygv-quiz-card__title` stavlja `<a class="ygv-quiz-card__title-link" href="{permalink}">{esc_html naslov}</a>`.

- [ ] **Step 1: Snimak pre izmene**
  - `snapshot-cards.php save`:
    - kao gost, pa kao prvi administrator, upisuje u `/tmp/ygv-cards-{guest|admin}.json` HTML AJAX filtera (strane 1 i 2, poziv `ygv_archive_filter_ajax()` uz `wp_die_ajax_handler` trik iz `tests/guest/test-showdown-ajax.php`) i HTML `do_shortcode('[quiz_slider]')`;
    - kategorije K i težinu ne filtrira.
  - `compare` pravi isto, pa normalizuje oba ispisa:
    - uklanja `<a class="ygv-quiz-card__title-link" href="[^"]*">` i odgovarajući `</a>` u naslovu;
    - zatim `preg_replace('/\s+/', '', …)`;
    - zatim izbacuje id slajdera (`ygv-quiz-slider-[0-9a-f]+`).
  - Ispis: „SVE PASS“ ili prvih 300 znakova razlike.
  - Run: `docker compose cp tests/seo/snapshot-cards.php wordpress:/tmp/ && docker compose exec -T wordpress php /tmp/snapshot-cards.php save`
  - Expected: „sačuvano: 4 snimka“.
- [ ] **Step 2: Test za link (`test-etapa2.php`)**

```php
ob_start(); ygv_render_quiz_card($Q1, 0); $h = ob_get_clean();
t('kartica: naslov je link ka kvizu', (bool) preg_match('#<h3 class="ygv-quiz-card__title"><a class="ygv-quiz-card__title-link" href="' . preg_quote(get_permalink($Q1), '#') . '">#', $h), true);
t('kartica: dugme otvara prozor', strpos($h, 'data-quiz-id="' . $Q1 . '"') !== false, true);
t('kartica: naslov escapovan jednom', strpos($h, 'Kviz &quot;A&amp;B&quot; &lt;/script&gt;') !== false, true);
```

- [ ] **Step 3: Proveri da pada**
  - Expected: FAIL (`ygv_render_quiz_card` ne postoji).
- [ ] **Step 4: Implementiraj partial**
  - Implementiraj `quiz-card.php` i zameni tela petlji u AJAX filteru i slajderu pozivom `ygv_render_quiz_card($quiz_id, $user_id)`.
  - Slajder zadržava omotač `<div class="swiper-slide">`.
  - Promenljive koje partial više ne koristi uklanja se iz oba fajla.
  - U `archive-quiz.php:311` samo naslov dobija isti `<a>`.
- [ ] **Step 5: Proveri**
  - Run: `snapshot-cards.php compare` i `test-etapa2.php`.
  - Expected: SVE PASS u oba.
- [ ] **Step 6: Klik na naslov (JS i CSS)**
  - U `main.js`, `document.addEventListener('click', e => …)`:
    - ako je `e.target.closest('.ygv-quiz-card__title-link')`, `e.button === 0` i nema `ctrlKey`, `metaKey`, `shiftKey` ni `altKey`, onda `preventDefault()` i `link.closest('.ygv-quiz-card')?.querySelector('.ygv-quiz-start-btn')?.click()`;
    - inače ništa.
  - CSS: `.ygv-quiz-card__title-link { color: inherit; text-decoration: none; }` i `.ygv-quiz-card__title-link:hover { color: inherit; text-decoration: none; }`.
- [ ] **Step 7: Sekcija `landing` (deo 1) u `check_head.py`**
  - `/kvizovi/` ima `class="ygv-quiz-card__title-link" href="{BASE}/quiz/`.
  - Početna ima isti link (slajder).
  - Run: `--sections landing`.
  - Expected: PASS za te dve stavke.
- [ ] **Step 8: Commit** — poruka „Kvizovi: jedna kartica za filter, slajder i preporuke; naslov kartice je link“.

### Task 4: Stranica kviza (izgled B)

**Files:**
- Create: `THEME/inc/quizzes/quiz-landing.php` (učitava se iz `quizzes-init.php`). Sadrži i `wp_enqueue_scripts` za `css/landing.css` kad je `is_singular(['quiz','voting_poll'])`.
- Create: `THEME/single-quiz.php`.
- Create: `THEME/css/landing.css`.
- Modify: `tests/seo/test-etapa2.php`, `tests/seo/check_head.py` (sekcija `landing`).

**Interfaces:**
- Consumes:
  - `ygv_breadcrumb_trail_for()` i `ygv_quiz_category_url()` (zadatak 2);
  - `ygv_quiz_difficulty_color()`, `ygv_quiz_progress()` i `ygv_render_quiz_card()` (zadatak 3).
- Produces:
  - `ygv_quiz_cta_state(int $user_id, bool $guest_allowed, int $attempts): string`, sa vrednostima:
    - `'locked'`: gost i `!$guest_allowed`;
    - `'replay'`: prijavljen i `$attempts > 0`;
    - inače `'start'`.
  - `ygv_quiz_landing_data(int $quiz_id, int $user_id): array` sa ključevima:
    - `title`, `description` (`_quiz_description`, pa `post_excerpt`, pa `''`);
    - `category` (`?WP_Term`), `category_color`;
    - `questions` (`_num_questions` ili 10), `seconds` (`_time_per_question` ili 10), `xp` (`_quiz_xp_value` ili 20);
    - `difficulty` (naslov `quiz_levels` ili `''`), `difficulty_color`;
    - `guest_allowed`, `cta_state`.
  - `ygv_quiz_related_ids(int $quiz_id, int $limit = 3): array<int>`.
  - `ygv_landing_crumbs(array $trail): void`: ispisuje `nav.ygv-landing-crumbs`. Zadatak 5 ga koristi.
  - `ygv_landing_open(string $pill): void` i `ygv_landing_close(): void`: zaglavlje i omotač kartice, za oba tipa.

- [ ] **Step 1: Testovi**

```php
t('cta gost zaključan', ygv_quiz_cta_state(0, false, 0), 'locked');
t('cta gost otvoren', ygv_quiz_cta_state(0, true, 0), 'start');
t('cta prijavljen odigrao', ygv_quiz_cta_state(5, false, 2), 'replay');
t('cta prijavljen nije igrao', ygv_quiz_cta_state(5, false, 0), 'start');
update_post_meta($Q1, '_quiz_description', 'Opis iz polja.');
t('opis iz _quiz_description', ygv_quiz_landing_data($Q1, 0)['description'], 'Opis iz polja.');
$d = ygv_quiz_landing_data($Q2, 0);
t('podrazumevano', [$d['questions'], $d['seconds'], $d['xp'], $d['difficulty'], $d['category']], [10, 10, 20, '', null]);
// K ima Q1 i još Q3 (najnoviji); Q4 i Q5 su van K
t('preporuke: prvo iz kategorije, pa dopuna, bez trenutnog i duplikata', ygv_quiz_related_ids($Q1), [$Q3, $Q5, $Q4]);
$html = ygv_test_render_singular($Q2, 'single-quiz.php'); // pomoćna funkcija u testu, vidi napomenu
t('stranica bez slike/kategorije/težine: bez upozorenja', $GLOBALS['ygv_warnings'], []);
t('stranica: tačno jedan h1', substr_count($html, '<h1'), 1);
t('gost: nema "Igraj ponovo"', strpos($html, 'Igraj ponovo'), false);
t('gost: dugme za prozor', strpos($html, 'ygv-quiz-start-btn') !== false && strpos($html, 'data-quiz-id="' . $Q2 . '"') !== false, true);
```

  - Q3, Q4 i Q5 se prave sa rastućim `post_date`; Q3 i Q1 su u K.
  - Ostali kvizovi iz baze ne smeju da uđu u dopunu. Zato je dopuna u testu proverena na izdvojenoj kopiji upita: test privremeno spušta datum svih ostalih kvizova ispod Q4, a posle ga vraća. Ako je to teško, test proverava samo `count == 3`, `!in_array($Q1)`, `array_unique` i to da je `$Q3` prvi.
  - **Napomena za `ygv_test_render_singular(int $id, string $tpl): string`:**
    - postavlja `$wp_query`/`$wp_the_query` na `new WP_Query(['p'=>$id,'post_type'=>get_post_type($id)])`, plus `$post` i `setup_postdata`;
    - `set_error_handler` za `E_WARNING|E_NOTICE|E_DEPRECATED` upisuje greške iz fajlova teme u `$GLOBALS['ygv_warnings']`;
    - izlaz je `ob_start(); include THEME/$tpl; ob_get_clean()`.
- [ ] **Step 2: Proveri da pada** — Expected: FAIL (nedefinisane funkcije, šablona nema).
- [ ] **Step 3: Implementiraj `quiz-landing.php` i `single-quiz.php`**
  - Struktura i tekstovi su tačno po specifikaciji, odeljak „Stranica kviza“:
    - putanja preko `ygv_landing_crumbs(ygv_breadcrumb_trail_for($post))`, a poslednja stavka je `span.ygv-landing-crumbs__current` bez linka;
    - pill „Kviz“;
    - kartica: slika `large` ili placeholder sa gradijentom i ikonicom `brain` (48); bedž kategorije sa `strtoupper` nazivom, kao na kartici;
    - meta ikonice: `file-text`, `star` (boja težine), `clock`, `zap`;
    - dugme `button.ygv-landing-cta.ygv-quiz-start-btn[data-quiz-id]`:
      - `locked`: ikonica `lock`, „Prijavi se da igraš“ i `data-locked="true"`;
      - `replay`: ikonica `refresh`, „Igraj ponovo“;
      - `start`: ikonica `play`, „Započni kviz“;
    - napomena „Igraš odmah, bez registracije“ samo kad je gost i `guest_allowed`;
    - „Još kvizova“: `h2` sa ikonicom `brain` i `div.ygv-landing-more__grid`, a u njemu `ygv_render_quiz_card()` za svaki id.
  - `get_header()`/`get_footer()` kao u `archive-quiz.php`.
- [ ] **Step 4: Implementiraj `css/landing.css`**
  - Vrednosti iz mockup-a (`.superpowers/brainstorm/2455-1795001903/content/kviz-stranica-v2.html`, opcija B):

    | Mockup | Klasa |
    |---|---|
    | `.hero-b` | `.ygv-landing-hero` |
    | `.crumbs--light` | `.ygv-landing-crumbs` |
    | `.pill` | `.ygv-landing-pill` |
    | `.qwrap` | `.ygv-landing-wrap` |
    | `.qcard` | `.ygv-landing-card` |
    | `.qmedia`, `.qimg` | `__media`, `__img` |
    | `.badge` | `__badge` |
    | `.qbody` | `__body` |
    | `h1` | `__title` |
    | `.meta` | `__meta` |
    | `.desc` | `__desc` |
    | `.cta--navy` | `.ygv-landing-cta` |
    | `.cta-note` | `.ygv-landing-note` |
    | `.more` | `.ygv-landing-more` |
    | `.grid` | `__grid` |

  - Font naslova je isti kao `.ygv-page-hero__title`.
  - `@media (max-width: 767px)`: vrednosti iz mockup-a za telefon. `__grid` je `display:flex; overflow-x:auto; scroll-snap-type:x mandatory; gap:16px`, a deca `flex:0 0 76%; scroll-snap-align:start`.
- [ ] **Step 5: Sekcija `landing` (deo 2)**
  - Stranica `kviz`:
    - `data-quiz-id` je jednak ID-ju iz `postid-N` u klasi body-ja;
    - `crumbs(b,'ygv-landing-crumbs')` je jednak imenima iz JSON-LD-a;
    - ima `ygv-landing-more__grid` sa bar jednom `ygv-quiz-card`.
  - Run: PHP test i `--sections h1,schema,landing`.
  - Expected: SVE PASS osim stavki za anketu.
- [ ] **Step 6: Commit** — poruka „Stranica kviza (izgled B): naslov, podaci, dugme za prozor, još kvizova“.

### Task 5: Stranica ankete + keš ankete

**Files:**
- Create: `THEME/inc/polls/poll-landing.php` (učitava se iz `THEME/inc/polls/polls-init.php`).
- Create: `THEME/single-voting_poll.php`.
- Modify:
  - `THEME/inc/polls/polls-init.php` (`ygv_render_floating_poll`: ne ispisuje se kad je `is_singular('voting_poll')`);
  - `THEME/inc/polls/polls-shortcode.php` (`cs_poll_shortcode`);
  - `THEME/css/landing.css`;
  - testovi.

**Interfaces:**
- Consumes: `ygv_landing_open()`, `ygv_landing_close()`, `ygv_landing_crumbs()` i `ygv_breadcrumb_trail_for()`.
- Produces: `ygv_poll_related_ids(int $poll_id, int $limit = 3): array<int>`: najnovije objavljene, bez trenutne, bez `_cs_poll_locked === '1'`.

- [ ] **Step 1: Testovi**

```php
// A1 (najnovija), A2 zaključana, A3, A4 — sve privremene, sa rastućim datumom A4 < A3 < A2 < A1
t('još anketa: bez trenutne i zaključanih', array_slice(ygv_poll_related_ids($A1, 3), 0, 2), [$A3, $A4]);
$priv = 0; add_action('litespeed_control_set_private', function () use (&$priv) { $priv++; });
update_post_meta($A2, '_cs_poll_locked', '1'); unset($_COOKIE['cs_poll_' . $A2]); wp_set_current_user(0);
do_shortcode('[voting_poll id="' . $A2 . '"]');
t('zaključana anketa, gost nije glasao: bez privatnog keša', $priv, 0);
$_COOKIE['cs_poll_' . $A3] = '1'; do_shortcode('[voting_poll id="' . $A3 . '"]');
t('gost je glasao: privatni keš', $priv, 1);
$html = ygv_test_render_singular($A1, 'single-voting_poll.php');
t('anketa: jedan h1, forma, bez upozorenja', [substr_count($html, '<h1'), strpos($html, 'class="ygv-poll ') !== false, $GLOBALS['ygv_warnings']], [1, true, []]);
```

- [ ] **Step 2: Proveri da pada.**
- [ ] **Step 3: Implementacija**
  - **`single-voting_poll.php`:**
    - pill „Anketa“;
    - putanja `Ankete › naslov`;
    - kartica:
      - levo slika ili placeholder SVG iz `poll-card.php`, bez bedža;
      - desno `h1`, `div.ygv-landing-card__desc` (`wpautop(wp_kses_post(post_content))`, samo ako nije prazno) i `div.ygv-landing-card__poll` sa `[voting_poll id]`;
    - „Još anketa“: `h2` (ikonica `bar-chart`) i `div.ygv-landing-more__grid ygv-landing-more__grid--polls` sa `poll-card.php` (promenljive `$poll_id`, `$img_url` = `medium_large`, `$total_votes`).
  - **CSS:** `.ygv-landing-card__poll .ygv-poll__question { display:none; }`. Mreža anketa: 3 kolone na desktopu, na telefonu red za prevlačenje kao kod kvizova.
  - **`cs_poll_shortcode`:** `$guest_voted` se računa pre nego što zaključavanje forsira `$has_voted`, a privatni keš se traži samo kad `$guest_voted && !is_user_logged_in()`.
- [ ] **Step 4: Sekcija `landing` (deo 3)**
  - Stranica `anketa`:
    - ima `class="ygv-poll ` sa `data-poll-id` jednakim ID-ju posta;
    - `crumbs(b,'ygv-landing-crumbs')` je jednak JSON-LD imenima.
  - Run: PHP test i `check-head.sh http://yugovote.local:8080` (sve sekcije).
  - Expected: SVE PASS.
- [ ] **Step 5: Commit** — poruka „Stranica ankete (izgled B) + javni keš za zaključane ankete“.

### Task 6: Provera u pregledaču i regresija

**Files:** nema novih. Ispravke koje provera nađe idu u fajlove iz zadataka 1–5.

- [ ] **Step 1: Snimci**
  - Za jedan kviz sa slikom (npr. „Brojke i činjenice“), jedan kviz bez opisa ako postoji, i za anketu, na 1440 px i na 390 px. Uporedi sa mockup-om B.
  - Na 390 px: `document.documentElement.scrollWidth <= innerWidth`, a dugme „Započni kviz“ je unutar prvog ekrana (`getBoundingClientRect().bottom <= innerHeight`).
  - Expected: oba `true`.
- [ ] **Step 2: Ponašanje**
  - Klik na „Započni kviz“ otvara prozor (`#quiz-modal` ili ono što `Quiz` pravi).
  - Na `/kvizovi/` običan klik na naslov kartice otvara prozor, a `href` ide na `/quiz/`.
  - Na početnoj isto za slajder.
  - Zaključan kviz kao gost: prozor za prijavu (stranica kviza) ili prozor kviza sa porukom (arhiva).
  - Glas u anketi sa stranice ankete kao gost se upisuje. To proveri pa obriši probni glas u lokalnoj bazi.
  - Početna i dvoboj izgledaju isto kao pre (snimak pre i posle).
- [ ] **Step 3: Regresija**
  - Sve iz `tests/guest/` i `tests/seo/`:
    - `run-tests.php`, `check-head.sh` sve sekcije;
    - `test-admin-save`, `test-context`, `test-import`;
    - `test-etapa2`, `snapshot-cards compare`.
  - Expected: SVE PASS svuda.
- [ ] **Step 4: Commit** ispravki, ako ih ima.

### Task 7: Puštanje (posle završne recenzije)

- [ ] `git checkout main && git merge --ff-only seo-etapa2 && git push origin main`
- [ ] `bash scripts/deploy-theme.sh`, pa `ssh yugovote 'cd ~/domains/yugovote.com/public_html && wp litespeed-purge all'`
- [ ] Provera na produkciji:
  - `bash tests/seo/check-head.sh https://yugovote.com` i `bash tests/guest/check-guest-ajax.sh https://yugovote.com`;
  - snimci kviza i ankete sa produkcije;
  - Rich Results test za jedan kviz i jednu listu (`https://search.google.com/test/rich-results?url=…`).
  - Expected: SVE PASS, BreadcrumbList bez grešaka.
