# Instagram kviz-Reel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Skripta koja od ID-a pitanja iz YugoVote baze pravi gotov vertikalni Instagram Reel, u tri vizuelne teme koje se porede prije izbora stalnog formata.

**Architecture:** Svaki frejm overlay-a je čista funkcija vremena — `frameSvg(t)` vraća SVG string, `rsvg-convert` ga pretvara u PNG sa alfom, ffmpeg preklopi cijelu sekvencu preko pozadine jednom komandom. Nema animacije u ffmpeg-u, nema `enable=` izraza; sva logika je u JavaScriptu gdje se može testirati. Tema je podatak, ne grana koda.

**Tech Stack:** Node 22 (ESM, `node:test`, bez runtime zavisnosti), `rsvg-convert` (librsvg), ffmpeg 8.1.2, WordPress REST API.

## Global Constraints

- **ffmpeg nema `drawtext` ni `libass`.** Build je Homebrew 8.1.2_1 bez `libfreetype`. Svaki filtergraph koji ih koristi neće raditi. Tekst ide isključivo kroz SVG → `rsvg-convert` → `overlay`.
- **Chendolle (brend font) nema našu dijakritiku.** Ima 157 znakova; nedostaju Č č Ć ć Š š Ž ž Đ đ i navodnici „ ”. Koristi se **isključivo** za wordmark "YUGOVOTE", koji ih ne sadrži. Sav dinamički tekst ide u Poppins.
- **Sve na srpskom, latinica.**
- **Nivo i kategorija se čitaju iz API-ja**, nikad ne upisuju ručno. Pitanje 44065 je `Intermediate`, kategorija `Muzika`.
- **Izlaz:** 1080×1920, 30 fps, 13.0 s, H.264, `yuv420p`.
- **Brend boje:** navy `#4456A6`, koralna `#FE6555`, žuta `#FFCB05`, narandžasta `#FAA74A`.
- **Boje kategorija** (`--cat-color` sa sajta): Sport `#36c43f`, Biznis `#4457a5`, Muzika `#b0b9dd`, Film i TV `#e65552`, Culture Club `#e9e33a`, Trendy/Lifestyle `#f599e9`.
- **Tajna se ne odaje prije vremena:** tačan odgovor se ne smije pojaviti ni u jednom frejmu prije `t = 8.0 s`.
- Radni direktorijum svih komandi: `scripts/ig-reel/`.

---

### Task 1: Skela, fontovi i zaštita od nepokrivenih znakova

Prva stvar koja se gradi je test koji hvata baš onu grešku zbog koje brend font ne može nositi tekst. Bez njega se ista greška vraća tiho, kroz pogrešno ispisano ime.

**Files:**
- Create: `scripts/ig-reel/package.json`
- Create: `scripts/ig-reel/src/font.js`
- Create: `scripts/ig-reel/fonts/.gitkeep`
- Create: `scripts/ig-reel/fontconfig/fonts.conf`
- Create: `scripts/ig-reel/.gitignore`
- Test: `scripts/ig-reel/test/font.test.js`

**Interfaces:**
- Consumes: ništa
- Produces:
  - `coveredCodepoints(ttfPath: string) => Set<number>` — skup Unicode kodnih tačaka iz `cmap` tabele
  - `REQUIRED_CHARS: string` — znakovi koje font za tekst mora imati
  - `assertCoverage(ttfPath: string, chars?: string) => void` — baca `Error` sa spiskom nedostajućih znakova

- [ ] **Step 1: Napravi package.json**

```json
{
  "name": "yugovote-ig-reel",
  "version": "1.0.0",
  "private": true,
  "type": "module",
  "scripts": {
    "test": "node --test test/"
  }
}
```

- [ ] **Step 2: Napravi .gitignore**

```gitignore
out/
frames/
assets/bg/*.mp4
assets/bg/*.png
```

Pozadinski snimci se ne komituju — veliki su i regenerišu se preko Higgsfielda (Task 6).

- [ ] **Step 3: Napiši test koji pada**

`test/font.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { coveredCodepoints, assertCoverage, REQUIRED_CHARS } from '../src/font.js';

const POPPINS = new URL('../fonts/Poppins-Bold.ttf', import.meta.url).pathname;
const CHENDOLLE = new URL('../fonts/Chendolle.ttf', import.meta.url).pathname;

test('REQUIRED_CHARS sadrzi svu nasu dijakritiku i navodnike', () => {
  for (const ch of 'ČčĆćŠšŽžĐđ„”') {
    assert.ok(REQUIRED_CHARS.includes(ch), `nedostaje ${ch}`);
  }
});

test('Poppins pokriva sve trazene znakove', () => {
  assert.doesNotThrow(() => assertCoverage(POPPINS));
});

test('coveredCodepoints vraca neprazan skup', () => {
  const set = coveredCodepoints(POPPINS);
  assert.ok(set.size > 200, `premalo znakova: ${set.size}`);
  assert.ok(set.has(0x010C), 'nema C sa kvacicom');
});

test('Chendolle NEMA nasu dijakritiku - ovo je ocekivano i mora ostati istina', () => {
  const set = coveredCodepoints(CHENDOLLE);
  assert.equal(set.has(0x0160), false, 'Chendolle odjednom ima S sa kvacicom');
  assert.throws(() => assertCoverage(CHENDOLLE), /nedostaju/);
});

test('assertCoverage imenuje tacno koji znakovi nedostaju', () => {
  try {
    assertCoverage(CHENDOLLE);
    assert.fail('trebalo je da baci');
  } catch (err) {
    assert.match(err.message, /Š/);
  }
});
```

- [ ] **Step 4: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/font.test.js`
Expected: FAIL — `Cannot find module '../src/font.js'`

- [ ] **Step 5: Skini fontove**

```bash
cd scripts/ig-reel/fonts
curl -sL "https://yugovote.com/wp-content/uploads/2025/01/Chendolle.ttf" -o Chendolle.ttf
curl -sL "https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-Bold.ttf" -o Poppins-Bold.ttf
curl -sL "https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-SemiBold.ttf" -o Poppins-SemiBold.ttf
curl -sL "https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-Regular.ttf" -o Poppins-Regular.ttf
ls -la
```

Poppins je pod SIL Open Font License, pa se smije komitovati u repo. Chendolle je brend font sa sajta.

- [ ] **Step 6: Implementiraj src/font.js**

```js
import { readFileSync } from 'node:fs';

export const REQUIRED_CHARS = 'ČčĆćŠšŽžĐđ„”…—abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789?!.,:;()/-';

