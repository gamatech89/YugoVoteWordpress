# YugoVote SEO — Etapa 2: prave stranice kviza i ankete, H1, strukturirani podaci

Datum: 2026-11-24
Status: odobreno u razgovoru. Korisnik je izabrao opciju A (prave stranice) i izgled B („kartica, kao anketa“). Uslov: arhiva i slajder i dalje odmah otvaraju kviz u prozoru. Na kraju je rekao: „nastavi i sve na live mora da ide“.
Prethodna etapa: `docs/superpowers/specs/2026-11-15-yugovote-seo-etapa1-design.md` (puštena 2026-11-16).
Recenzija izvođača (2026-11-24): šest pitanja i šest netačnih činjenica. Sve je rešeno u ovom dokumentu; odluke su u odeljku „Odluke posle recenzije“.

## Namera

Etapa 1 je dala lepe naslove i opise za Google svim kvizovima (152) i anketama (28). Ali ko klikne
na kviz iz pretrage ili preko podeljenog linka, stiže na **praznu stranu**: `/quiz/x/` nema ni naslov,
ni sliku, ni dugme. Kviz se igra samo u prozoru iz arhive `/kvizovi/`. Anketa `/anketa/x/` prikazuje
jednu rečenicu, bez same ankete. Google takve strane vremenom proglasi praznim i spusti ih.

Cilj etape 2: posetilac sa Google-a ili sa deljenog linka dobija ono što je tražio, a Google dobija
jasnu strukturu: H1 na svakoj stranici i putanju u rezultatima („yugovote.com › Kvizovi › Culture Club“).

## Ciljevi

1. **Stranica kviza** (`/quiz/x/`) po izgledu B:
   - kratko indigo zaglavlje sa putanjom i oznakom „Kviz“;
   - kartica: slika levo, desno naslov (H1), podaci, opis i dugme „Započni kviz“, koje otvara postojeći prozor kviza;
   - ispod toga „Još kvizova“.
2. **Stranica ankete** (`/anketa/x/`) u istom izgledu: kartica sa slikom levo, a desno naslov (H1), opis i sama anketa za glasanje. Ispod toga „Još anketa“.
3. **Arhiva i slajder ostaju sa prozorom.** Klik na karticu ili dugme i dalje odmah otvara kviz u prozoru, bez koraka više. Jedina promena: naslov kartice postaje pravi link ka stranici kviza. Običan klik na naslov i dalje otvara prozor, a Ctrl/Cmd klik ili srednji taster otvara stranicu u novom tabu. Tako Google vidi linkove ka svim kvizovima.
4. **Tačno jedan H1** na svakoj vrsti stranice. H1 danas fali na:
   - početnoj;
   - kvizu i anketi (rešava tačka 1–2);
   - dvoboju (`/showdown/x/`);
   - `/liste/` i `/vip-osobe/`;
   - `/quiz-category/x/`;
   - `/voting-item-category/x/`.
5. **Strukturirani podaci (JSON-LD):**
   - `WebSite` i `Organization` na početnoj;
   - `BreadcrumbList` na listi, kategoriji lista, kvizu i anketi, i to tačno ona putanja koja se vidi na stranici.

## Ne-ciljevi

- Menjanje postojećih tekstova na sajtu. Novi tekstovi su samo četiri kratka, koje je korisnik video u mockup-u:
  - „Igraš odmah, bez registracije“
  - „Još kvizova“
  - „Još anketa“ (par prethodnom)
  - „Započni kviz“ (dugme)
- Redizajn sirovih Hello arhiva (`/liste/`, `/vip-osobe/`, `/quiz-category/x/`, `/voting-item-category/x/`). One dobijaju samo H1, a predlog za njih ide u izveštaj (etapa 3).
- Google „Quiz“ rich result (važi samo za obrazovne sajtove), `ItemList` za liste (ne daje poseban prikaz) i `SearchAction` (Google ga je ukinuo 2024).
- Search Console i brzina: etapa 3.

