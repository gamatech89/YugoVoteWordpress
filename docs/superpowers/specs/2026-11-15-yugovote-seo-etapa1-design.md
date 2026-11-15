# YugoVote SEO — Etapa 1: tehnička osnova + ručno pisani meta opisi

Datum: 2026-11-15
Status: odobreno (korisnik: "nastavi da radiš i donosiš odluke sam", 2026-11-15)
Recenzija izvođača: bez otvorenih pitanja; ispravke činjenica unete.

## Namera

YugoVote (yugovote.com, WordPress + Hello Elementor child tema) treba da se pojavljuje na Google-u
"kao portal sa vestima": bilo koja lista, kviz, anketa ili tekst sa sajta treba da može da izađe u
rezultatima i da lepo izgleda kad se podeli (Facebook, Viber, WhatsApp, Instagram link u bio).

Korisnik je izričito rekao: **tekstove na sajtu sada ne menjamo**, radimo samo meta sloj (naslovi za
Google, opisi, share kartice, indeksiranje). Korisnik želi da **Claude napiše meta naslove i opise**
za sadržaj, ne samo da napravi mehanizam.

## Ciljevi

1. Svaka javna stranica ima smislen `<title>`, meta description, Open Graph i Twitter kartice.
2. Google indeksira samo vredne stranice; prazne/tehničke/test stranice su `noindex` i van sitemap-a.
3. Sajt je označen kao srpski (latinica), ne engleski.
4. Ručno napisani (od strane Claude-a) naslovi i opisi za ~845 najvažnijih stranica, upisani u bazu.
5. Urednik može kasnije ručno da prepravi naslov/opis ili sakrije stranicu, kroz "SEO" kutiju u adminu.
6. Novi sadržaj dobija dobar automatski opis bez ičijeg rada.

## Ne-ciljevi (ova etapa)

- Menjanje vidljivih tekstova (uvodi lista, opisi stavki, biografije).
- Strukturirani podaci (JSON-LD) i ispravka nedostajućih H1 naslova → **Etapa 2**.
- Google Search Console, brzina sajta (početna ~3,8 s) → **Etapa 3**.
- Brisanje test sadržaja ("Hello world!", "Testiranje Liste") — samo se sakrivaju; korisnik briše sam.
- Ručno pisanje opisa za 8.188 stavki (`voting_items`) i 59 kategorija stavki.

## Anti-ciljevi (neuspeh čak i ako "radi")

- Opisi koji zvuče kao generički šablon ili AI-kliše ("Otkrijte fascinantan svet…"), ili koji ne liče na
  duhovit glas sajta.
- Bilo šta što usporava sajt (novi teški plugin, upiti po stranici koji nisu keširani).
- Prepisivanje ručnih izmena urednika pri ponovnom importu.
- Slučajno `noindex` na stranici koja treba da bude u Google-u (liste, kvizovi, stavke).
- Promena jezika celog WordPress-a (admin, datumi, tekstovi pluginova) kao sporedni efekat.

## Trenutno stanje (provereno 2026-11-15 na produkciji)

- Nema SEO plugina. Aktivni: admin-menu-editor, ACF, Elementor (+Pro), FileBird Pro, Hostinger, LiteSpeed Cache, WPIDE.
- `<html lang="en-US">`, WP locale `en_US`, tagline prazan. Naslov početne: samo "YugoVote".
- Nijedna stranica nema meta description, OG ni Twitter tagove, ni JSON-LD. Separator naslova je `–`.
- Core `wp_robots` već dodaje `max-image-preview:large`; core daje canonical na singular stranicama.
- Core sitemap (`/wp-sitemap.xml`, i `robots.txt` ga navodi) ima ~17.000 URL-ova:
  - `question` 8.981 — stranice su **prazne** (samo widget "Anketa dana", bez H1, bez pitanja)
  - `voting_items` 8.188 — imaju pravi tekst (duhovit opis + biografija + "liste na kojima se nalazi") → **ostaju**
  - `voting_list` 616 (612 sa uvodom, median ~494 znaka, svih 616 sa slikom) → glavni sadržaj
  - `quiz` 152 (post_content prazan; opis u meta `_quiz_description`; 151 sa slikom)
  - `voting_poll` 28 (kratak content ~38 znakova; odgovori u `_cs_poll_answers`; svi sa slikom)
  - `yuv_showdown` 8 (bez contenta i bez slike; stavke u `_yuv_showdown_items`)
  - `vip_person` 2, `quiz_levels` 4, `post` 2 (oba test), `page` 16, korisnici 4
  - taksonomije: `voting_list_category` (26, sve imaju opis), `quiz_category` 6, `voting_item_category` 59, `question_category` 6, `category` 2