/** Cita cmap format 4 i vraca skup pokrivenih kodnih tacaka. */
export function coveredCodepoints(ttfPath) {
  const d = readFileSync(ttfPath);
  const base = d.readUInt32BE(0) === 0x74746366 ? d.readUInt32BE(12) : 0;
  const numTables = d.readUInt16BE(base + 4);

  let cmapOff = null;
  for (let i = 0; i < numTables; i++) {
    const o = base + 12 + 16 * i;
    if (d.toString('latin1', o, o + 4) === 'cmap') cmapOff = d.readUInt32BE(o + 8);
  }
  if (cmapOff === null) throw new Error(`nema cmap tabele: ${ttfPath}`);

  const numSub = d.readUInt16BE(cmapOff + 2);
  let sub = null;
  for (let i = 0; i < numSub; i++) {
    const o = cmapOff + 4 + 8 * i;
    const pid = d.readUInt16BE(o);
    const eid = d.readUInt16BE(o + 2);
    const key = `${pid},${eid}`;
    if (['3,1', '3,10', '0,3', '0,4'].includes(key)) sub = cmapOff + d.readUInt32BE(o + 4);
  }
  if (sub === null) throw new Error(`nema Unicode cmap podtabele: ${ttfPath}`);
  if (d.readUInt16BE(sub) !== 4) throw new Error(`cmap nije format 4: ${ttfPath}`);

  const segX2 = d.readUInt16BE(sub + 6);
  const seg = segX2 / 2;
  const endBase = sub + 14;
  const startBase = endBase + segX2 + 2;

  const out = new Set();
  for (let i = 0; i < seg; i++) {
    const end = d.readUInt16BE(endBase + 2 * i);
    const start = d.readUInt16BE(startBase + 2 * i);
    if (end === 0xffff) continue;
    for (let c = start; c <= end; c++) out.add(c);
  }
  return out;
}

/** Baca gresku sa spiskom nedostajucih znakova. */
export function assertCoverage(ttfPath, chars = REQUIRED_CHARS) {
  const set = coveredCodepoints(ttfPath);
  const missing = [...new Set(chars)].filter((ch) => !set.has(ch.codePointAt(0)));
  if (missing.length) {
    throw new Error(`fontu ${ttfPath} nedostaju znakovi: ${missing.join(' ')}`);
  }
}
```

- [ ] **Step 7: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/font.test.js`
Expected: PASS, svih 5 testova

- [ ] **Step 8: Napravi fontconfig koji ne dira sistemski Font Book**

`fontconfig/fonts.conf`:

```xml
<?xml version="1.0"?>
<!DOCTYPE fontconfig SYSTEM "fonts.dtd">
<fontconfig>
  <dir prefix="relative">../fonts</dir>
  <cachedir prefix="relative">../.fontcache</cachedir>
  <config></config>
</fontconfig>
```

Render se pokreće sa `FONTCONFIG_FILE=$PWD/fontconfig/fonts.conf`, pa `rsvg-convert` vidi samo naša dva fonta. Ništa se ne instalira u sistem.

- [ ] **Step 9: Provjeri da fontconfig stvarno radi**

```bash
cd scripts/ig-reel
mkdir -p .fontcache out
printf '%s' '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="120"><rect width="900" height="120" fill="#4456A6"/><text x="20" y="80" font-family="Poppins" font-weight="bold" font-size="56" fill="#fff">Šerifović, Đorđe, Čolić</text></svg>' > out/probe.svg
FONTCONFIG_FILE=$PWD/fontconfig/fonts.conf rsvg-convert -w 900 -h 120 out/probe.svg -o out/probe.png
```

Expected: `out/probe.png` postoji. Otvori ga i potvrdi očima da piše `Šerifović, Đorđe, Čolić` u Poppins-u, bez kvadratića.

- [ ] **Step 10: Commit**

```bash
git add scripts/ig-reel/package.json scripts/ig-reel/.gitignore scripts/ig-reel/src/font.js \
        scripts/ig-reel/test/font.test.js scripts/ig-reel/fontconfig/fonts.conf scripts/ig-reel/fonts/
git commit -m "IG Reel: skela, fontovi i provjera pokrivenosti znakova"
```

---

### Task 2: Dohvatanje pitanja iz YugoVote API-ja

**Files:**
- Create: `scripts/ig-reel/src/api.js`
- Create: `scripts/ig-reel/test/fixtures/question-44065.json`
- Test: `scripts/ig-reel/test/api.test.js`

**Interfaces:**
- Consumes: ništa
- Produces:
  - `normalizeQuestion(raw: object) => Question` gdje je
    `Question = { id: number, text: string, answers: string[], correctIndex: number, level: string, categoryName: string, categoryId: number }`
  - `fetchQuestion(id: number, env?: object) => Promise<Question>`

- [ ] **Step 1: Snimi fixture iz stvarnog API-ja**

```bash
cd /Users/bmarkovic/Documents/Projects/YugoVote/yugovote-mcp
set -a && . ./.env && set +a
AUTH=$(printf '%s:%s' "$WP_USERNAME" "$WP_APP_PASSWORD" | base64)
curl -s -H "Authorization: Basic $AUTH" \
  "$WP_BASE_URL/wp-json/yugovote-mcp/v1/questions/44065" \
  > ../scripts/ig-reel/test/fixtures/question-44065.json
```

- [ ] **Step 2: Napiši test koji pada**

`test/api.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { normalizeQuestion } from '../src/api.js';

const raw = JSON.parse(
  readFileSync(new URL('./fixtures/question-44065.json', import.meta.url), 'utf8')
);

test('normalizeQuestion izvlaci polja koja render koristi', () => {
  const q = normalizeQuestion(raw);
  assert.equal(q.id, 44065);
  assert.equal(q.text, 'Koja je grupa odnela jedinu pobedu za Jugoslaviju na Evroviziji 1989. godine?');
  assert.deepEqual(q.answers, ['Pepel in kri', 'Riva', 'Novi Fosili', 'Srebrna krila']);
  assert.equal(q.correctIndex, 1);
  assert.equal(q.answers[q.correctIndex], 'Riva');
});

test('nivo i kategorija dolaze iz baze, ne iz koda', () => {
  const q = normalizeQuestion(raw);
  assert.equal(q.level, 'Intermediate');
  assert.equal(q.categoryName, 'Muzika');
  assert.equal(q.categoryId, 25);
});

test('pitanje bez kategorije ne rusi normalizaciju', () => {
  const q = normalizeQuestion({ ...raw, categories: [] });
  assert.equal(q.categoryName, null);
  assert.equal(q.categoryId, null);
});

test('correct_answer van opsega je greska, ne tiha nula', () => {
  assert.throws(
    () => normalizeQuestion({ ...raw, correct_answer: 9 }),
    /correct_answer/
  );
});
```

- [ ] **Step 3: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/api.test.js`
Expected: FAIL — `Cannot find module '../src/api.js'`

- [ ] **Step 4: Implementiraj src/api.js**

```js
const API_PATH = '/wp-json/yugovote-mcp/v1';

export function normalizeQuestion(raw) {
  const answers = Array.isArray(raw.answers) ? raw.answers : [];
  const idx = Number(raw.correct_answer);

  if (!Number.isInteger(idx) || idx < 0 || idx >= answers.length) {
    throw new Error(
      `correct_answer ${raw.correct_answer} je van opsega za ${answers.length} odgovora (pitanje ${raw.id})`
    );
  }

  const cat = Array.isArray(raw.categories) && raw.categories.length ? raw.categories[0] : null;

  return {
    id: Number(raw.id),
    text: String(raw.question_text ?? raw.title ?? '').trim(),
    answers: answers.map((a) => String(a).trim()),
    correctIndex: idx,
    level: raw.difficulty ? String(raw.difficulty) : null,
    categoryName: cat ? String(cat.name) : null,
    categoryId: cat ? Number(cat.id) : null,
  };
}

