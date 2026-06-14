<?php
/**
 * Template Name: Zaštita Privatnosti
 * Description: Politika privatnosti yugovote.com
 */

if (!defined('ABSPATH'))
    exit;

get_header();
?>

<main class="ygv-page ygv-page--legal">

    <!-- ========== HERO ========== -->
    <section class="ygv-page-hero ygv-page-hero--legal">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Pravni dokumenti
            </div>
            <h1 class="ygv-page-hero__title">Zaštita privatnosti</h1>
            <p class="ygv-page-hero__desc">
                Vaša privatnost nam je važna. Ovde objašnjavamo koje podatke prikupljamo i kako ih koristimo.
            </p>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#f8fafc"/>
            </svg>
        </div>
    </section>

    <div class="ygv-container ygv-legal-body">

        <div class="ygv-legal-updated">
            Poslednje ažuriranje: <?php echo date('d. m. Y.', strtotime('2025-01-01')); ?>
        </div>

        <div class="ygv-legal-layout">

            <!-- TOC -->
            <aside class="ygv-legal-toc">
                <div class="ygv-legal-toc__inner">
                    <p class="ygv-legal-toc__title">Sadržaj</p>
                    <ol class="ygv-legal-toc__list">
                        <li><a href="#uvod">Uvod</a></li>
                        <li><a href="#podaci">Koje podatke prikupljamo</a></li>
                        <li><a href="#svrha">Svrha obrade podataka</a></li>
                        <li><a href="#trece-strane">Treće strane</a></li>
                        <li><a href="#kolacici">Kolačići</a></li>
                        <li><a href="#cuvanje">Čuvanje podataka</a></li>
                        <li><a href="#prava">Vaša prava</a></li>
                        <li><a href="#izmene">Izmene politike</a></li>
                        <li><a href="#kontakt">Kontakt</a></li>
                    </ol>
                </div>
            </aside>

            <!-- Content -->
            <article class="ygv-legal-content">

                <section class="ygv-legal-section" id="uvod">
                    <h2>1. Uvod</h2>
                    <p>yugovote.com (u daljem tekstu: „Sajt", „mi" ili „naš") posvećen je zaštiti privatnosti svojih korisnika. Ova Politika privatnosti opisuje koje lične podatke prikupljamo, na koji način ih koristimo, s kim ih delimo i koja su vaša prava u vezi sa njima.</p>
                    <p>Obrada podataka vrši se u skladu sa Opštom uredbom o zaštiti podataka (GDPR) i važećim lokalnim propisima o zaštiti podataka o ličnosti.</p>
                    <p>Za sva pitanja u vezi sa zaštitom privatnosti možete nas kontaktirati na: <a href="mailto:office@yugovote.com">office@yugovote.com</a></p>
                </section>

                <section class="ygv-legal-section" id="podaci">
                    <h2>2. Koje podatke prikupljamo</h2>

                    <h3>Podaci koje nam direktno dajete</h3>
                    <ul>
                        <li><strong>Registracija naloga:</strong> korisničko ime, e-mail adresa, lozinka (čuvana u hashovanom obliku).</li>
                        <li><strong>Podaci profila:</strong> avatar, datum rođenja, zemlja (opcionalno, pri popunjavanju profila).</li>
                    </ul>

                    <h3>Podaci koje prikupljamo automatski</h3>
                    <ul>
                        <li><strong>Podaci o glasanju:</strong> koje liste, stavke i ankete ste glasali, kada i koji glas ste dali.</li>
                        <li><strong>Podaci o kvizovima:</strong> rezultati kvizova, tačnost odgovora i poeni.</li>
                        <li><strong>Tehnički podaci:</strong> IP adresa, tip pregledača, operativni sistem, stranice koje ste posetili i vreme posete.</li>
                        <li><strong>Kolačići:</strong> vidite odeljak 5.</li>
                    </ul>

                    <h3>Podaci iz društvenih mreža (ukoliko koristite prijavu)</h3>
                    <ul>
                        <li><strong>Google prijava:</strong> ime, e-mail adresa i profilna slika iz vašeg Google naloga.</li>
                        <li><strong>Facebook prijava:</strong> ime, e-mail adresa i profilna slika iz vašeg Facebook naloga.</li>
                    </ul>
                    <p>Ne prikupljamo lozinke društvenih mreža niti objavljujemo ništa u vaše ime.</p>
                </section>

                <section class="ygv-legal-section" id="svrha">
                    <h2>3. Svrha obrade podataka</h2>
                    <p>Vaše podatke koristimo isključivo za sledeće svrhe:</p>
                    <ul>
                        <li><strong>Pružanje usluge</strong> — upravljanje vašim nalogom, evidentiranje glasova, izračunavanje rangova i prikazivanje personalizovanih rezultata.</li>
                        <li><strong>Integritet glasanja</strong> — detekcija i sprečavanje zloupotrebe glasanja (botovi, višestruko glasanje, koordinisane akcije).</li>
                        <li><strong>Bezbednost</strong> — zaštita Sajta i korisnika od neovlašćenog pristupa.</li>
                        <li><strong>Analitika</strong> — razumevanje načina korišćenja Sajta radi poboljšanja korisničkog iskustva (putem Google Analytics).</li>
                        <li><strong>Komunikacija</strong> — slanje bitnih obaveštenja o nalogu ili izmenama uslova (ne šaljemo marketinški spam).</li>
                    </ul>
                </section>

                <section class="ygv-legal-section" id="trece-strane">
                    <h2>4. Treće strane</h2>
                    <p>Sarađujemo sa sledećim trećim stranama kojima mogu biti dostupni određeni podaci:</p>

                    <div class="ygv-legal-third-party">
                        <div class="ygv-legal-third-party__item">
                            <div class="ygv-legal-third-party__logo">G</div>
                            <div>
                                <strong>Google Analytics</strong>
                                <p>Koristimo Google Analytics za analitiku poseta. Google može prikupljati podatke o vašoj aktivnosti na Sajtu putem kolačića. Više informacija: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a></p>
                            </div>
                        </div>
                        <div class="ygv-legal-third-party__item">
                            <div class="ygv-legal-third-party__logo" style="background:#4267B2">f</div>
                            <div>
                                <strong>Meta (Facebook) Pixel i prijava</strong>
                                <p>Koristimo Meta Pixel za analitiku i eventualno oglašavanje. Opcija prijave putem Facebook naloga omogućava lakšu registraciju. Više: <a href="https://www.facebook.com/privacy/policy/" target="_blank" rel="noopener">facebook.com/privacy/policy</a></p>
                            </div>
                        </div>
                        <div class="ygv-legal-third-party__item">
                            <div class="ygv-legal-third-party__logo">G</div>
                            <div>
                                <strong>Google prijava (OAuth)</strong>
                                <p>Opcija prijave putem Google naloga. Koristi se isključivo za autentifikaciju. Google prima informaciju da ste posetili naš Sajt u svrhu prijave. Više: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a></p>
                            </div>
                        </div>
                    </div>

                    <p>Ne prodajemo vaše podatke trećim stranama niti ih koristimo u komercijalne svrhe izvan navedenog.</p>
                </section>

                <section class="ygv-legal-section" id="kolacici">
                    <h2>5. Kolačići</h2>
                    <p>Sajt koristi kolačiće (cookies) — male tekstualne fajlove koji se čuvaju u vašem pregledaču. Koristimo:</p>
                    <ul>
                        <li><strong>Neophodni kolačići</strong> — za funkcionisanje sajta (sesija, prijava, sigurnost). Ovi kolačići ne mogu se isključiti.</li>
                        <li><strong>Analitički kolačići</strong> — Google Analytics, za razumevanje poseta. Možete ih odbiti.</li>
                        <li><strong>Marketinški kolačići</strong> — Meta Pixel. Možete ih odbiti.</li>
                    </ul>
                    <p>Prikazujemo baner za upravljanje kolačićima pri prvoj poseti. Možete izmeniti svoje preferencije u bilo kom trenutku klikom na link za upravljanje kolačićima u podnožju sajta.</p>
                </section>

                <section class="ygv-legal-section" id="cuvanje">
                    <h2>6. Čuvanje podataka</h2>
                    <p>Vaše podatke čuvamo onoliko dugo koliko je neophodno za pružanje usluge:</p>
                    <ul>
                        <li>Podaci naloga čuvaju se dok ne zatražite brisanje ili dok ne obrišemo vaš nalog.</li>
                        <li>Podaci o glasanju i kvizovima čuvaju se trajno u anonimiziranom agregiranom obliku radi integriteta rangova.</li>
                        <li>Tehnički logovi (IP adrese, vreme pristupa) brišu se nakon 90 dana.</li>
                    </ul>
                </section>

                <section class="ygv-legal-section" id="prava">
                    <h2>7. Vaša prava</h2>
                    <p>U skladu sa GDPR-om imate sledeća prava:</p>
                    <ul>
                        <li><strong>Pravo na pristup</strong> — možete zatražiti kopiju podataka koje čuvamo o vama.</li>
                        <li><strong>Pravo na ispravku</strong> — možete zahtevati ispravku netačnih podataka.</li>
                        <li><strong>Pravo na brisanje</strong> — možete zahtevati brisanje vašeg naloga i ličnih podataka.</li>
                        <li><strong>Pravo na prenosivost</strong> — možete zatražiti export vaših podataka u mašinski čitljivom formatu.</li>
                        <li><strong>Pravo na prigovor</strong> — možete se usprotiviti određenim vrstama obrade podataka.</li>
                        <li><strong>Povlačenje pristanka</strong> — za obradu zasnovanu na pristanku (npr. marketinški kolačići), pristanak možete povući u bilo kom trenutku.</li>
                    </ul>
                    <p>Za ostvarivanje navedenih prava pišite nam na <a href="mailto:office@yugovote.com">office@yugovote.com</a>. Odgovorićemo u roku od 30 dana.</p>
                    <div class="ygv-legal-notice">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Napomena: brisanje naloga ne briše anonimizovane glasove koji doprinose integritetu rangova na platformi.
                    </div>
                </section>

                <section class="ygv-legal-section" id="izmene">
                    <h2>8. Izmene politike</h2>
                    <p>Ovu Politiku privatnosti možemo povremeno ažurirati. O bitnim izmenama obavestićemo vas putem e-pošte ili obaveštenjem na Sajtu. Datum poslednjeg ažuriranja uvek je vidljiv na vrhu ove stranice.</p>
                    <p>Preporučujemo da povremeno proverite ovu stranicu.</p>
                </section>

                <section class="ygv-legal-section" id="kontakt">
                    <h2>9. Kontakt</h2>
                    <p>Za sva pitanja u vezi sa zaštitom privatnosti i obradom vaših podataka:</p>
                    <div class="ygv-legal-contact">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <a href="mailto:office@yugovote.com">office@yugovote.com</a>
                    </div>
                </section>

            </article>

        </div>

    </div>

</main>

<?php get_footer(); ?>