## Anti-ciljevi (neuspeh čak i ako „radi“)

- Arhiva ili slajder koji posle promene traže dodatni klik do kviza.
- Stranica kviza koja se sama otvara u prozoru po dolasku. Google kažnjava sadržaj prekriven prozorom.
- Lično stanje u javno keširanom HTML-u: tuđi rezultat, „Igraj ponovo“, „Glasao si“ kod gosta. Stranice za goste LiteSpeed kešira 7 dana (vidi popravku od 2026-11-22).
- JSON-LD putanja koja se razlikuje od vidljive.
- Neispravan JSON zbog navodnika ili `</script>` u naslovu.
- Vizuelno odstupanje od izgleda B ili lom na telefonu (390 px).

## Pristupi (odlučeno u razgovoru)

Za prazne stranice kviza i ankete:
- **A) Prave stranice.** Izabrano. To je jedini put na kom posetilac iz pretrage dobija ono što je tražio, a ručni opisi iz etape 1 rade svoj posao.
- B) Preusmeravanje `/quiz/x/` na arhivu, koja sama otvori kviz. Kvizovi tada nemaju svoje stranice u Google-u, a opisi propadaju.
- C) Sakrivanje iz Google-a. Gubi se sav saobraćaj na kvizove.

Za izgled (mockup `.superpowers/brainstorm/2455-1795001903/content/kviz-stranica-v2.html`, desktop i telefon):
- A) Zaglavlje kao arhive, sa zamućenom slikom.
- **B) Kartica, kao anketa.** Izabrano: slika se jasno vidi, dugme je na prvom ekranu telefona, a kviz i anketa izgledaju kao par.
- C) Poster preko celog ekrana. Odbačeno: 62 od 152 kviza imaju sliku užu od 600 px.

Za H1 na početnoj:
- **Logo kao H1**, samo na početnoj. Izabrano (moja odluka): vidljivo je isto kao sada, a tekst H1 je „YugoVote“ iz `alt`.
- Skriveni H1 sa rečenicom: Google ga toleriše, ali je to nevidljiv tekst.

Za H1 na sirovim Hello arhivama:
- **Uključiti Hello naslov (`hello_elementor_page_title`) samo za te tri vrste, bez prefiksa „Kategorija:“.** Izabrano (moja odluka): mala, bezbedna izmena.
- Lep šablon (npr. arhiva kvizova filtrirana po kategoriji). Odloženo: traži promenu JS filtera i URL-ova, a te stranice niko ne otvara iz menija.

## Kako sajt radi danas (činjenice za izvođača)

- Tema: Hello Elementor (3.4.7 na produkciji, 3.4.4 lokalno; mehanizam je isti) + child `wp-content/themes/hello-elementor-child/` (dalje THEME). Hello podešavanje `hello_elementor_settings_page_title=true` gasi naslove: Hello dodaje `add_filter('hello_elementor_page_title', '__return_false')`.
- **Kviz:**
  - CPT `quiz`, rewrite `quiz`, arhiva `/kvizovi/` (THEME `archive-quiz.php`). Šablona `single-quiz.php` nema, pa Hello prikazuje prazan `the_content()`.
  - Meta:
    - `_num_questions` (podrazumevano 10)
    - `_time_per_question` (sekunde, podrazumevano 10)
    - `_quiz_difficulty` (ID posta `quiz_levels`: naslov je naziv težine, slug određuje boju)
    - `_allow_guest_play` (`'1'` = gost može da igra)
    - `_quiz_xp_value` (podrazumevano 20)
    - `_quiz_description`: opis kviza (polje „Quiz Description“ u adminu). Imaju ga svih 152 kviza, a `post_excerpt` nema nijedan. Isti opis koriste prozor kviza i automatski opis iz etape 1.
  - Taksonomija `quiz_category`, boja kroz `ygv_get_quiz_category_color($quiz_id)`.
  - Napredak prijavljenog korisnika je u tabeli `{$wpdb->prefix}ygv_user_quiz_progress` (`best_percent`, `attempts`).