export async function fetchQuestion(id, env = process.env) {
  const { WP_BASE_URL, WP_USERNAME, WP_APP_PASSWORD } = env;
  if (!WP_BASE_URL || !WP_USERNAME || !WP_APP_PASSWORD) {
    throw new Error('nedostaju WP_BASE_URL, WP_USERNAME ili WP_APP_PASSWORD');
  }

  const auth = Buffer.from(`${WP_USERNAME}:${WP_APP_PASSWORD}`).toString('base64');
  const res = await fetch(`${WP_BASE_URL}${API_PATH}/questions/${id}`, {
    headers: { Authorization: `Basic ${auth}`, Accept: 'application/json' },
  });

  if (!res.ok) throw new Error(`API ${res.status} za pitanje ${id}`);
  return normalizeQuestion(await res.json());
}
```

- [ ] **Step 5: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/api.test.js`
Expected: PASS, sva 4 testa

- [ ] **Step 6: Commit**

```bash
git add scripts/ig-reel/src/api.js scripts/ig-reel/test/api.test.js scripts/ig-reel/test/fixtures/
git commit -m "IG Reel: dohvatanje i normalizacija pitanja iz API-ja"
```

---

### Task 3: Prelom teksta i XML escape

Ovo je mjesto gdje se format najlakše tiho pokvari. Tekst pitanja sadrži `„ ” ' &`, a jedan neescape-ovan `&` obara cijeli SVG bez jasne poruke.

**Files:**
- Create: `scripts/ig-reel/src/text.js`
- Test: `scripts/ig-reel/test/text.test.js`

**Interfaces:**
- Consumes: ništa
- Produces:
  - `xmlEscape(s: string) => string`
  - `wrapText(s: string, maxCharsPerLine: number) => string[]`

- [ ] **Step 1: Napiši test koji pada**

`test/text.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { xmlEscape, wrapText } from '../src/text.js';

test('xmlEscape stiti pet XML znakova', () => {
  assert.equal(xmlEscape('a & b'), 'a &amp; b');
  assert.equal(xmlEscape('<tag>'), '&lt;tag&gt;');
  assert.equal(xmlEscape('"x"'), '&quot;x&quot;');
  assert.equal(xmlEscape("Jel' ti reka ko"), 'Jel&apos; ti reka ko');
});

test('xmlEscape ne dira nasu dijakritiku ni srpske navodnike', () => {
  const s = '„Šerifović, Đorđe i Čolić”';
  assert.equal(xmlEscape(s), s);
});

test('xmlEscape escapuje ampersand pre nego ostalo, bez dvostrukog escape-a', () => {
  assert.equal(xmlEscape('&lt;'), '&amp;lt;');
});

test('wrapText lomi po rijecima i nikad ne prelazi granicu', () => {
  const lines = wrapText('Koja je grupa odnela jedinu pobedu za Jugoslaviju na Evroviziji 1989. godine?', 24);
  assert.ok(lines.length > 1);
  for (const l of lines) assert.ok(l.length <= 24, `predugacak red: "${l}" (${l.length})`);
  assert.equal(lines.join(' '), 'Koja je grupa odnela jedinu pobedu za Jugoslaviju na Evroviziji 1989. godine?');
});

test('wrapText ne gubi i ne duplira rijeci', () => {
  const src = 'Pepel in kri Riva Novi Fosili Srebrna krila';
  assert.equal(wrapText(src, 10).join(' '), src);
});

test('rijec duza od granice ide u svoj red umjesto da se izgubi', () => {
  const lines = wrapText('kratko supernedopustivodugackarijec kraj', 10);
  assert.ok(lines.includes('supernedopustivodugackarijec'));
  assert.equal(lines.join(' '), 'kratko supernedopustivodugackarijec kraj');
});

test('prazan ulaz daje prazan niz', () => {
  assert.deepEqual(wrapText('', 20), []);
  assert.deepEqual(wrapText('   ', 20), []);
});
```

- [ ] **Step 2: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/text.test.js`
Expected: FAIL — `Cannot find module '../src/text.js'`

- [ ] **Step 3: Implementiraj src/text.js**

```js
/** Escapuje pet XML znakova. Ampersand prvi, inace bi se duplo escapovao. */
export function xmlEscape(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;');
}

/** Lomi tekst po rijecima. Rijec duza od granice dobija svoj red. */
export function wrapText(s, maxCharsPerLine) {
  const words = String(s).trim().split(/\s+/).filter(Boolean);
  if (!words.length) return [];

  const lines = [];
  let cur = '';

  for (const w of words) {
    if (!cur) {
      cur = w;
    } else if (cur.length + 1 + w.length <= maxCharsPerLine) {
      cur += ' ' + w;
    } else {
      lines.push(cur);
      cur = w;
    }
  }
  if (cur) lines.push(cur);
  return lines;
}
```

- [ ] **Step 4: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/text.test.js`
Expected: PASS, svih 7 testova

- [ ] **Step 5: Commit**

```bash
git add scripts/ig-reel/src/text.js scripts/ig-reel/test/text.test.js
git commit -m "IG Reel: prelom teksta i XML escape"
```

---

### Task 4: Teme i vremenska osa

Tri vizuelna pravca su tri konfiguracije, ne tri grane koda. Vremenska osa je zajednička i definisana jednom.

**Files:**
- Create: `scripts/ig-reel/src/theme.js`
- Create: `scripts/ig-reel/src/timeline.js`
- Test: `scripts/ig-reel/test/theme.test.js`

**Interfaces:**
- Consumes: ništa
- Produces:
  - `THEMES: Record<'hybrid'|'light'|'cinematic', Theme>` gdje `Theme = { name, usesVideoBg: boolean, bg: string|null, textColor, panelColor, panelOpacity, cardFill, cardText, timerRunning, timerExpired, revealColor }`
  - `CATEGORY_COLORS: Record<string, string>`
  - `categoryColor(name: string|null) => string` — boja kategorije, ili brend navy ako kategorija nije poznata
  - `BRAND: { navy, coral, yellow, orange }`
  - `PHASES: { questionIn, answersIn, timerStart, timerEnd, revealAt, endCardAt, total }`
  - `phaseAt(t: number) => 'question'|'answers'|'timer'|'reveal'|'endcard'`
  - `timerProgress(t: number) => number` — 1 na početku odbrojavanja, 0 na kraju, van odbrojavanja tačno 1 ili 0

- [ ] **Step 1: Napiši test koji pada**