- Front page: stranica `pocetna` (ID 31369, Elementor). Arhive: `/liste/`, `/kvizovi/`, `/showdown/`, `/vip-osobe/`.
  Kategorije lista: `/liste-za-glasanje/{term}/`.
- Stavke liste: meta `_voting_items` + pivot tabela `{prefix}voting_list_item_relations`; poeni se računaju
  kao `SUM(vote_value)` iz `{prefix}voting_list_votes` po (`voting_list_id`, `voting_item_id`).
- Parent tema Hello Elementor 3.4.7 sama ispisuje `<meta name="description">` iz excerpt-a na singular
  stranicama; gasi se filterom `hello_elementor_description_meta_tag`.
- Liste sa `_is_tournament_match` se preusmeravaju na početnu; trenutno ih nema objavljenih (0).
- Objekt keš je aktivan (LiteSpeed), pa transient-i idu u njega.
- Slug-ovi stranica na produkciji: kontakt, uslovi-koriscenja, test, test-vip, uredi-profil, anketa,
  moj-nalog, zaboravljena-lozinka, pocetna, kompletiranje-naloga, registracija, prijava, podrska,
  o-nama, pocetna-old, politika-privatnosti.
- Tema koristi nativne `add_meta_box` kutije (ne ACF) za sva podešavanja CPT-ova.
- `header.php` i custom auth šabloni (`page-login.php`, `page-register.php`, …) svi zovu `wp_head()`.
- Pristup: `ssh yugovote` radi (ključ), WP-CLI 2.12 dostupan. Deploy: git push → `~/yugovote-theme/deploy-theme.sh` → purge LiteSpeed.
- Lokalni DB dump je iz januara 2026 (zastareo).

## Pristupi

| | Pristup | Za | Protiv |
|---|---|---|---|
| A | SEO plugin (Rank Math / Yoast) | admin UI, ručne izmene po stranici, gotov sitemap | opisi iz šablona su generički; podešavanja u bazi, ne u git-u; još jedan težak plugin na već sporom sajtu |
| **B** | **Sopstveni modul u child temi** | opisi iz stvarnih podataka (uvod + top imena, broj glasova); sve u git-u i postojećem deploy-u; lagano | nema gotovog UI-ja → pravimo sopstvenu "SEO" kutiju |
| C | Plugin + naši filteri | UI plugina + pametni opisi | dva sistema koja se bore oko istih tagova |

**Izabrano: B** (korisnik odobrio). Razlog: sadržaj je u custom CPT-ovima i pivot tabeli, tekstovi se ne
pišu ručno, pa kvalitet zavisi od toga koliko dobro opis izvlačimo iz podataka; tema je već ručno pisana i
deploy-uje se iz git-a.

## Dizajn

### 1. Modul `inc/seo/` u child temi

Učitava se iz `inc/init.php`. Ako je aktivan Yoast ili Rank Math, modul ne ispisuje ništa (zaštita od
duplih tagova) — *Claude-ova odluka*. Gasi parent-temin description tag (`hello_elementor_description_meta_tag` → false).

**Podaci (post meta i term meta, isti ključevi):**
- `_ygv_seo_title` — ručni naslov (bez " | YugoVote", sufiks dodaje kod)
- `_ygv_seo_desc` — ručni opis
- `_ygv_seo_noindex` — `'1'` = sakrij iz Google-a

**Prioritet:** ručno polje → automatski fallback. Isto važi za naslov i opis.

**Naslov (`<title>`):** format `{naslov} | YugoVote` (separator `|` umesto `–`). Početna: ručno napisan
naslov sa ključnim rečima (npr. "YugoVote — glasaj za najbolje sa ex-YU prostora"). Ide kroz
`pre_get_document_title`/`document_title_parts` + `document_title_separator`, da radi i na Elementor
stranicama i na custom auth šablonima.

**Automatski opis (fallback), po tipu:**
- `voting_list`: početak uvoda (`post_content`, bez HTML-a, prekinut na granici reči) + top 3 stavke po
  poenima (`SUM(vote_value)` iz `voting_list_votes`, samo stavke iz `_voting_items`) → ukupno ≤155 znakova.
