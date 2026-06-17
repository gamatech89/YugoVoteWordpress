<?php
/**
 * Template Name: Kontakt
 * Description: Kontakt stranica sa formularom
 */

if (!defined('ABSPATH'))
    exit;

get_header();
?>

<main class="ygv-page ygv-page--kontakt">

    <!-- ========== HERO ========== -->
    <section class="ygv-page-hero ygv-page-hero--kontakt">
        <div class="ygv-page-hero__inner">
            <div class="ygv-page-hero__label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                Pišite nam
            </div>
            <h1 class="ygv-page-hero__title">Kontakt</h1>
            <p class="ygv-page-hero__desc">
                Imate sugestiju, pitanje ili ideju? Volimo da čujemo od vas.
            </p>
        </div>
        <div class="ygv-page-hero__wave">
            <svg viewBox="0 0 1440 48" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,48 C360,0 1080,0 1440,48 L1440,48 L0,48 Z" fill="#f8fafc"/>
            </svg>
        </div>
    </section>

    <div class="ygv-container ygv-kontakt-body">

        <div class="ygv-kontakt-layout">

            <!-- Info -->
            <aside class="ygv-kontakt-info">
                <div class="ygv-kontakt-info__card">
                    <h2>Povežimo se</h2>
                    <p>Pišite nam za pitanja, predloge, prijavljivanje grešaka ili saradnju.</p>

                    <div class="ygv-kontakt-info__items">
                        <div class="ygv-kontakt-info__item">
                            <div class="ygv-kontakt-info__icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div>
                                <span>E-mail</span>
                                <a href="mailto:office@yugovote.com">office@yugovote.com</a>
                            </div>
                        </div>

                        <div class="ygv-kontakt-info__item">
                            <div class="ygv-kontakt-info__icon ygv-kontakt-info__icon--ig">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                                </svg>
                            </div>
                            <div>
                                <span>Instagram</span>
                                <a href="https://www.instagram.com/yugovote/" target="_blank" rel="noopener">@yugovote</a>
                            </div>
                        </div>

                        <div class="ygv-kontakt-info__item">
                            <div class="ygv-kontakt-info__icon ygv-kontakt-info__icon--fb">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                </svg>
                            </div>
                            <div>
                                <span>Facebook</span>
                                <a href="https://www.facebook.com/yugovote/" target="_blank" rel="noopener">YugoVote</a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Form -->
            <div class="ygv-kontakt-form-wrap">
                <form class="ygv-kontakt-form" id="ygv-kontakt-form" novalidate>
                    <?php wp_nonce_field('ygv_kontakt_nonce', 'ygv_kontakt_nonce_field'); ?>

                    <div class="ygv-kontakt-form__row ygv-kontakt-form__row--two">
                        <div class="ygv-kontakt-form__field">
                            <label for="ygv-ime">Ime i prezime <span>*</span></label>
                            <input type="text" id="ygv-ime" name="ime" placeholder="Vaše ime" autocomplete="name" required>
                        </div>
                        <div class="ygv-kontakt-form__field">
                            <label for="ygv-email">E-mail adresa <span>*</span></label>
                            <input type="email" id="ygv-email" name="email" placeholder="vasa@adresa.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="ygv-kontakt-form__field">
                        <label for="ygv-tema">Tema</label>
                        <select id="ygv-tema" name="tema">
                            <option value="Opšto pitanje">Opšto pitanje</option>
                            <option value="Prijava greške">Prijava greške</option>
                            <option value="Predlog za sadržaj">Predlog za sadržaj</option>
                            <option value="Saradnja">Saradnja</option>
                            <option value="Ostalo">Ostalo</option>
                        </select>
                    </div>

                    <div class="ygv-kontakt-form__field">
                        <label for="ygv-poruka">Poruka <span>*</span></label>
                        <textarea id="ygv-poruka" name="poruka" rows="6" placeholder="Napišite vašu poruku..." required></textarea>
                    </div>

                    <button type="submit" class="ygv-kontakt-form__submit" id="ygv-kontakt-submit">
                        <span class="ygv-kontakt-form__submit-text">Pošalji poruku</span>
                        <span class="ygv-kontakt-form__submit-loading" style="display:none">Šalje se...</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="ygv-kontakt-form__submit-icon">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </button>

                    <div class="ygv-kontakt-form__feedback ygv-kontakt-form__feedback--success" id="ygv-kontakt-success" style="display:none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Hvala! Poruka je primljena. Odgovorićemo vam uskoro.
                    </div>

                    <div class="ygv-kontakt-form__feedback ygv-kontakt-form__feedback--error" id="ygv-kontakt-error" style="display:none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Došlo je do greške. Pokušajte ponovo ili nas kontaktirajte direktno.
                    </div>
                </form>
            </div>

        </div>

    </div>

</main>

<script>
(function () {
    var form    = document.getElementById('ygv-kontakt-form');
    var btn     = document.getElementById('ygv-kontakt-submit');
    var success = document.getElementById('ygv-kontakt-success');
    var error   = document.getElementById('ygv-kontakt-error');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var ime    = form.querySelector('[name="ime"]').value.trim();
        var email  = form.querySelector('[name="email"]').value.trim();
        var poruka = form.querySelector('[name="poruka"]').value.trim();

        if (!ime || !email || !poruka) {
            error.style.display = 'flex';
            error.querySelector('svg + *') || (error.lastChild.textContent = '');
            error.textContent = '';
            error.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Molimo popunite sva obavezna polja.';
            return;
        }

        btn.disabled = true;
        btn.querySelector('.ygv-kontakt-form__submit-text').style.display = 'none';
        btn.querySelector('.ygv-kontakt-form__submit-loading').style.display = 'inline';
        btn.querySelector('.ygv-kontakt-form__submit-icon').style.display = 'none';
        success.style.display = 'none';
        error.style.display   = 'none';

        var data = new FormData(form);
        data.append('action', 'ygv_kontakt_submit');

        fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
            method: 'POST',
            body: data
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                form.reset();
                success.style.display = 'flex';
            } else {
                error.style.display = 'flex';
            }
        })
        .catch(function () {
            error.style.display = 'flex';
        })
        .finally(function () {
            btn.disabled = false;
            btn.querySelector('.ygv-kontakt-form__submit-text').style.display = 'inline';
            btn.querySelector('.ygv-kontakt-form__submit-loading').style.display = 'none';
            btn.querySelector('.ygv-kontakt-form__submit-icon').style.display = 'inline';
        });
    });
})();
</script>

<?php get_footer(); ?>