`test/theme.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { THEMES, CATEGORY_COLORS, BRAND } from '../src/theme.js';
import { PHASES, phaseAt, timerProgress } from '../src/timeline.js';

test('postoje tacno tri teme', () => {
  assert.deepEqual(Object.keys(THEMES).sort(), ['cinematic', 'hybrid', 'light']);
});

test('svaka tema ima sva polja koja render trazi', () => {
  const keys = ['name', 'usesVideoBg', 'bg', 'textColor', 'panelColor', 'panelOpacity',
                'cardFill', 'cardText', 'timerRunning', 'timerExpired', 'revealColor'];
  for (const [id, th] of Object.entries(THEMES)) {
    for (const k of keys) assert.ok(k in th, `temi ${id} nedostaje ${k}`);
  }
});

test('light tema ne koristi video pozadinu, druge dvije koriste', () => {
  assert.equal(THEMES.light.usesVideoBg, false);
  assert.equal(typeof THEMES.light.bg, 'string');
  assert.equal(THEMES.hybrid.usesVideoBg, true);
  assert.equal(THEMES.cinematic.usesVideoBg, true);
});

test('brend boje su tacne vrijednosti iz logotipa', () => {
  assert.equal(BRAND.navy, '#4456A6');
  assert.equal(BRAND.coral, '#FE6555');
  assert.equal(BRAND.yellow, '#FFCB05');
  assert.equal(BRAND.orange, '#FAA74A');
});

test('boje kategorija se poklapaju sa sajtom', () => {
  assert.equal(CATEGORY_COLORS['Muzika'], '#b0b9dd');
  assert.equal(CATEGORY_COLORS['Sport'], '#36c43f');
  assert.equal(CATEGORY_COLORS['Film i tv'], '#e65552');
  assert.equal(Object.keys(CATEGORY_COLORS).length, 6);
});

test('vremenska osa traje tacno 13 sekundi i faze su rastuce', () => {
  assert.equal(PHASES.total, 13);
  const order = [PHASES.questionIn, PHASES.answersIn, PHASES.timerStart,
                 PHASES.timerEnd, PHASES.revealAt, PHASES.endCardAt, PHASES.total];
  for (let i = 1; i < order.length; i++) {
    assert.ok(order[i] >= order[i - 1], `faza ${i} ide unazad`);
  }
});

test('phaseAt vraca tacnu fazu na granicama', () => {
  assert.equal(phaseAt(0), 'question');
  assert.equal(phaseAt(2), 'answers');
  assert.equal(phaseAt(5), 'timer');
  assert.equal(phaseAt(8), 'reveal');
  assert.equal(phaseAt(11), 'endcard');
  assert.equal(phaseAt(12.99), 'endcard');
});

test('odgovor se ne smije otkriti prije 8. sekunde', () => {
  for (const t of [0, 1, 3, 5, 7.9]) {
    assert.notEqual(phaseAt(t), 'reveal', `otkriva na t=${t}`);
  }
});

test('timerProgress ide od 1 do 0 i ne izlazi iz opsega', () => {
  assert.equal(timerProgress(PHASES.timerStart), 1);
  assert.equal(timerProgress(PHASES.timerEnd), 0);
  assert.ok(Math.abs(timerProgress(5.5) - 0.5) < 0.001);
  assert.equal(timerProgress(0), 1);
  assert.equal(timerProgress(12), 0);
});
```

- [ ] **Step 2: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/theme.test.js`
Expected: FAIL — `Cannot find module '../src/theme.js'`

- [ ] **Step 3: Implementiraj src/theme.js**

```js
export const BRAND = {
  navy: '#4456A6',
  coral: '#FE6555',
  yellow: '#FFCB05',
  orange: '#FAA74A',
};

/** Preuzeto sa yugovote.com, CSS varijabla --cat-color. */
export const CATEGORY_COLORS = {
  'Sport': '#36c43f',
  'Biznis': '#4457a5',
  'Muzika': '#b0b9dd',
  'Film i tv': '#e65552',
  'Culture Club': '#e9e33a',
  'Trendy / Lifestyle': '#f599e9',
};

export const THEMES = {
  hybrid: {
    name: 'hybrid',
    usesVideoBg: true,
    bg: null,
    textColor: '#FFFFFF',
    panelColor: '#000000',
    panelOpacity: 0.45,
    cardFill: '#FFFFFF',
    cardText: BRAND.navy,
    timerRunning: BRAND.navy,
    timerExpired: BRAND.coral,
    revealColor: BRAND.coral,
  },
  light: {
    name: 'light',
    usesVideoBg: false,
    bg: '#FFFFFF',
    textColor: BRAND.navy,
    panelColor: '#FFFFFF',
    panelOpacity: 0,
    cardFill: '#FFFFFF',
    cardText: BRAND.navy,
    timerRunning: BRAND.navy,
    timerExpired: BRAND.coral,
    revealColor: BRAND.coral,
  },
  cinematic: {
    name: 'cinematic',
    usesVideoBg: true,
    bg: null,
    textColor: '#FFFFFF',
    panelColor: '#000000',
    panelOpacity: 0.65,
    cardFill: '#FFFFFF',
    cardText: '#111111',
    timerRunning: '#FFFFFF',
    timerExpired: BRAND.coral,
    revealColor: BRAND.coral,
  },
};

export function categoryColor(name) {
  return CATEGORY_COLORS[name] ?? BRAND.navy;
}
```

- [ ] **Step 4: Implementiraj src/timeline.js**

```js
export const PHASES = {
  questionIn: 0,
  answersIn: 1,
  timerStart: 3,
  timerEnd: 8,
  revealAt: 8,
  endCardAt: 10,
  total: 13,
};

export function phaseAt(t) {
  if (t >= PHASES.endCardAt) return 'endcard';
  if (t >= PHASES.revealAt) return 'reveal';
  if (t >= PHASES.timerStart) return 'timer';
  if (t >= PHASES.answersIn) return 'answers';
  return 'question';
}

/** 1 na pocetku odbrojavanja, 0 na kraju. Van odbrojavanja tacno 1 ili 0. */
export function timerProgress(t) {
  if (t <= PHASES.timerStart) return 1;
  if (t >= PHASES.timerEnd) return 0;
  return 1 - (t - PHASES.timerStart) / (PHASES.timerEnd - PHASES.timerStart);
}
```

- [ ] **Step 5: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/theme.test.js`
Expected: PASS, svih 9 testova

- [ ] **Step 6: Commit**

```bash
git add scripts/ig-reel/src/theme.js scripts/ig-reel/src/timeline.js scripts/ig-reel/test/theme.test.js
git commit -m "IG Reel: tri teme i zajednicka vremenska osa"
```

---

### Task 5: Gradnja SVG frejma

**Files:**
- Create: `scripts/ig-reel/src/frame.js`
- Test: `scripts/ig-reel/test/frame.test.js`

**Interfaces:**
- Consumes: `xmlEscape`, `wrapText` (Task 3); `THEMES`, `categoryColor`, `BRAND` (Task 4); `PHASES`, `phaseAt`, `timerProgress` (Task 4); `Question` (Task 2)
- Produces:
  - `W: 1080`, `H: 1920`
  - `frameSvg({ question, theme, t }) => string` — kompletan SVG dokument za taj trenutak

- [ ] **Step 1: Napiši test koji pada**

