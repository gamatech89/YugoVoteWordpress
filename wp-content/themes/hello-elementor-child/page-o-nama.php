<?php
/**
 * Template Name: O Nama
 * Description: Stranica o YugoVote zajednici
 */

if (!defined('ABSPATH'))
    exit;

get_header();
?>

<main class="ygv-page ygv-page--o-nama">

    <!-- ========== HERO ========== -->
    <section class="ygv-page-hero ygv-page-hero--o-nama">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 8v4l3 3" />
                </svg>
                O Nama
            </div>
            <h1 class="ygv-page-hero__title">YU Go Vote!</h1>
            <p class="ygv-page-hero__desc">
                Glasaj o onome što znaš. Tvoj glas je tvoj — ali lista je realna.
            </p>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#f8fafc" />
            </svg>
        </div>
    </section>

    <div class="ygv-container ygv-o-nama-body">

        <!-- ========== SLOGANI ========== -->
        <section class="ygv-ona-slogans">
            <div class="ygv-ona-slogan">
                <span class="ygv-ona-slogan__icon">&#128150;</span>
                <p>Svi su mladi i lepi na yugovote.com</p>
            </div>
            <div class="ygv-ona-slogan">
                <span class="ygv-ona-slogan__icon">&#9878;</span>
                <p>Sve naše je najlepše i najbolje na yugovote.com</p>
            </div>
            <div class="ygv-ona-slogan">
                <span class="ygv-ona-slogan__icon">&#128373;</span>
                <p>Na yugovote.com svačiji glas ne vredi isto — napokon.</p>
            </div>
        </section>

        <!-- ========== MISIJA ========== -->
        <section class="ygv-ona-section">
            <div class="ygv-ona-section__badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
                Naša misija
            </div>
            <h2 class="ygv-ona-section__title">Obrazovni, zabavni i humanitarni karakter</h2>
            <p class="ygv-ona-section__text">
                yugovote.com propagira prave vrednosti, bez obzira na vaše mišljenje o tome.
                Sajt ima obrazovni, zabavni i humanitarni karakter. Njegov cilj je da kroz zanimljiv i
                interaktivan sadržaj podstakne međusobno upoznavanje ljudi iz regiona, doprinese
                pomirenju i boljem razumevanju, te pomogne u rušenju predrasuda, stereotipa i
                tabua koji i dalje postoje.
            </p>
        </section>

        <!-- ========== VI ODLUČUJETE ========== -->
        <section class="ygv-ona-split">
            <div class="ygv-ona-split__card ygv-ona-split__card--you">
                <div class="ygv-ona-split__icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </div>
                <h3>Vi odlučujete</h3>
                <p>
                    Ne treba plaćeni stručnjaci da vam kažu koje je plaža ili glumica najbolja,
                    niti likovi stali u osamdesetim da vam objašnjavaju koji je album najboji.
                    Vi se pitate. Oni stručniji&nbsp;— malo više.
                </p>
            </div>
            <div class="ygv-ona-split__card ygv-ona-split__card--algo">
                <div class="ygv-ona-split__icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </div>
                <h3>Realan redosled</h3>
                <p>
                    Vaše mišljenje je vaše, ali redosled na listi je realan jer je rezultat velikog broja
                    glasova — i posetilaca koji su dokazali znanje, tako da njihov glas vredi više.
                    Naš algoritam eliminiše evidentne nelogičnosti i zlonamerne pokušaje narušavanja integriteta.
                </p>
            </div>
        </section>

        <!-- ========== KO GLASA ========== -->
        <section class="ygv-ona-section ygv-ona-section--community">
            <div class="ygv-ona-section__badge ygv-ona-section__badge--red">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
                Zajednica
            </div>
            <h2 class="ygv-ona-section__title">Dobri, lepi, pošteni i neostrašćeni</h2>
            <p class="ygv-ona-section__text">
                Na yugovote.com glasaju dobri, lepi, pošteni i neostrašćeni ljudi bez velikog ega i sujete.
                Za njih najlepši fudbaleri nisu isključivo Srbi. Tito je i Slovenac (po majci) — ako vredi za
                Dončića... Tesla je Srbin, a Dražen — Hrvat. Andrić je Hrvat (po rođenju), Bosanac (po mestu
                rođenja) i Srbin (po opredeljenju). Šantić je Srbin iz Bosne i nalazi se i na listi najvećih
                srpskih i bosanskih pesnika. Termin Hrvata i hrvatski nije sinonim. Takođe, teško je poverovati
                — Elvis Prisli nije Srbin (svi ostali jesu).
            </p>
            <blockquote class="ygv-ona-quote">
                To je logika i nepromenljiva je. Ako vam se sviđa — glasajte. Ako se palite na tzv.
                (h)istoričare i pseudostručnjake — molimo, napustite sajt.
            </blockquote>
        </section>

        <!-- ========== CTA ========== -->
        <section class="ygv-ona-cta">
            <div class="ygv-ona-cta__inner">
                <p class="ygv-ona-cta__eyebrow">Zato,</p>
                <h2 class="ygv-ona-cta__title">YU Go Vote!</h2>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ygv-ona-cta__btn">
                    Počni glasati
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </section>

    </div>

</main>

<?php get_footer(); ?>