- `voting_items`: početak `post_content` (duhoviti opis), ≤155.
- `quiz`: `_quiz_description`; ako nema: "Kviz: {naslov}. {N} pitanja, {težina}. Proveri koliko znaš."
- `voting_poll`: content + 2–3 ponuđena odgovora.
- `yuv_showdown`: naslov + imena stavki u duelu.
- taksonomije: opis terma (`voting_list_category` ga ima za svih 26).
- arhive (`/liste/`, `/kvizovi/`, `/showdown/`, `/vip-osobe/`) i početna: fiksni tekstovi u kodu, koje piše Claude.
- Rezultat skupih fallback-a (pivot upit) se kešira u transient/object cache po postu, i invalidira na `save_post`.

**Ispis u `wp_head`:**
- `<meta name="description">`
- `og:type` (`website` za početnu i arhive, `article` za singular), `og:title`, `og:description`, `og:url`,
  `og:site_name`, `og:locale` = `sr_RS`, `og:image` (+ `og:image:width/height`)
- `twitter:card` = `summary_large_image`, `twitter:title/description/image`
- `rel="canonical"` za taksonomije i arhive (singular već pokriva core; ne duplirati)
- `og:image`: istaknuta slika (veličina `large` ili veća); fallback je nova podrazumevana share slika
  `assets/images/og-default.jpg` 1200×630, sa logom/maskotom sajta (*Claude pravi, korisnik odobrava*).

**Jezik:** filter `language_attributes` → `lang="sr-Latn"`. WP locale ostaje `en_US`, da se ne promene
admin, datumi i tekstovi pluginova.

**Indeksiranje (`wp_robots` → `noindex, follow`):**
- post tipovi: `question`, `quiz_levels`, `post`
- taksonomije: `question_category`, `category`
- autorske arhive, pretraga, 404
- stranice (po slug-u): `test`, `test-vip`, `pocetna-old`, `moj-nalog`, `uredi-profil`, `prijava`,
  `registracija`, `zaboravljena-lozinka`, `kompletiranje-naloga`
- svaki post ili term sa `_ygv_seo_noindex = '1'`
- liste sa `_is_tournament_match` (preusmeravaju se, ne treba da budu u sitemap-u)

Ostaju indeksirani: `voting_list`, `voting_items`, `quiz`, `voting_poll`, `yuv_showdown`, `vip_person`,
javne stranice (početna, kontakt, o-nama, anketa, uslovi-koriscenja, politika-privatnosti, podrska),
`voting_list_category`, `quiz_category`, `voting_item_category`.

**Sitemap (core filteri):**
- iz sitemap-a se izbacuju post tipovi `question`, `quiz_levels`, `post` i taksonomije
  `question_category`, `category`
- isključuje se provider `users`
- iz sitemap-a se izbacuju i pojedinačni postovi i stranice koji su `noindex` (meta ili lista slug-ova)
- `robots.txt` ostaje kakav jeste (već pokazuje na `/wp-sitemap.xml`)

Stranice pitanja **ostaju dostupne** (nije 404/410), samo su `noindex`, tako da kviz radi kao do sada.

**"SEO" meta kutija u adminu** (nativni `add_meta_box`, u stilu postojećih kutija), na tipovima
`voting_list`, `voting_items`, `quiz`, `voting_poll`, `yuv_showdown`, `vip_person`, `page`. Iste
kontrole su i u formi za edit terma za `voting_list_category`, `quiz_category` i `voting_item_category`:
- Naslov za Google (opciono) sa brojačem znakova (cilj ≤ 50, jer sufiks " | YugoVote" dodaje 11)
- Opis za Google (opciono) sa brojačem (cilj 120–155)
- ☐ Sakrij iz Google-a
- Pregled: šta će se prikazati ako su polja prazna (automatski naslov i opis)
- nonce i provera `edit_post` / `edit_term`; prazno polje briše meta

### 2. Ručno pisani naslovi i opisi (Claude)

**Obim (~845):** 616 lista, 152 kviza, 28 anketa, 8 showdown-a, 2 VIP osobe, 26 kategorija lista,
6 kategorija kvizova, početna, javne stranice, 4 arhive.
**Bez ručnog pisanja:** 8.188 stavki i 59 kategorija stavki, za njih radi automatski fallback.