- **Prozor kviza:**
  - `js/quizzes/main.js` ima globalni handler: klik na `.ygv-quiz-start-btn[data-quiz-id]` otvara prozor; `data-locked="true"` otvara prozor za prijavu.
  - Skripta i `quizSettings` se učitavaju na svim stranicama (`inc/quizzes/quizzes-scripts.php`, `wp_enqueue_scripts`).
  - Na `/kvizovi/` klik na dugme u mreži prvo hvata `js/quizzes/quiz-archive-page.js` (`stopPropagation`, ne gleda `data-locked`), pa zaključan kviz otvara prozor kviza sa porukom za prijavu. To ostaje kako jeste.
- **Kartice kviza** (`.ygv-quiz-card`, naslov `h3.ygv-quiz-card__title`) postoje na tri mesta, sa skoro istim markup-om:
  - `archive-quiz.php` (petlja);
  - `inc/quizzes/api/quiz-archive-ajax.php` (AJAX filter arhive);
  - `inc/quizzes/templates/quiz-slider.php` (slajder na početnoj).
  - Logika za stanje kartice (zaključano, odigrano, novo) i boje težine je kopirana u sva tri.
  - Razlika: za gosta na zaključanom kvizu arhiva pri prvom učitavanju piše „Novi kviz za tebe!“, a AJAX filter i slajder pišu „Registracija potrebna“.
  - Ikonica `file-list` ne postoji u `inc/icons.php`, pa red „N pitanja“ danas nema ikonicu.
- **Anketa:**
  - CPT `voting_poll`, rewrite `anketa`, bez arhive. Arhiva je stranica `/anketa/` sa šablonom `page-ankete.php`.
  - Meta: `_cs_poll_answers`, `_cs_poll_total_votes`, `_cs_poll_locked`. Shortcode `[voting_poll id]` crta formu i rezultate (`inc/polls/templates/single-poll.php`).
  - Shortcode stavlja stranicu u privatni keš kad je gost već glasao, ali i za **svakog** gosta kad je anketa zaključana (`_cs_poll_locked`), jer zaključanu tretira kao „glasao“. Tako stranice sa zaključanom anketom (7 od 28, uključujući `/anketa/`) nikad nisu u javnom kešu.
  - Kartica ankete je `inc/polls/templates/poll-card.php` (sadrži i formu).
  - Plutajuća anketa se ispisuje na svim stranicama osim `/anketa/` i lista (`inc/polls/polls-init.php`).
  - Zbir glasova je u widgetu namerno sakriven („Hidden total votes per client request“).
- **Dvoboj:** naslov je `<h2 class="showdown-title">` u `inc/voting/showdown/shortcodes/showdown-shortcodes.php:66`. Druga `.showdown-title` (`:165`, „Prethodni dvoboji“) ostaje h2.
  - Isti shortcode `[yuv_showdown]` se crta i na početnoj (Elementor stranica 31369) i na `/showdown/` (`archive-yuv_showdown.php:57`), koja već ima `<h1>1 na 1</h1>`.
  - Izmereno na produkciji: h2 i h1 sa klasom `.showdown-title` se razlikuju samo po marginama (33,2 px prema 26,8 px, tj. 0,83em prema 0,67em).
- **Vidljive putanje danas:**
  - Lista `vlp-breadcrumb` (`inc/voting/templates/voting-list/voting-list-fullpage.php:699`): Početna › [nadkategorija] › [kategorija] › lista.
    - Kategorija je prva sa `parent !== 0` iz `get_the_terms(..., 'voting_list_category')`, inače prva.
    - Nadkategorija je njen parent.
  - Kategorija lista `ygv-cat-hero__breadcrumb` (`taxonomy-voting_list_category-fullpage.php:218`): Početna › [nadkategorija, ako je podkategorija] › kategorija.
- **SEO modul** iz etape 1 je u `THEME/inc/seo/`:
  - `ygv_seo_context()`: naslov, opis i URL;
  - `ygv_seo_clean()`: čist tekst bez HTML-a i entiteta;
  - `wp_head` prioritet 1.
