# YugoVote: stil SEO naslova i opisa

Ovaj vodič važi za sve ručno pisane naslove i opise (`meta-etapa1.json`). Po njemu pišu Claude i
pomoćni agenti, a urednik ga može koristiti kad sam popunjava "SEO (Google)" kutiju.

## Šta pišemo

- **Naslov** (`title`): ide u Google rezultat i na share karticu. Kod sam dodaje " | YugoVote", pa se to ne piše.
- **Opis** (`desc`): dve-tri rečenice ispod naslova na Google-u i na share kartici.

## Tvrda pravila (proverava `validate-meta.py`)

- Opis ima **120–155 znakova**, a naslov **najviše 50**. Duža imena lista smeju do 60, ali samo ako je
  originalni naziv dug.
- Latinica i ekavica. Vlastita imena i nazivi ostaju kakvi jesu, i kad su ijekavski: "Šta bi dao da si na
  mom mjestu", "Ljubav na posljednji pogled".
- Bez emodžija, bez reči napisanih VELIKIM SLOVIMA i bez sufiksa "YugoVote" u naslovu.
- Zabranjene fraze (AI klišei): otkrijte/otkrij, fascinantno, zaronite, ultimativno, neverovatan svet,
  svet čuda, putovanje kroz, nezaboravno, očekuje vas, čeka vas.
- Opis ne sme biti prepisan početak uvoda liste, a dva opisa ne smeju biti ista.

## Glas

YugoVote zvuči kao društvo za kafanskim stolom koje se svađa ko je bolji: duhovito, lokalno, malo
bezobrazno, ali **tačno**. Kratke rečenice. Retoričko pitanje radi. Ton uzimamo iz uvoda liste, a ne
prepisujemo ga.

**Liste:**
- Pomeni 2–4 poznata imena sa liste, iz `top` polja. Ne tvrdi ko je prvi, jer se redosled menja sa
  glasovima. Zato "Savićević, Mijatović, Jovetić…", a ne "Savićević je broj 1".
- Završi pozivom: "Glasaj!", "Glasaj.", "Složi svoju listu", "Reci ko je bolji", "Ti biraš".
- Naslov: naziv liste, plus ključna reč koju ljudi kucaju ("rang lista", "najbolji…") ako je ima mesta i
  ako je nema u nazivu. Ne izmišljaj što lista nema. Igre reči iz naziva ("Sjedi mali…") objasni:
  "kultni sportski komentatori".
- Ne preteruj sa brojevima. Broj stavki pomeni samo kad je upečatljiv ("100 najboljih…", "više od 100 ličnosti").

**Kvizovi:**
- Naslov: "Kviz: …" ili "Kviz o …", ili prirodna varijanta kad "kviz" već stoji u nazivu.
- Opis: tema (iz `intro` i `categories`) + težina (`difficulty`: Beginner = za početnike/lak,
  Intermediate = srednji, Expert = ekspertski) + broj pitanja (`num_questions`) + izazov.

**Ankete:** pitanje + 2–4 ponuđena odgovora + "Glasaj u anketi". Naslov: "Anketa: …".

**Showdown:** "dvoboj", "jedan na jedan", "dok ne ostane samo jedan" + 3–4 imena.

**Kategorije:** šta ima u kategoriji (liste/kvizovi o čemu) + poziv. Ton uzmi iz opisa kategorije.

**VIP osobe:** ime + po čemu je poznat + "pogledaj kako je složio svoje rang liste i glasaj".

## Uzorci (odobrio Claude; korisnik ih pregleda pre importa)

