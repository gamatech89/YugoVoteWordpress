<?php
/**
 * 404 Error Page
 */

if (!defined('ABSPATH'))
    exit;

get_header();
?>

<main class="ygv-page ygv-404-page">

    <div class="ygv-container ygv-404-wrap">

        <div class="ygv-404-code">404</div>

        <h1 class="ygv-404-title">Ova stranica ne postoji</h1>
        <p class="ygv-404-desc">
            Izgledaš kao Elvis Prisli — nisi tamo gde se očekuješ.<br>
            Hajde da te vratimo na pravo mesto.
        </p>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="ygv-404-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Idi na početnu
        </a>

        <div class="ygv-404-links">
            <p>Ili idi pravo na:</p>
            <div class="ygv-404-links__grid">
                <a href="<?php echo esc_url(home_url('/kvizovi/')); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    Kvizovi
                </a>
                <a href="<?php echo esc_url(home_url('/dvoboji/')); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 3 21 3 21 8"/>
                        <line x1="4" y1="20" x2="21" y2="3"/>
                        <polyline points="21 16 21 21 16 21"/>
                        <line x1="15" y1="15" x2="21" y2="21"/>
                    </svg>
                    Dvoboji
                </a>
                <a href="<?php echo esc_url(home_url('/ankete/')); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    Ankete
                </a>
            </div>
        </div>

    </div>

</main>

<?php get_footer(); ?>