- **Logo:**
  - Na sajtu je `Logo.png` (196×28, samo natpis) i maskota `THEME/assets/images/king-head.svg`.
  - Google za `Organization.logo` traži najmanje 112×112 px.
- **Društvene mreže** (iz footera): `https://www.facebook.com/profile.php?id=61591842951679`, `https://www.instagram.com/yugo_vote/`.

## Dizajn

### Stranica kviza (`THEME/single-quiz.php`)

Prati mockup B:
- **Boje:**
  - indigo gradijent `#6366f1 → #4338ca → #3730a3`;
  - panel slike `#5468d4 → #6b7fe3`;
  - dugme `--navy #2d3a8c`;
  - oznaka „Kviz“ `--coral #fe6555`.
- **Fontovi:** naslovi Jockey One, tekst Inter. Sajt ih već učitava; mockup ih je uzeo sa sajta.

Struktura:
1. **Zaglavlje (indigo traka)**, kratko, sa belim krugom kao dekoracijom. Sadrži:
   - putanju `Kvizovi › {kategorija} › {naslov}` (svetla, poslednja stavka podebljana, bez linka);
   - oznaku „Kviz“ (coral pill) desno, a na telefonu ispod putanje.
   - Linkovi u putanji:
     - „Kvizovi“ vodi na `/kvizovi/`;
     - kategorija vodi na `/kvizovi/?cat={slug}`, tj. na lepu filtriranu arhivu, a ne na sirovu `/quiz-category/x/`.
   - Ako kviz nema kategoriju, ta stavka se izostavlja.
2. **Kartica** koja se penje preko trake (`margin-top` negativan): bela, `border-radius 22px`, senka iz mockup-a.
   - **Levo** je indigo panel sa slikom 16:10 (`object-fit: cover`, `large`, `alt` je naslov) i bedžom kategorije u boji kategorije, gore levo.
     - Bez slike: gradijent u boji kategorije i ikonica `brain`, kao na arhivi.
   - **Desno:**
     - `h1` naslov kviza;
     - red podataka:
       - `{N} pitanja` (ikonica `file-text`; `file-list` ne postoji);
       - težina (`star`, u boji težine kao na arhivi);
       - `{s} s po pitanju` (`clock`);
       - `+{XP} XP` (`zap`).
       - Podatak koji fali se izostavlja; težina bez `_quiz_difficulty` se ne prikazuje.
     - opis je `_quiz_description` (ako je prazan, `post_excerpt`; ako su oba prazna, ne prikazuje se);
     - dugme preko cele širine (`button.ygv-quiz-start-btn[data-quiz-id]`), sa tri stanja:
       - **zaključan za gosta** (gost i `_allow_guest_play !== '1'`): ikonica `lock`, „Prijavi se da igraš“, `data-locked="true"`;
       - **odigran** (prijavljen, `attempts > 0`): ikonica `refresh`, „Igraj ponovo“;
       - **inače**: ikonica `play`, „Započni kviz“;
     - napomena ispod dugmeta: „Igraš odmah, bez registracije“, samo kad je posetilac gost, a kviz otvoren za goste.
3. **„Još kvizova“**: `h2` sa ikonicom `brain`, ispod mreža od 3 kartice. Na telefonu je red koji se prevlači (`scroll-snap`, kartica 76% širine), kao u mockup-u B.
   - Kartice su iste kao na arhivi (isti markup, isti stilovi, isto stanje), sa naslovom kao linkom.
   - Izbor:
     - do 3 najnovija objavljena kviza iz iste kategorije, bez trenutnog;
     - dopuna najnovijim kvizovima iz bilo koje kategorije;
     - bez duplikata.
   - Podnaslov iz mockup-a („Klik na karticu odmah otvara kviz.“) se **ne** dodaje: korisniku su najavljena dva nova teksta.