| Tip | Naslov | Opis |
|---|---|---|
| lista 2568 | Najbolji crnogorski fudbaleri: rang lista | Savićević, Mijatović, Jovetić, Vučinić… Cela Crna Gora, ni pola Beograda, a toliko majstora. Ko je najbolji crnogorski fudbaler? Glasaj! |
| lista 2485 | 100 najboljih srpskih fudbalera | Piksi, Džajić, Šekularac, Vidić ili Tadić? Stare legende protiv novih generacija, Zvezda protiv Partizana. Složi svoju listu i glasaj. |
| lista 3114 | Najbolji albumi Bijelog Dugmeta: rang lista | Tri pevača, bezbroj hitova i skandala. Bitanga i princeza, Eto! Baš hoću! ili Doživjeti stotu? Poređaj albume Bijelog Dugmeta i glasaj. |
| lista 3563 | Najbolji tržni centri u Srbiji | Hramovi potrošačkog društva: Galerija, Ušće, Delta City, Big Fashion… Gde najradije ostavljaš pare? Glasaj za najbolji tržni centar u Srbiji. |
| lista 5066 | Fudbaleri Zvezde rođeni u Beogradu | Rođen u Beogradu, igrao za Zvezdu, dao gol Partizanu. Pižon, Stanković, Pantelić, Bleki… Ko je najveći Zvezdin Beograđanin? Glasaj! |
| lista 11793 | Filmovi crnog talasa: rang lista | Skupljači perja, Buđenje pacova, Zaseda, Kad budem mrtav i beo… Zabranjivani, sporni i genijalni. Koji je najbolji film crnog talasa? Glasaj. |
| lista 22879 | Bosanske folk pevačice: rang lista | Silvana Armenulić, Hanka Paldum, Lepa Brena, Šemsa Suljaković… Pevačice raznih generacija i pesme za sva vremena. Ko je najbolja? Glasaj! |
| lista 27054 | Najbolji domaći album (1960–2026) | Kost u grlu, Bitanga i princeza, Malinkonija ili Odbrana i poslednji dani? Najbolji album poslednjih 70 godina, bez obzira na žanr. Glasaj. |
| lista 30216 | The greatest of all Serbs: najveći od 1945. | Đoković, patrijarh Pavle, Ivo Andrić, Paja Vuisić, Đinđić… Ko je najveći Srbin od 1945. naovamo? Više od 100 ličnosti, a ti biraš. Glasaj! |
| lista 35215 | Sjedi mali (2): kultni sportski komentatori | Mirko Kamenjašević, Milojko Pantić, Vladanko Stojaković… Komentatori čije se rečenice prepričavaju decenijama. Ko je najveća legenda? Glasaj. |
| kviz 40075 | Kviz opšte kulture: Ko ovo ne zna… | Najlakši kviz opšte kulture: 10 pitanja za početnike. Ako ovde pogrešiš, nemamo o čemu da pričamo. Igraj i proveri se za dva minuta. |
| kviz 43768 | Kviz: srpska proizvodna preduzeća | Ekspertski kviz o domaćim proizvođačima koji pokreću srpsku privredu. 10 pitanja o fabrikama i brendovima. Koliko znaš o tome šta pravimo? |
| kviz 38762 | Kviz: Od svega po malo (ekspert) | Istorija umetnosti, književnost, pozorište i još po malo svega. Ekspertski kviz opšte kulture od 10 pitanja, samo za one koji stvarno čitaju. |
| anketa 38344 | Anketa: svetski prvak u fudbalu 2026 | Francuska, Španija, Argentina, Brazil ili možda Hrvatska? Ko će biti svetski prvak u fudbalu 2026? Mi pijemo pivo u hladovini, a ti glasaj. |
| anketa 47730 | Anketa: najbolji roman Vedrane Rudan | Ljubav na posljednji pogled, Dabogda te majka rodila ili Crnci u Firenci? Koji roman Vedrane Rudan je najbolji? Glasaj u anketi. |
| showdown 38581 | Najbolje pivo: showdown dvoboj | Jelen ili Lav? Nikšićko ili Sarajevsko? Zaječarsko, Laško, Skopsko… Pivo protiv piva, dvoboj po dvoboj, dok ne ostane samo jedno. Glasaj. |
| VIP 37605 | Novak Đoković: njegove rang liste | Najbolji teniser svih vremena složio je svoje rang liste: vesele pesme, dečje pesme i još. Pogledaj kako je Novak poređao favorite i glasaj. |
| stranica 33363 | Ankete: reci svoje | Sve YugoVote ankete na jednom mestu: fudbal, muzika, knjige, more i sve između. Jedan klik, jedan glas, a rezultate vidiš odmah. Reci svoje. |
| kategorija 64 | Film i TV: rang liste filmova i serija | Brando ili Paja Vuisić? Kum ili Otac na službenom putu? Rang liste domaćih filmova, serija i glumaca, a redosled određuješ ti. Glasaj! |
| kviz kat. 215 | Sportski kvizovi | Sportski kvizovi o fudbalu, košarci i velikim šampionima sa ovih prostora. Od lakih do ekspertskih, po 10 pitanja. Proveri koliko znaš. |
