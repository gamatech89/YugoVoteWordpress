# Instagram kviz-Reel — dizajn

Datum: 2026-07-27
Status: prihvaćen dizajn, čeka plan implementacije

## Cilj

Dovesti saobraćaj sa Instagrama na yugovote.com. Nalog je nov i prazan, pa prvi post
nije jednokratan komad nego prototip serije — šablon koji se ponavlja i koji publika
nauči da prepozna.

Mjera uspjeha: klikovi na link u bio-u. Doseg i komentari su sekundarni, korisni
utoliko što hrane doseg.

## Zašto video, a ne slike

Nov nalog nema pratilaca. Carousel i single-image se na Instagramu serviraju
prvenstveno postojećim pratiocima, pa sa nula pratilaca nemaju gdje da odu. Reels su
jedini format sa ozbiljnom distribucijom ka ljudima koji nalog ne prate.

Cijena tog izbora: Reels slabije konvertuju u klik, jer ljudi nerado izlaze iz feeda.
Zato hook mora ostaviti motiv koji se ne može zadovoljiti unutar Instagrama.

## Format: anatomija Reela (~13s)

| Vrijeme | Sadržaj | Svrha |
|---|---|---|
| 0–1s | Vizual i pitanje odmah na ekranu. Bez uvoda, bez logoa. | Zaustaviti skrol |
| 1–3s | Četiri ponuđena odgovora | Gledalac se mentalno obavezuje na jedan |
| 3–8s | Tajmer 5s, kamera se sporo kreće naprijed | Jedini razlog da gledalac ostane |
| 8–10s | Odgovor se otkriva | Nagrada; pokreće komentare |
| 10–13s | Završna kartica: nivo, yugovote.com, broj pitanja | CTA i social proof |

### Odluka: odgovor se otkriva

Razmatrano je da se odgovor nikad ne otkrije, čime bi petlja ostala otvorena i
tjerala klik. Odbačeno: za jedan post to radi, ali kroz seriju publika nauči da je
zezaju i prestane gledati do kraja, što obara watch-time — a watch-time nosi doseg.

Umjesto toga petlja se pomjera. Odgovor na pitanje se daje, ali završna kartica
otvara veće pitanje — "koliko sam ja dobar" — koje jedan Reel ne može zatvoriti.
Zato kartica imenuje nivo pitanja i upućuje na teže nivoe na sajtu.

### Pravila šablona

- **Bez logoa u prvoj sekundi.** Brendiranje na početku signalizira reklamu i ubija
  zadržavanje. Logo ide isključivo na završnu karticu.
- **Tajmer je nosač formata**, ne dekoracija. On je jedini razlog zašto neko ostane
  pet sekundi na snimku bez radnje.
- **Pitanje mora biti srednje težine.** Prelako ne daje povod za komentar, preteško
  tjera na skrol. Ciljna zona je da otprilike pola gledalaca zna odgovor.

## Sadržaj prvog posta

Pitanje iz baze, ID **44065**:

> Koja je grupa odnela jedinu pobedu za Jugoslaviju na Evroviziji 1989. godine?
> Pepel in kri / **Riva** / Novi Fosili / Srebrna krila

Kategorija Muzika (ID 25), nivo Intermediate (ID 688).

Izabrano iz 6.482 pitanja jer:

- Jedino je po definiciji pan-jugoslovensko — pobjeda je bila za Jugoslaviju, ne za
  jednu republiku. Prvi post platforme koja pokriva svih šest zemalja ne smije
  zvučati kao da pripada jednoj.
- Distraktori rade. Novi Fosili i Srebrna krila su prve asocijacije, Pepel in kri
  pokriva slovenačku stranu. Pitanje nije poklon, ali nije ni nemoguće.
- Nosi emociju bez politike. Godina je 1989; publika sama dodaje kontekst.
- Sam izaziva komentare — ljudi upisuju "Rock Me" čim vide odgovor.

Tačnost provjerena: Riva, iz Zadra, pobijedila 1989. u Lozani pjesmom "Rock Me";
jedina pobjeda Jugoslavije na Evroviziji. Poklapa se sa bazom.

## Vizuelni šablon

AI generiše isključivo ambijent. Ne generiše prepoznatljiva lica, ne rekonstruiše
stvarne događaje, i ni u jednom kadru se ne pretvara da je arhivski snimak.
Publika za ex-Yu nostalgiju kažnjava netačnost, a "skoro tačno" lice poznate osobe
je gore nego nikakvo.

Scena: stari televizor u dnevnoj sobi s kraja osamdesetih. Kamera vrlo sporo prilazi
ekranu. Na ekranu samo scenska svjetla i reflektori, apstraktno, bez ljudi. Nostalgiju
nosi soba — tapete, tepih, čipkani stolnjak, staklena vitrina, prašina u snopu
svjetla. Topla, zrnasta slika, VHS karakter.

Ovo rješava tri stvari odjednom: ambijent je univerzalan pa ga svako prepoznaje iz
svoje kuće; ništa ne glumi stvarni snimak pa nema šta da se ne poklopi; tamni ekran
televizora je prirodno mjesto za tekst, pa overlay ne izgleda nalijepljeno.

Tipografija: pitanje bijelo, teško, centrirano preko ekrana TV-a; odgovori u četiri
reda ispod.

### Boje

Brend nose dvije boje iz logotipa — **plava `#4457a5`** i **crvena `#e65552`**.
One su primarne u svakom postu i drže nalog prepoznatljivim kroz sve kategorije.

Koriste se semantički, ne dekorativno:

