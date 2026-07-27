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
reda ispod. Tajmer je tanak prsten koji se prazni, u narandžastoj — akcentnoj boji
sajta, da post i odredišna stranica izgledaju kao ista stvar.

Završna kartica: logo, `yugovote.com`, i stvarna brojka **6.482 pitanja te čeka**
(zbir po kategorijama: 701 + 1.704 + 1.301 + 976 + 1.250 + 550). Za nov nalog je
korisnije pokazati da iza stoji nešto veliko nego glumiti postojeću publiku.

## Proizvodnja

Ključna odluka: **tekst crta ffmpeg lokalno iz baze, ne generativni model.**

Dva razloga. Pitanja su puna dijakritike — Čolić, Šerifović, Đorđe, Dragojević — a
generativni modeli to prije ili kasnije zgužvaju; jedan pogrešan znak u imenu je
tačno ono što nostalgična publika kažnjava. I drugo, ako se tekst crta skriptom iz
baze, pedeseti post košta koliko i drugi: povučeš pitanje po ID-u, skripta ispljune
video.

| Sloj | Alat | Učestalost |
|---|---|---|
| Pozadinski video (soba, TV, spori dolly-in) | Higgsfield `generate_image` → `generate_video` | Jednom po kategoriji, reciklira se |
| Pitanje, odgovori, tajmer, završna kartica | ffmpeg, iz baze | Svaki post, automatski |
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