Telefon (≤ 767 px): vrednosti iz mockup-a (`hero-b` padding `16px 16px 92px`, kartica u jednoj koloni, h1 34 px, „Još kvizova“ kao red za prevlačenje, …). Stranica nema horizontalni skrol na 390 px; prevlači se samo red kartica.

Keš: HTML za gosta je isti za sve goste. Stanje „odigran“ postoji samo za prijavljene, a njihove stranice LiteSpeed ne stavlja u javni keš.

### Stranica ankete (`THEME/single-voting_poll.php`)

Isti okvir kao kviz:
- **Zaglavlje:** putanja `Ankete › {naslov}` („Ankete“ vodi na stranicu `/anketa/`) i oznaka „Anketa“.
- **Kartica:**
  - levo slika ankete, ili placeholder sa ikonicom kao u `poll-card.php`, bez bedža;
  - desno:
    - `h1` naslov;
    - opis iz `post_content` (`wp_kses_post`, kao „Anketa dana“);
    - ispod toga `[voting_poll id=X]`.
  - Pitanje `h4.ygv-poll__question` u widgetu se skriva, jer ponavlja H1, isto kao u „Anketi dana“.
  - Zbir glasova se ne prikazuje, po ranijem zahtevu klijenta.
- **„Još anketa“:** do 3 najnovije druge objavljene ankete **koje nisu zaključane**, kroz postojeći `poll-card.php` (sa formom za glasanje i brojem glasova, kao na `/anketa/`).
- Plutajuća anketa se ne ispisuje na stranici ankete.

### Kartice kviza: naslov kao link

- Na sva tri mesta (arhiva, AJAX filter, slajder) naslov postaje:
  `<h3 class="ygv-quiz-card__title"><a class="ygv-quiz-card__title-link" href="{permalink}">…</a></h3>`
- Link nasleđuje boju i font naslova, bez podvlačenja; hover je isti kao danas.
- Moja odluka: AJAX i slajder kopija (iste su) postaju jedan partial, `ygv_render_quiz_card(int $quiz_id, int $user_id): void` u `inc/quizzes/templates/quiz-card.php`. Koriste ga AJAX filter, slajder i „Još kvizova“.
  - Partial ispisuje isti HTML kao današnja AJAX kopija, plus link u naslovu. To potvrđuje test koji poredi stari i novi ispis.
  - Petlja u `archive-quiz.php` ostaje svoja (zbog teksta „Novi kviz za tebe!“) i samo dobija link u naslovu.
- JS u `main.js`: klik na `.ygv-quiz-card__title-link` levim tasterom, bez Ctrl/Cmd/Shift/Alt, radi `preventDefault()` i klikne dugme `.ygv-quiz-start-btn` iz iste kartice. Tako i zaključana kartica pokazuje prozor za prijavu. Svaki drugi klik radi kao običan link.

### H1

- **Početna:** u `template-parts/header/header-custom.php` je logo `<a class="ygv-header__logo">`.
  - Samo kad je `is_front_page()`, logo je umotan u `<h1 class="ygv-header__logo-h1">`, sa CSS resetom (`margin:0; font-size:inherit; line-height:0; display:flex; flex-shrink:0`).
  - Izgled se ne menja.
- **Dvoboj:** `inc/voting/showdown/shortcodes/showdown-shortcodes.php:66` postaje `h1` **samo kad je `is_singular('yuv_showdown')`**; na početnoj i na `/showdown/` ostaje `h2`.
  - Postojeći `.showdown-title` stil se primenjuje.
  - `.showdown-title { margin-top: .83em; margin-bottom: .83em; }` zadržava današnje margine.
- **Hello arhive:** filter `hello_elementor_page_title` (prioritet 20) vraća `true` za `is_post_type_archive(['voting_list','vip_person']) || is_tax(['quiz_category','voting_item_category'])`.
  - U istim slučajevima `get_the_archive_title_prefix` vraća `''`.
  - Tekst H1:
    - za kategorije je naziv kategorije;
    - za `/liste/` i `/vip-osobe/` su naslovi iz etape 1 (`ygv_seo_static_copy()`): „Sve rang liste za glasanje“ i „VIP liste poznatih ličnosti“. Engleski nazivi tipova („Voting Lists“, „VIP Persons“) se ne prikazuju.
