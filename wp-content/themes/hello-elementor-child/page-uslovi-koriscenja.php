<?php
/**
 * Template Name: Uslovi Korišćenja
 * Description: Stranica uslova korišćenja yugovote.com
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
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Pravni dokumenti
            </div>
            <h1 class="ygv-page-hero__title">Uslovi korišćenja</h1>
            <p class="ygv-page-hero__desc">
                Korišćenjem yugovote.com prihvatate sledeće uslove. Molimo vas da ih pažljivo pročitate.
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
                        <li><a href="#prihvatanje">Prihvatanje uslova</a></li>
                        <li><a href="#usluge">Opis usluge</a></li>
                        <li><a href="#nalozi">Korisnički nalozi</a></li>
                        <li><a href="#ponasanje">Pravila ponašanja</a></li>
                        <li><a href="#sadrzaj">Korisnički sadržaj</a></li>
                        <li><a href="#ip">Intelektualna svojina</a></li>
                        <li><a href="#odgovornost">Ograničenje odgovornosti</a></li>
                        <li><a href="#izmene">Izmene uslova</a></li>
                        <li><a href="#kontakt">Kontakt</a></li>
                    </ol>
                </div>
            </aside>

            <!-- Content -->
            <article class="ygv-legal-content">

                <section class="ygv-legal-section" id="prihvatanje">
                    <h2>1. Prihvatanje uslova</h2>
                    <p>Korišćenjem web sajta yugovote.com (u daljem tekstu: „Sajt") izjavljujete da ste pročitali, razumeli i prihvatili ove Uslove korišćenja. Ukoliko se ne slažete sa bilo kojim delom ovih uslova, molimo vas da ne koristite Sajt.</p>
                    <p>Ovi uslovi se primenjuju na sve posetioce, registrovane korisnike i sve ostale koji pristupaju Sajtu ili ga koriste.</p>
                </section>

                <section class="ygv-legal-section" id="usluge">
                    <h2>2. Opis usluge</h2>
                    <p>yugovote.com je interaktivna glasačka platforma obrazovnog, zabavnog i humanitarnog karaktera. Platforma omogućava korisnicima da:</p>
                    <ul>
                        <li>glasaju na listama i rangiranjima iz oblasti kulture, sporta, istorije i zabave regiona bivše Jugoslavije;</li>
                        <li>učestvuju u kvizovima znanja;</li>
                        <li>učestvuju u dvostrukobirajućim poređenjima (dvoboji);</li>
                        <li>glasaju na anketama o aktuelnim temama;</li>
                        <li>pregledaju rezultate i rangove zasnovane na glasovima zajednice.</li>
                    </ul>
                    <p>Sajt zadržava pravo da izmeni, suspenduje ili ukine bilo koji deo usluge u bilo kom trenutku, bez prethodnog obaveštenja.</p>
                </section>

                <section class="ygv-legal-section" id="nalozi">
                    <h2>3. Korisnički nalozi</h2>
                    <p>Određene funkcije Sajta dostupne su isključivo registrovanim korisnicima. Registracijom se obavezujete da:</p>
                    <ul>
                        <li>navedete tačne i potpune podatke;</li>
                        <li>čuvate poverljivost vaše lozinke i naloga;</li>
                        <li>odmah nas obavestite o bilo kakvoj neovlašćenoj upotrebi vašeg naloga;</li>
                        <li>ne kreirate više od jednog naloga u svrhu manipulacije glasanjem.</li>
                    </ul>
                    <p>yugovote.com zadržava pravo da suspenduje ili trajno obriše nalog koji krši ove uslove, bez prethodnog upozorenja.</p>
                </section>

                <section class="ygv-legal-section" id="ponasanje">
                    <h2>4. Pravila ponašanja</h2>
                    <p>Korisnici se obavezuju da neće:</p>
                    <ul>
                        <li>koristiti automatizovane skripte, botove ili bilo koji drugi mehanizam za manipulaciju glasovima;</li>
                        <li>organizovati koordinisane akcije glasanja putem društvenih mreža ili drugih kanala u svrhu narušavanja integriteta rezultata;</li>
                        <li>objavljivati sadržaj koji je uvredljiv, preteći, diskriminatorski, seksualan ili na drugi način neprikladan;</li>
                        <li>kršiti prava trećih lica, uključujući prava intelektualne svojine;</li>
                        <li>pokušavati neovlašćeno pristupiti sistemu ili podacima Sajta.</li>
                    </ul>
                    <p>Sajt koristi algoritamske mehanizme za detekciju i eliminaciju zlonamernog glasanja. Rezultati koji su posledica zloupotrebe mogu biti korigovani bez obaveštenja.</p>
                </section>

                <section class="ygv-legal-section" id="sadrzaj">
                    <h2>5. Korisnički sadržaj</h2>
                    <p>Sve što objavite na Sajtu (komentari, mišljenja, avatari i sl.) ostaje vaša intelektualna svojina. Međutim, objavljivanjem sadržaja dajete yugovote.com besplatnu, neekskluzivnu, teritorijalno neograničenu licencu za korišćenje, prikaz i distribuciju tog sadržaja u okviru platforme.</p>
                    <p>Sajt zadržava pravo da ukloni bilo koji korisnički sadržaj koji smatra neprikladnim, bez obrazloženja.</p>
                </section>

                <section class="ygv-legal-section" id="ip">
                    <h2>6. Intelektualna svojina</h2>
                    <p>Sav sadržaj Sajta koji nije generisan od strane korisnika — uključujući dizajn, logo, tekst, kod, grafike i algoritme — vlasništvo je yugovote.com i zaštićen je važećim propisima o zaštiti intelektualne svojine.</p>
                    <p>Zabranjeno je kopiranje, distribucija ili komercijalno korišćenje bilo kog dela Sajta bez pisane dozvole.</p>
                </section>

                <section class="ygv-legal-section" id="odgovornost">
                    <h2>7. Ograničenje odgovornosti</h2>
                    <p>yugovote.com ne garantuje tačnost, potpunost ni ažurnost sadržaja na Sajtu. Rangovi i rezultati glasanja odražavaju mišljenje zajednice korisnika i ne predstavljaju uredničke stavove platforme.</p>
                    <p>Sajt se pruža „kakav jeste" i ne preuzima odgovornost za bilo kakvu direktnu ili indirektnu štetu koja može nastati korišćenjem ili nemogućnošću korišćenja Sajta.</p>
                    <p>yugovote.com nije odgovoran za sadržaj eksternih linkova i sajtova na koje upućuje.</p>
                </section>

                <section class="ygv-legal-section" id="izmene">
                    <h2>8. Izmene uslova</h2>
                    <p>Zadržavamo pravo izmene ovih Uslova korišćenja u bilo kom trenutku. O bitnim izmenama obavestićemo registrovane korisnike putem e-pošte ili obaveštenjem na Sajtu. Nastavak korišćenja Sajta nakon objavljivanja izmena smatra se prihvatanjem novih uslova.</p>
                    <p>Preporučujemo da povremeno proverite ovu stranicu radi eventualnih izmena.</p>
                </section>

                <section class="ygv-legal-section" id="kontakt">
                    <h2>9. Kontakt</h2>
                    <p>Ukoliko imate pitanja u vezi sa ovim Uslovima korišćenja, možete nas kontaktirati:</p>
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