`test/frame.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { frameSvg, W, H } from '../src/frame.js';
import { THEMES } from '../src/theme.js';

const Q = {
  id: 44065,
  text: 'Koja je grupa odnela jedinu pobedu za Jugoslaviju na Evroviziji 1989. godine?',
  answers: ['Pepel in kri', 'Riva', 'Novi Fosili', 'Srebrna krila'],
  correctIndex: 1,
  level: 'Intermediate',
  categoryName: 'Muzika',
  categoryId: 25,
};

const at = (t, theme = THEMES.hybrid) => frameSvg({ question: Q, theme, t });

test('dimenzije su vertikalne 1080x1920', () => {
  assert.equal(W, 1080);
  assert.equal(H, 1920);
  const svg = at(0);
  assert.match(svg, /width="1080"/);
  assert.match(svg, /height="1920"/);
});

test('frejm je jedan validan SVG dokument', () => {
  const svg = at(5);
  assert.ok(svg.startsWith('<svg'));
  assert.ok(svg.trimEnd().endsWith('</svg>'));
  assert.equal((svg.match(/<svg/g) || []).length, 1);
});

test('pitanje je vidljivo od prve sekunde', () => {
  assert.match(at(0.5), /Evroviziji/);
});

test('logotip se NE pojavljuje u prvoj sekundi', () => {
  assert.doesNotMatch(at(0.5), /YUGOVOTE/);
  assert.match(at(11), /YUGOVOTE/);
});

test('tacan odgovor se ne pojavljuje ni u jednom frejmu prije 8s', () => {
  for (let t = 0; t < 8; t += 0.1) {
    const svg = frameSvg({ question: Q, theme: THEMES.hybrid, t });
    const hits = (svg.match(/Riva/g) || []).length;
    assert.ok(hits <= 1, `t=${t.toFixed(1)} istice odgovor (${hits} pojava)`);
    assert.doesNotMatch(svg, /class="correct"/, `t=${t.toFixed(1)} oznacava tacan odgovor`);
  }
});

test('poslije 8s tacan odgovor je oznacen', () => {
  assert.match(at(9), /class="correct"/);
});

test('sva cetiri odgovora su prisutna poslije 1s', () => {
  const svg = at(2);
  for (const a of Q.answers) assert.ok(svg.includes(a), `nedostaje odgovor: ${a}`);
});

test('nivo i kategorija dolaze iz pitanja, ne iz koda', () => {
  assert.match(at(11), /Intermediate/);
  assert.match(at(2), /MUZIKA/i);
});

test('tajmer je plav dok tece, crven kad istekne', () => {
  assert.ok(at(4).includes('#4456A6'), 'nema brend plave dok tajmer tece');
  assert.ok(at(9).includes('#FE6555'), 'nema brend crvene poslije isteka');
});

test('tekst sa ampersandom i apostrofom ne kvari SVG', () => {
  const q = { ...Q, text: "Ko peva „Jel' ti reka ko” & ko ne?", answers: ['A & B', "C'D", '<E>', 'F'] };
  const svg = frameSvg({ question: q, theme: THEMES.hybrid, t: 2 });
  assert.match(svg, /&amp;/);
  assert.match(svg, /&apos;/);
  assert.match(svg, /&lt;E&gt;/);
  assert.doesNotMatch(svg, /<E>/);
});

test('dijakritika prolazi neizmijenjena', () => {
  const q = { ...Q, answers: ['Šerifović', 'Đorđe', 'Čolić', 'Žera'] };
  const svg = frameSvg({ question: q, theme: THEMES.hybrid, t: 2 });
  for (const a of q.answers) assert.ok(svg.includes(a), `izgubljeno: ${a}`);
});

test('sve tri teme daju upotrebljiv SVG na svakoj fazi', () => {
  for (const theme of Object.values(THEMES)) {
    for (const t of [0, 2, 5, 9, 11]) {
      const svg = frameSvg({ question: Q, theme, t });
      assert.ok(svg.startsWith('<svg'), `${theme.name} pukao na t=${t}`);
      assert.ok(svg.length > 400, `${theme.name} sumnjivo kratak na t=${t}`);
    }
  }
});

test('dugacko pitanje se prelama, ne izlazi iz kadra', () => {
  const q = { ...Q, text: 'A'.repeat(20) + ' ' + 'B'.repeat(20) + ' ' + 'C'.repeat(20) + ' ' + 'D'.repeat(20) };
  const svg = frameSvg({ question: q, theme: THEMES.hybrid, t: 2 });
  assert.ok((svg.match(/<tspan/g) || []).length >= 3, 'pitanje nije prelomljeno u vise redova');
});
```

- [ ] **Step 2: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/frame.test.js`
Expected: FAIL — `Cannot find module '../src/frame.js'`

- [ ] **Step 3: Implementiraj src/frame.js**

```js
import { xmlEscape, wrapText } from './text.js';
import { categoryColor, BRAND } from './theme.js';
import { PHASES, phaseAt, timerProgress } from './timeline.js';

export const W = 1080;
export const H = 1920;

const QUESTION_WRAP = 22;
const TEXT_FONT = 'Poppins';
const BRAND_FONT = 'Chendolle';

const fadeIn = (t, start, dur = 0.4) => {
  if (t <= start) return 0;
  if (t >= start + dur) return 1;
  return (t - start) / dur;
};

function questionBlock(q, theme, t) {
  const lines = wrapText(q.text, QUESTION_WRAP);
  const size = lines.length > 4 ? 62 : 72;
  const lead = size * 1.22;
  const top = 620 - ((lines.length - 1) * lead) / 2;

  const tspans = lines
    .map((l, i) => `<tspan x="${W / 2}" y="${(top + i * lead).toFixed(1)}">${xmlEscape(l)}</tspan>`)
    .join('');

  return `<text text-anchor="middle" font-family="${TEXT_FONT}" font-weight="bold" font-size="${size}" fill="${theme.textColor}" opacity="${fadeIn(t, PHASES.questionIn).toFixed(3)}">${tspans}</text>`;
}

function answerCards(q, theme, t) {
  const revealed = phaseAt(t) === 'reveal' || phaseAt(t) === 'endcard';
  const cardH = 118;
  const gap = 22;
  const top = 1010;

  return q.answers
    .map((a, i) => {
      const y = top + i * (cardH + gap);
      const isCorrect = revealed && i === q.correctIndex;
      const fill = isCorrect ? theme.revealColor : theme.cardFill;
      const txt = isCorrect ? '#FFFFFF' : theme.cardText;
      const cls = isCorrect ? ' class="correct"' : '';
      const op = fadeIn(t, PHASES.answersIn + i * 0.12).toFixed(3);

      return `<g${cls} opacity="${op}">` +
        `<rect x="110" y="${y}" width="${W - 220}" height="${cardH}" rx="26" fill="${fill}"/>` +
        `<text x="${W / 2}" y="${y + cardH / 2 + 16}" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="600" font-size="46" fill="${txt}">${xmlEscape(a)}</text>` +
        `</g>`;
    })
    .join('');
}

function timerRing(theme, t) {
  const phase = phaseAt(t);
  if (phase === 'endcard') return '';

  const cx = W - 150;
  const cy = 250;
  const r = 62;
  const circ = 2 * Math.PI * r;
  const p = timerProgress(t);
  const expired = t >= PHASES.timerEnd;
  const color = expired ? theme.timerExpired : theme.timerRunning;
  const secs = Math.max(0, Math.ceil((PHASES.timerEnd - t)));

  return `<g>` +
    `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${theme.textColor}" stroke-opacity="0.25" stroke-width="10"/>` +
    `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="10" stroke-linecap="round" ` +
      `stroke-dasharray="${circ.toFixed(2)}" stroke-dashoffset="${(circ * (1 - p)).toFixed(2)}" ` +
      `transform="rotate(-90 ${cx} ${cy})"/>` +
    `<text x="${cx}" y="${cy + 18}" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="bold" font-size="52" fill="${color}">${secs}</text>` +
    `</g>`;
}