- **Provera:** svaka vrsta stranice iz tabele ispod ima tačno jedan `<h1>` sa tekstom koji nije prazan.

### JSON-LD (`THEME/inc/seo/seo-schema.php`, učitava se iz `seo-init.php`)

- Jedan `<script type="application/ld+json">` po stranici, sa `@graph`, ispisan u `wp_head` posle meta bloka (prioritet 2).
- Kodiranje: `wp_json_encode` sa `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP`.
- Tekst za `name` ide kroz `ygv_seo_clean()`: bez HTML-a, entiteti dekodirani, jer naslovi u bazi imaju `&quot;`.

| Stranica | Sadržaj grafa |
|---|---|
| Početna | `WebSite` + `Organization` |
| Lista (`voting_list`) | `BreadcrumbList`: Početna › [nadkategorija] › [kategorija] › lista (ista logika kao vidljiva putanja) |
| Kategorija lista | `BreadcrumbList`: Početna › [nadkategorija] › kategorija |
| Kviz | `BreadcrumbList`: Kvizovi › [kategorija] › kviz |
| Anketa | `BreadcrumbList`: Ankete › anketa |
| Ostalo | ništa |

- **`WebSite`:**
  - `@id {home}#website`, `url`;
  - `name` „YugoVote“, `inLanguage` „sr-Latn“;
  - `publisher` je `{@id {home}#organization}`.
- **`Organization`:**
  - `@id {home}#organization`, `name` „YugoVote“, `url`;
  - `logo` je `ImageObject` sa url, širinom i visinom;
  - `sameAs` su dve društvene mreže.
- **Logo:** novi kvadratni PNG `THEME/assets/images/logo-schema.png` (512×512, maskota na beloj pozadini), napravljen iz `king-head.svg`.
- **`BreadcrumbList`:** `itemListElement[]` sa `ListItem {position (od 1), name, item (apsolutni URL)}`. Poslednja stavka nosi kanonski URL stranice.
- Putanju računa jedna funkcija, `ygv_breadcrumb_trail(): array<array{name:string,url:string}>`, za trenutni upit.
  - Za kviz i anketu istu funkciju koriste i novi šabloni za vidljivu putanju.
  - Za listu i kategoriju lista funkcija ponavlja logiku postojećih šablona (izbor kategorije je opisan gore). Postojeće šablone lista **ne diramo**, jer je rizik veći od koristi.
  - Da se putanje ne raziđu, HTTP provera upoređuje JSON-LD putanju sa vidljivom (`vlp-breadcrumb`, `ygv-cat-hero__breadcrumb`) na stvarnim stranicama.

## Odluke posle recenzije

Korisnik je rekao „nastavi da radiš i donosiš odluke sam“ i „sve na live mora da ide“, pa su sve odluke moje:

1. **H1 na `/liste/` i `/vip-osobe/`**: naslovi iz etape 1, ne engleski naziv tipa. Ako nije dobro: jedan srpski naslov više, koji se lako menja.
2. **„Još kvizova“ na telefonu**: red za prevlačenje, kao u mockup-u. Ako nije dobro: jedna CSS izmena.
3. **Kartice**: zajednički partial je AJAX i slajder varijanta, a arhiva zadržava svoju. Ako nije dobro: tekst ostaje nedosledan kao i danas, ali se ništa vidljivo ne menja.
4. **`/vip-osobe/` dobija H1.** Ako nije dobro: ništa, to je samo H1.
5. **„Još anketa“** prikazuje broj glasova kao kartice na `/anketa/`, a zaključane ankete preskače, jer za njih nema glasanja, a i isključile bi keš stranice. Ako nije dobro: druge tri ankete u izboru.
6. **Putanja kviza**: kategorija vodi na `/kvizovi/?cat={slug}` i u HTML-u i u JSON-LD-u, iako je canonical te adrese `/kvizovi/`. Google to prihvata; korisnik dobija lepu stranicu umesto sirove `/quiz-category/x/`. Ako nije dobro: Google u putanji pokaže „Kvizovi“, a ne kategoriju.
7. **Usputna popravka keša anketa**: privatni keš samo kad je gost stvarno glasao, a ne za svaku zaključanu anketu. Zaključana anketa svima prikazuje iste rezultate, pa javni keš tu ne curi ništa lično. Ako nije dobro: stranice sa zaključanom anketom rade sporije, kao i danas.