- **Plava** — tajmer dok teče, i sve dok je pitanje otvoreno
- **Crvena** — trenutak isteka i otkrivanja odgovora
- **Boja kategorije** — samo kao tanka oznaka kategorije u uglu i akcenat na
  završnoj kartici

Tako brend boje nose radnju umjesto da stoje sa strane, a gledalac uči da crveno
znači "gotovo je" prije nego pročita ijednu riječ.

Boje kategorija preuzete su sa sajta (CSS varijabla `--cat-color`), ne izmišljene:

| Kategorija | Boja |
|---|---|
| Sport | `#36c43f` |
| Biznis | `#4457a5` |
| Muzika | `#b0b9dd` |
| Film i TV | `#e65552` |
| Culture Club | `#e9e33a` |
| Trendy / Lifestyle | `#f599e9` |

Napomena: Biznis i Film i TV koriste iste vrijednosti kao brend plava i crvena. Za
te dvije kategorije oznaka kategorije se stapa sa brendom, što nije greška ali
znači da oznaka tu ne nosi dodatnu informaciju.

Završna kartica: logo, `yugovote.com`, i stvarna brojka **6.482 pitanja te čeka**
(zbir po kategorijama: 701 + 1.704 + 1.301 + 976 + 1.250 + 550). Za nov nalog je
korisnije pokazati da iza stoji nešto veliko nego glumiti postojeću publiku.

## Proizvodnja

Ključna odluka: **tekst se crta lokalno iz baze, ne generativnim modelom.**

Dva razloga. Pitanja su puna dijakritike — Čolić, Šerifović, Đorđe, Dragojević — a
generativni modeli to prije ili kasnije zgužvaju; jedan pogrešan znak u imenu je
tačno ono što nostalgična publika kažnjava. I drugo, ako se tekst crta skriptom iz
baze, pedeseti post košta koliko i drugi: povučeš pitanje po ID-u, skripta ispljune
video.

Lanac je **SVG → `rsvg-convert` → PNG sa alfom → ffmpeg `overlay`**.

Razlog zašto ne ffmpeg `drawtext`, kako je prvobitno zamišljeno: lokalni ffmpeg
(Homebrew 8.1.2_1) build je bez `libfreetype`, pa filtera `drawtext` jednostavno
nema, a nema ni `libass`. Provjereno na `configuration:` liniji builda.

Ispalo je da je SVG ionako bolji izbor: nosi prelom teksta u više redova, prsten
tajmera i završnu karticu — dakle sve što `drawtext` ne bi mogao ni da je bio tu.
Provjereno je i da `rsvg-convert` ispravno iscrtava našu dijakritiku i navodnike
(`Čolić, Šerifović, Đorđe, žćčđš, „ ”`) i da daje PNG sa alfa kanalom.

| Sloj | Alat | Učestalost |
|---|---|---|
| Pozadinski video (soba, TV, spori dolly-in) | Higgsfield `generate_image` → `generate_video` | Jednom po kategoriji, reciklira se |
| Pitanje, odgovori, tajmer, završna kartica | SVG → `rsvg-convert` → ffmpeg `overlay` | Svaki post, automatski |
| Zvuk (tik tajmera, buzz) | lokalni SFX | Jednom, reciklira se |

Higgsfield se troši praktično samo na početku. Za svaku kategoriju se pravi 5–6
varijanti pozadine koje se rotiraju, da nalog ne izgleda kao pokvarena ploča, dok
šablon ostaje prepoznatljiv.

Ambijent po kategorijama: Muzika — dnevna soba s televizorom; Sport — kafana ili
tribina; Film i TV — bioskopska sala. Culture Club, Biznis i Trendy/Lifestyle se
određuju kad na njih dođe red.

Prije objave gotov snimak može proći kroz Higgsfield `virality_predictor`. Koristi se
za kalibraciju hooka, ne kao presuda.

## Caption i hashtagovi

Nacrt:

> 1989, Lozana. Jedini put da je Jugoslavija pobijedila na Evroviziji.
> Znaš li ko? Odgovor je u snimku.
> A ovo je bio tek Intermediate — 6.482 pitanja te čeka. Link u bio.

Hashtagovi ostaju široki: `#jugoslavija #exyu #nostalgija #evrovizija #kviz`.

**Odgovor ne smije biti u hashtagovima.** `#riva` bi pokvario post prije nego ga iko
pogleda. Isto pravilo važi za svaki naredni post u seriji.

## Granice

- Objava na Instagram je ručna. Higgsfield ima TikTok integraciju, ali ne Instagram.
  Automatizacija objave preko Meta API-ja je zaseban posao i nije dio ovog dizajna.
- Nivo u završnoj kartici i u captionu mora se povlačiti iz baze, ne upisivati ručno.
  Pitanje 44065 je Intermediate; pogrešno naveden nivo potkopava upravo onu petlju
  ("koliko sam ja dobar") na kojoj cijeli format počiva.

## Poznat problem izvan opsega

MCP server `yugovote-mcp` ima grešku: `server.tool()` je pozvan sa običnim
JSON-schema objektom tamo gdje SDK očekuje Zod shape, pa je SDK cijeli objekat
protumačio kao `annotations`. Posljedica — svi alati imaju prazan `inputSchema`,
parametri se ignorišu (`search` i `per_page` nemaju efekta), a `claude mcp list`
javlja `tools fetch failed`.

Za ovaj dizajn to nije blokada jer se pitanja povlače direktno sa REST API-ja, koji
radi ispravno. Popravka je zaseban zadatak.