function categoryChip(q, theme, t) {
  if (!q.categoryName) return '';
  const color = categoryColor(q.categoryName);
  const label = q.categoryName.toUpperCase();
  const w = 90 + label.length * 26;

  return `<g opacity="${fadeIn(t, 0.2).toFixed(3)}">` +
    `<rect x="110" y="200" width="${w}" height="86" rx="43" fill="${color}"/>` +
    `<text x="${110 + w / 2}" y="257" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="bold" font-size="38" fill="#FFFFFF" letter-spacing="3">${xmlEscape(label)}</text>` +
    `</g>`;
}

function endCard(q, theme, t) {
  if (phaseAt(t) !== 'endcard') return '';
  const op = fadeIn(t, PHASES.endCardAt, 0.5).toFixed(3);
  const level = q.level ? xmlEscape(q.level) : '';

  return `<g opacity="${op}">` +
    `<rect x="0" y="1420" width="${W}" height="500" fill="${BRAND.navy}"/>` +
    `<text x="${W / 2}" y="1540" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="600" font-size="44" fill="#FFFFFF">Ovo je bio ${level}.</text>` +
    `<text x="${W / 2}" y="1616" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="bold" font-size="52" fill="${BRAND.yellow}">6.482 pitanja te ceka.</text>` +
    `<text x="${W / 2}" y="1750" text-anchor="middle" font-family="${BRAND_FONT}" font-size="76" fill="#FFFFFF">YUGOVOTE</text>` +
    `<text x="${W / 2}" y="1830" text-anchor="middle" font-family="${TEXT_FONT}" font-weight="600" font-size="40" fill="${BRAND.coral}">yugovote.com</text>` +
    `</g>`;
}

export function frameSvg({ question, theme, t }) {
  const bg = theme.usesVideoBg
    ? `<rect width="${W}" height="${H}" fill="${theme.panelColor}" fill-opacity="${theme.panelOpacity}"/>`
    : `<rect width="${W}" height="${H}" fill="${theme.bg}"/>`;

  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">` +
    bg +
    categoryChip(question, theme, t) +
    timerRing(theme, t) +
    questionBlock(question, theme, t) +
    answerCards(question, theme, t) +
    endCard(question, theme, t) +
    `</svg>`;
}
```

- [ ] **Step 4: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/frame.test.js`
Expected: PASS, svih 13 testova

- [ ] **Step 5: Commit**

```bash
git add scripts/ig-reel/src/frame.js scripts/ig-reel/test/frame.test.js
git commit -m "IG Reel: gradnja SVG frejma kao funkcija vremena"
```

---

### Task 6: Rasterizacija i sklapanje videa

**Files:**
- Create: `scripts/ig-reel/src/render.js`
- Create: `scripts/ig-reel/src/cli.js`
- Test: `scripts/ig-reel/test/render.test.js`

**Interfaces:**
- Consumes: `frameSvg`, `W`, `H` (Task 5); `THEMES` (Task 4); `fetchQuestion` (Task 2)
- Produces:
  - `ffmpegArgs({ theme, bgPath, framesGlob, outPath, fps, duration }) => string[]`
  - `renderFrames({ question, theme, fps, outDir }) => Promise<number>`
  - `renderReel({ question, theme, bgPath, outPath, fps }) => Promise<string>`

- [ ] **Step 1: Napiši test koji pada**

`test/render.test.js`:

```js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ffmpegArgs } from '../src/render.js';
import { THEMES } from '../src/theme.js';

const base = { framesGlob: 'frames/%04d.png', outPath: 'out/r.mp4', fps: 30, duration: 13 };

test('nikad ne koristi drawtext ni subtitles - ovaj ffmpeg ih nema', () => {
  for (const theme of Object.values(THEMES)) {
    const args = ffmpegArgs({ ...base, theme, bgPath: 'bg.mp4' }).join(' ');
    assert.doesNotMatch(args, /drawtext/);
    assert.doesNotMatch(args, /subtitles/);
    assert.doesNotMatch(args, /\bass\b/);
  }
});

test('teme sa videom citaju pozadinski fajl', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.hybrid, bgPath: 'assets/bg/muzika-01.mp4' });
  assert.ok(args.includes('assets/bg/muzika-01.mp4'));
});

test('light tema pravi jednobojnu pozadinu preko lavfi', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.light, bgPath: null }).join(' ');
  assert.match(args, /lavfi/);
  assert.match(args, /color=/);
});

test('izlaz je uvijek yuv420p i tacno trajanje', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.hybrid, bgPath: 'bg.mp4' });
  assert.ok(args.includes('yuv420p'));
  const i = args.indexOf('-t');
  assert.ok(i > -1 && args[i + 1] === '13');
});

test('sekvenca frejmova se ucitava sa zadatim fps', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.hybrid, bgPath: 'bg.mp4' });
  const i = args.indexOf('-framerate');
  assert.ok(i > -1 && args[i + 1] === '30');
  assert.ok(args.includes('frames/%04d.png'));
});

test('filtergraph skalira pozadinu na 1080x1920 pa preklapa', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.hybrid, bgPath: 'bg.mp4' }).join(' ');
  assert.match(args, /scale=1080:1920/);
  assert.match(args, /overlay/);
});

test('argumenti su niz stringova, bez shell interpolacije', () => {
  const args = ffmpegArgs({ ...base, theme: THEMES.hybrid, bgPath: 'bg.mp4' });
  assert.ok(Array.isArray(args));
  for (const a of args) assert.equal(typeof a, 'string');
});
```

- [ ] **Step 2: Pokreni test i potvrdi da pada**

Run: `cd scripts/ig-reel && node --test test/render.test.js`
Expected: FAIL — `Cannot find module '../src/render.js'`

- [ ] **Step 3: Implementiraj src/render.js**

```js
import { mkdir, writeFile, rm } from 'node:fs/promises';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { join } from 'node:path';
import { frameSvg, W, H } from './frame.js';
import { PHASES } from './timeline.js';

const run = promisify(execFile);
const FONTCONFIG = join(process.cwd(), 'fontconfig', 'fonts.conf');

export function ffmpegArgs({ theme, bgPath, framesGlob, outPath, fps, duration }) {
  const args = ['-y'];

  if (theme.usesVideoBg) {
    args.push('-stream_loop', '-1', '-i', bgPath);
  } else {
    args.push('-f', 'lavfi', '-i', `color=c=${theme.bg}:s=${W}x${H}:r=${fps}:d=${duration}`);
  }

  args.push('-framerate', String(fps), '-i', framesGlob);
  args.push(
    '-filter_complex',
    `[0:v]scale=${W}:${H}:force_original_aspect_ratio=increase,crop=${W}:${H},fps=${fps}[bg];[bg][1:v]overlay=0:0:format=auto[v]`
  );
  args.push('-map', '[v]');
  args.push('-c:v', 'libx264', '-preset', 'medium', '-crf', '19');
  args.push('-pix_fmt', 'yuv420p', '-r', String(fps), '-t', String(duration));
  args.push(outPath);

  return args;
}

export async function renderFrames({ question, theme, fps = 30, outDir }) {
  await rm(outDir, { recursive: true, force: true });
  await mkdir(outDir, { recursive: true });

  const total = Math.round(PHASES.total * fps);

  for (let f = 0; f < total; f++) {
    const t = f / fps;
    const svg = frameSvg({ question, theme, t });
    const svgPath = join(outDir, 'cur.svg');
    const pngPath = join(outDir, String(f).padStart(4, '0') + '.png');

    await writeFile(svgPath, svg, 'utf8');
    await run('rsvg-convert', ['-w', String(W), '-h', String(H), svgPath, '-o', pngPath], {
      env: { ...process.env, FONTCONFIG_FILE: FONTCONFIG },
    });
  }

  await rm(join(outDir, 'cur.svg'), { force: true });
  return total;
}

export async function renderReel({ question, theme, bgPath, outPath, fps = 30 }) {
  const framesDir = join('frames', theme.name);
  const count = await renderFrames({ question, theme, fps, outDir: framesDir });
  if (count === 0) throw new Error('nijedan frejm nije generisan');

  await mkdir('out', { recursive: true });
  const args = ffmpegArgs({
    theme,
    bgPath,
    framesGlob: join(framesDir, '%04d.png'),
    outPath,
    fps,
    duration: PHASES.total,
  });

  await run('ffmpeg', args, { maxBuffer: 32 * 1024 * 1024 });
  return outPath;
}
```