## Provera (testovi)

- **PHP test u kontejneru** (`tests/seo/test-etapa2.php`, isti obrazac kao etapa 1; prolazi samo kad ispiše „SVE PASS“):
  - stanje dugmeta kviza (gost otvoren / gost zaključan / prijavljen odigran);
  - opis kviza iz `_quiz_description`;
  - partial kartice daje isti HTML kao stara AJAX kopija (osim linka u naslovu);
  - H1 dvoboja: `h1` na stranici dvoboja, `h2` drugde;
  - keš ankete: zaključana anketa za gosta koji nije glasao ne traži privatni keš;
  - „Još anketa“ preskače zaključane;
  - izbor „Još kvizova“ (ista kategorija, dopuna, bez trenutnog, bez duplikata);
  - `ygv_breadcrumb_trail()` za listu sa i bez nadkategorije, za kategoriju i podkategoriju, za kviz sa i bez kategorije i za anketu;
  - JSON-LD je ispravan JSON i ima `JSON_HEX_TAG` zaštitu za naslov sa `</script>` i navodnicima.
- **HTTP provera** (`tests/seo/check_head.py` se proširuje, radi i protiv produkcije):
  - po jedan URL za početnu, listu, kategoriju lista, kviz, anketu, dvoboj, `/showdown/`, `/kvizovi/`, `/liste/`, `/vip-osobe/`, `/quiz-category/x/` i `/voting-item-category/x/`;
  - tačno jedan H1 koji nije prazan;
  - JSON-LD se parsira, `@type` je očekivan, a putanja iz JSON-LD-a je jednaka vidljivoj;
  - stranica kviza ima dugme `ygv-quiz-start-btn` sa ispravnim `data-quiz-id`;
  - stranica ankete ima formu `ygv-poll`;
  - kartice na `/kvizovi/` imaju link ka `/quiz/`.
- **Pregledač** (lokalno, pa produkcija):
  - snimci kviza i ankete, desktop i 390 px, upoređeni sa mockup-om B;
  - klik na „Započni kviz“ otvara prozor;
  - klik na naslov kartice na arhivi otvara prozor, a Cmd-klik otvara novi tab;
  - zaključan kviz kao gost: na stranici kviza i u slajderu otvara prozor za prijavu, a na `/kvizovi/` prozor kviza sa porukom za prijavu (kao danas);
  - glasanje u anketi sa stranice ankete radi kao gost;
  - početna i dvoboj izgledaju isto kao pre.

## Puštanje

- Korisnik je odobrio: „sve na live mora da ide“.
- Redosled: merge `seo-etapa2` u `main`, push, `bash scripts/deploy-theme.sh`, pa `wp litespeed-purge all`.
- Posle toga: HTTP provera protiv `https://yugovote.com`, snimci sa produkcije i Google Rich Results test za kviz i listu.
- Bez promena u bazi.
- Povratak: `git revert` i ponovni deploy.

## Ostavljeno izvođaču

- Tačne vrednosti CSS-a za desktop prepisati iz mockup-a. Klase dobijaju prefiks `ygv-landing-`.
- Novi stil ide u `THEME/css/landing.css`, sa verzijom `filemtime`. Učitava se samo na stranicama kviza i ankete, zajedno sa `css/templates.css` zbog kartica kviza.
- Izbor ikonica u redu podataka je u `inc/icons.php`.