**Pravila pisanja:**
- latinica, ekavica, srpski kao na sajtu; bez emodžija i bez VELIKIH SLOVA
- opis 120–155 znakova; naslov ≤ 50 znakova (pre sufiksa)
- glas sajta: duhovit, lokalan, malo bezobrazan, ali tačan; bez AI klišea ("otkrijte", "fascinantan",
  "zaronite", "ultimativni vodič")
- liste: pomenuti 2–3 poznata imena sa liste i poziv na akciju (Glasaj / Reci ko je bolji / Složi svoju listu)
- kvizovi: tema + izazov ("Koliko znaš…") + broj pitanja ako je poznat
- naslov sme da doda reč koju ljudi kucaju ("rang lista", "kviz", "anketa") ako je nema u nazivu, ali ne
  sme da obeća ono čega nema na stranici
- bez duplikata: dva opisa ne smeju biti ista ni skoro ista

**Proces:**
1. **Export (read-only)** preko WP-CLI na produkciji → JSON: id, tip, naslov, url, uvod/opis, top stavke,
   broj pitanja/glasova.
2. **Uzorak od ~20** (mešano: liste raznih kategorija, kvizovi, ankete, kategorije, početna) →
   **korisnik odobrava ton** pre nastavka.
3. Pisanje celog skupa u turama, po tipu i kategoriji.
4. Automatska provera: dužine, duplikati, ćirilica/ijekavica (`ije`) i zabranjene fraze.
5. Fajl sa podacima se commit-uje u repo (`scripts/seo/meta-etapa1.json`), da postoji zapis i da se može
   ponovo uvesti.
6. **Import** preko `wp eval-file` skripte na serveru: upisuje samo prazna polja (ne prepisuje ručne
   izmene); prvo `--dry-run` sa izveštajem, pa pravi upis **tek uz izričito OK korisnika**.
7. Tekstovi za početnu i arhive idu direktno u kod modula.

Kod (modul) se deploy-uje pre importa. Do importa svuda radi automatski fallback, pa sajt nikad nije bez opisa.

### 3. Deploy i provera

- Deploy preko postojeće skripte, pa purge LiteSpeed keša (`wp litespeed-purge all` ili iz admina).
- Provera na produkciji, po jedna stranica od svakog tipa (lista, stavka, kviz, anketa, showdown,
  kategorija, arhiva, početna, stranica pitanja, prijava):
  `lang`, `<title>`, description, og/twitter tagovi, robots meta, canonical.
- `/wp-sitemap.xml` više ne sadrži `question`, `quiz_levels`, `post`, `users`, `question_category`, `category`.
- Share preview: provera kroz Facebook Sharing Debugger (ili ekvivalent) za jednu listu i jedan kviz.
- Posle importa: nasumičnih 10 stranica pokazuje ručni opis.

## Etape posle ove (posebni dizajni)

- **Etapa 2:** JSON-LD (`ItemList` za liste, `Quiz` za kvizove, `BreadcrumbList`, `Organization`/`WebSite`),
  H1 na kvizovima, anketama i showdown-ima (izmena šablona, ne teksta).
- **Etapa 3:** Google Search Console (verifikacija preko DNS-a ili meta taga, slanje sitemap-a, praćenje
  uklanjanja 9.000 stranica pitanja), brzina početne strane.

## Rizici

- LiteSpeed keš može da servira stari `<head>`, pa purge posle deploy-a i importa mora da se uradi.
- Elementor stranice (početna) moraju da dobiju naš naslov; proveriti da Elementor ne ispisuje svoj.
- Google će par nedelja još prikazivati stranice pitanja dok ne pokupi `noindex`; to je očekivano.
- Biografije stavki su verovatno preuzete sa Vikipedije (duplirani sadržaj). Van obima, jer tekstove ne menjamo.

## Prepušteno izvođaču

- Raspored fajlova u `inc/seo/` i imena funkcija (prefiks `ygv_seo_`).
- Tačna heuristika skraćivanja uvoda (granica rečenice ili reči) i kako se rangiraju top stavke.
- Izgled i stil SEO kutije, u skladu sa postojećim admin kutijama.
- Dizajn `og-default.jpg` (na osnovu postojećeg logoa ili maskote, boje sajta).
- Da li se testira lokalno (osvežen DB preko `scripts/pull-db.sh`) ili direktno na produkciji uz
  oprezan deploy. Sam modul je samo čitanje i ispis plus jedna meta kutija, pa je rizik mali.