- [ ] **Step 4: Pokreni test i potvrdi da prolazi**

Run: `cd scripts/ig-reel && node --test test/render.test.js`
Expected: PASS, svih 7 testova

- [ ] **Step 5: Implementiraj src/cli.js**

```js
#!/usr/bin/env node
import { parseArgs } from 'node:util';
import { readFileSync } from 'node:fs';
import { fetchQuestion } from './api.js';
import { THEMES } from './theme.js';
import { renderReel } from './render.js';

function loadEnv(path) {
  const out = {};
  try {
    for (const line of readFileSync(path, 'utf8').split('\n')) {
      const m = line.match(/^\s*([A-Z_]+)\s*=\s*(.*)\s*$/);
      if (m) out[m[1]] = m[2].replace(/^["']|["']$/g, '');
    }
  } catch {
    // nema .env — oslanjamo se na process.env
  }
  return out;
}

const { values } = parseArgs({
  options: {
    question: { type: 'string' },
    theme: { type: 'string', default: 'hybrid' },
    bg: { type: 'string' },
    out: { type: 'string' },
  },
});

if (!values.question) {
  console.error('upotreba: node src/cli.js --question 44065 [--theme hybrid|light|cinematic] [--bg put/do/bg.mp4] [--out put/do/izlaz.mp4]');
  process.exit(1);
}

const theme = THEMES[values.theme];
if (!theme) {
  console.error(`nepoznata tema "${values.theme}". Dostupne: ${Object.keys(THEMES).join(', ')}`);
  process.exit(1);
}

if (theme.usesVideoBg && !values.bg) {
  console.error(`tema "${theme.name}" trazi pozadinski snimak — dodaj --bg`);
  process.exit(1);
}

const env = { ...loadEnv('../../yugovote-mcp/.env'), ...process.env };
const question = await fetchQuestion(Number(values.question), env);
const out = values.out ?? `out/${question.id}-${theme.name}.mp4`;

console.error(`pitanje ${question.id} | ${question.categoryName} | ${question.level}`);
console.error(`tema ${theme.name} -> ${out}`);

await renderReel({ question, theme, bgPath: values.bg ?? null, outPath: out });
console.log(out);
```

- [ ] **Step 6: Provjeri kraj-do-kraja sa svijetlom temom, koja ne treba pozadinu**

```bash
cd scripts/ig-reel
node src/cli.js --question 44065 --theme light
ffprobe -v error -show_entries stream=width,height,duration,pix_fmt -of default=nw=1 out/44065-light.mp4
```

Expected: `width=1080`, `height=1920`, `duration` oko `13`, `pix_fmt=yuv420p`

- [ ] **Step 7: Pogledaj tri kadra očima i potvrdi da je tekst ispravan**

```bash
cd scripts/ig-reel
for t in 2 6 11; do
  ffmpeg -v error -y -ss $t -i out/44065-light.mp4 -frames:v 1 out/kadar-$t.png
done
```

Otvori `out/kadar-2.png`, `out/kadar-6.png` i `out/kadar-11.png`. Potvrdi: dijakritika je ispravna, pitanje je prelomljeno unutar kadra, tajmer odbrojava, na 11. sekundi stoji `Intermediate` i `yugovote.com`, a **na 2. i 6. sekundi odgovor "Riva" nije istaknut**.

- [ ] **Step 8: Commit**

```bash
git add scripts/ig-reel/src/render.js scripts/ig-reel/src/cli.js scripts/ig-reel/test/render.test.js
git commit -m "IG Reel: rasterizacija frejmova i sklapanje videa"
```

---

### Task 7: Pozadinski snimci na Higgsfieldu

**Files:**
- Create: `scripts/ig-reel/assets/bg/README.md`
- Create: `scripts/ig-reel/assets/bg/muzika-01.mp4` (nije u gitu)

**Interfaces:**
- Consumes: ništa
- Produces: `assets/bg/muzika-01.mp4` — 1080×1920, najmanje 13 s, spori dolly-in

- [ ] **Step 1: Generiši keyframe preko Higgsfielda**

Alat `generate_image`, odnos stranica 9:16. Prompt:

```
A warm, grainy 1980s Yugoslav living room at night, shot on 35mm film. An old
wooden-cased CRT television sits in the corner, its screen glowing with abstract
stage lights and lens flares — no people, no faces, no text on the screen.
Patterned wallpaper, a woven rug, a lace doily on a side table, a glass display
cabinet. Dust floating in the projected light. Shallow depth of field, soft warm
tungsten glow, deep shadows. Vertical composition, cinematic, nostalgic, no text.
```

Bitno: `no people, no faces, no text` je namjerno i mora ostati. Spec zabranjuje da AI glumi arhivski snimak ili prikazuje prepoznatljive osobe.

- [ ] **Step 2: Animiraj u video**

Alat `generate_video`, ulaz je slika iz prethodnog koraka. Prompt pokreta:

```
Extremely slow, steady dolly-in toward the television screen. Almost imperceptible
motion. No cuts, no camera shake, no zoom bursts. Dust particles drift slowly.
```

Trajanje najmanje 8 s. Ako alat vrati `preset_recommendation`, ponovi poziv sa `declined_preset_id`.

- [ ] **Step 3: Skini rezultat i normalizuj na 1080×1920**

```bash
cd scripts/ig-reel
mkdir -p assets/bg
# preuzmi generisani snimak u assets/bg/muzika-raw.mp4, pa:
ffmpeg -y -i assets/bg/muzika-raw.mp4 \
  -vf "scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,fps=30" \
  -an -c:v libx264 -crf 18 -pix_fmt yuv420p assets/bg/muzika-01.mp4
ffprobe -v error -show_entries stream=width,height,duration -of default=nw=1 assets/bg/muzika-01.mp4
```

Expected: `width=1080`, `height=1920`

Snimak je kraći od 13 s, ali `ffmpegArgs` ga vrti u petlji preko `-stream_loop -1`, pa je to u redu.

- [ ] **Step 4: Napiši README da se pozadina može ponovo napraviti**

`assets/bg/README.md` mora sadržati: oba prompta iz koraka 1 i 2 doslovno, korišćeni model, i komandu za normalizaciju iz koraka 3. Fajlovi `.mp4` nisu u gitu, pa je ovaj README jedini trag kako su nastali.

- [ ] **Step 5: Commit**

```bash
git add scripts/ig-reel/assets/bg/README.md
git commit -m "IG Reel: pozadina za Muziku i uputstvo za regeneraciju"
```

---

### Task 8: Sve tri varijante i poređenje

**Files:**
- Create: `scripts/ig-reel/README.md`
- Create: `scripts/ig-reel/out/44065-caption.txt`

**Interfaces:**
- Consumes: `renderReel` (Task 6), `THEMES` (Task 4), `fetchQuestion` (Task 2)
- Produces: `out/44065-{hybrid,light,cinematic}.mp4`, `out/poredjenje.mp4`, `out/44065-caption.txt`

- [ ] **Step 1: Renderuj sve tri teme**

```bash
cd scripts/ig-reel
node src/cli.js --question 44065 --theme light
node src/cli.js --question 44065 --theme hybrid    --bg assets/bg/muzika-01.mp4
node src/cli.js --question 44065 --theme cinematic --bg assets/bg/muzika-01.mp4
ls -la out/*.mp4
```

Expected: tri `.mp4` fajla

- [ ] **Step 2: Sklopi ih jedan pored drugog za poređenje**

```bash
cd scripts/ig-reel
ffmpeg -y \
  -i out/44065-light.mp4 -i out/44065-hybrid.mp4 -i out/44065-cinematic.mp4 \
  -filter_complex "[0:v]scale=540:960[a];[1:v]scale=540:960[b];[2:v]scale=540:960[c];[a][b][c]hstack=inputs=3[v]" \
  -map "[v]" -c:v libx264 -crf 20 -pix_fmt yuv420p out/poredjenje.mp4
open out/poredjenje.mp4
```

Redoslijed slijeva nadesno: `light`, `hybrid`, `cinematic`.

- [ ] **Step 3: Provjeri da nijedna varijanta ne odaje odgovor prerano**

```bash
cd scripts/ig-reel
for th in light hybrid cinematic; do
  ffmpeg -v error -y -ss 6 -i out/44065-$th.mp4 -frames:v 1 out/provjera-$th.png
done
```

Otvori sva tri PNG-a. Ni na jednom "Riva" ne smije biti istaknuta drugačije od ostala tri odgovora. Ako jeste — to je greška u `frame.js`, ne u renderu.

- [ ] **Step 4: Napiši caption**

Ton mora zvučati kao sam sajt — topao, razgovoran i samoironičan, kako stoji u
uvodniku za Muziku: *"Ranije je bilo bolje ovo, ranije je bilo bolje ono. Naravno da
nije. Ali, ljudi — kako se ovde samo nekada rokalo!"*

Upiši u `out/44065-caption.txt`:

```text
Jedini put. 1989, Lozana.

Jugoslavija je pobedila na Evroviziji tačno jednom — i ti to ili znaš, ili se praviš da znaš. Nema srama, i mi smo morali da proverimo.

Odgovor je u snimku. Ne moraš da guglaš.

A ovo je bio tek Intermediate. Ima ih još 6.482, i neka su stvarno zla.
Link u bio.

#jugoslavija #exyu #nostalgija #evrovizija #kviz #yugovote #osamdesete #bivsajugoslavija #jugoslovenskamuzika
```

Provjeri prije objave: **odgovor se ne smije pojaviti ni u tekstu ni u hashtagovima.**
Riječ "Riva" ne postoji u fajlu iznad, i tako mora ostati.

Nivo `Intermediate` u captionu mora se poklopiti sa onim što vraća API za to pitanje.
Ako se pitanje promijeni, mijenja se i caption.

- [ ] **Step 5: Napiši README**

`scripts/ig-reel/README.md`:

````markdown
# YugoVote — Instagram kviz-Reel

Od ID-a pitanja iz YugoVote baze pravi vertikalni Reel (1080×1920, 13 s).

## Zahtjevi

- Node 22+
- `ffmpeg` — **bez** potrebe za `drawtext`; ovaj build ga i nema
- `rsvg-convert` (`brew install librsvg`)

## Kredencijali

Čitaju se iz `yugovote-mcp/.env` (`WP_BASE_URL`, `WP_USERNAME`, `WP_APP_PASSWORD`),
ili iz okruženja ako su tamo postavljeni.

## Pokretanje

```bash
cd scripts/ig-reel
node src/cli.js --question 44065 --theme light
node src/cli.js --question 44065 --theme hybrid    --bg assets/bg/muzika-01.mp4
node src/cli.js --question 44065 --theme cinematic --bg assets/bg/muzika-01.mp4
```

## Teme

| Tema | Pozadina | Za koga |
|---|---|---|
| `light` | bijela, bez videa | najbliže izgledu sajta |
| `hybrid` | AI video + brend elementi | zaustavlja skrol, a odaje brend odmah |
| `cinematic` | AI video, brend tek na kraju | najjači kao snimak, najsporiji za prepoznavanje |

## Fontovi

`Chendolle` je brend font sa sajta, ali **ima svega 157 znakova i nijedan naš
dijakritički** — nema Č, ć, Š, ž, Đ, đ ni navodnike „ ”. Zato se koristi isključivo
za wordmark „YUGOVOTE", koji ih ne sadrži. Sav ostali tekst ide u `Poppins`.

Test `test/font.test.js` čuva ovo pravilo i pašće ako se font zamijeni nekim koji
nema punu pokrivenost.

Fontovi se ne instaliraju u sistem — `fontconfig/fonts.conf` ih učitava iz repozitorija.

## Kako radi

Svaki frejm je čista funkcija vremena: `frameSvg({question, theme, t})` vraća SVG,
`rsvg-convert` ga pretvara u PNG sa alfom, ffmpeg preklopi cijelu sekvencu preko
pozadine. Nema animacije u ffmpeg-u — sva logika je u JavaScriptu, gdje se testira.
````

- [ ] **Step 6: Pokreni sve testove**

Run: `cd scripts/ig-reel && npm test`
Expected: PASS, svi testovi iz svih fajlova

- [ ] **Step 7: Commit**

```bash
git add scripts/ig-reel/README.md
git commit -m "IG Reel: tri varijante, caption i uputstvo"
```

---

## Šta ostaje izvan ovog plana

- **Zvuk.** Tik tajmera i zvuk isteka nisu dio ovog plana. Snimci izlaze nijemi. Na Instagramu se ionako najčešće dodaje zvuk iz aplikacije, pa je to svjesno odloženo dok se ne izabere tema.
- **Objava.** Ručna. Higgsfield ima TikTok integraciju, ali ne Instagram.
- **Ostale kategorije.** Pozadine za Sport, Film i TV, Culture Club, Biznis i Trendy/Lifestyle prave se po istom postupku iz Task 7 kad se izabere tema.
- **Popravka `yugovote-mcp` sheme.** Zaseban zadatak; ovaj plan ide direktno na REST API.
