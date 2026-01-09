<?php
if (!defined('ABSPATH')) exit;

/** URL helper for the Moj nalog page */
function ygv_account_page_url(array $args = []): string {
    // Change slug if your page is not /moj-nalog
    $page = get_page_by_path('moj-nalog');
    $url  = $page ? get_permalink($page->ID) : home_url('/');
    return $args ? add_query_arg($args, $url) : $url;
}

/** Tabs config (filterable) */
function ygv_account_nav_items(): array {
    // key => [label, tabKey]
    $items = [
        'kvizovi'     => [__('Kvizovi', 'hello-elementor-child'), 'kvizovi'],
        'profil'      => [__('Profil', 'hello-elementor-child'), 'profil'],
        'podesavanja' => [__('Podešavanja', 'hello-elementor-child'), 'podesavanja'],
        'sigurnost'   => [__('Sigurnost', 'hello-elementor-child'), 'sigurnost'],
    ];
    return apply_filters('ygv_account_nav_items', $items);
}

/** Render tab nav */
function ygv_account_render_nav(string $active): string {
    $items = ygv_account_nav_items();
    ob_start(); ?>
    <nav class="cs-acc-nav">
      <?php foreach ($items as $key => [$label, $tab]): 
        $href = esc_url( ygv_account_page_url(['tab'=>$tab]) );
        $is   = ($active === $tab);
      ?>
        <a class="cs-chip<?php echo $is ? ' is-active':''; ?>" href="<?php echo $href; ?>">
          <?php echo esc_html($label); ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php return ob_get_clean();
}

/** Shortcode: [yugo_account] */
function yugo_account_shortcode_router($atts = []) {
    if (!is_user_logged_in()) {
        $login = wp_login_url( ygv_account_page_url() );
return '<div class="cs-card">'.sprintf(
    __('Morate biti prijavljeni. <a href="%s">Prijavite se</a>.', 'hello-elementor-child'),
    esc_url($login)
).'</div>';
    }

    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'kvizovi';
    $nav = ygv_account_render_nav($tab);

    $base = get_stylesheet_directory() . '/inc/account/templates/';
    $file = $base . 'account-tab-' . $tab . '.php';
    if (!file_exists($file)) $file = $base . 'account-tab-kvizovi.php';

    ob_start();
    echo $nav;
    include $file;
    return ob_get_clean();
}
add_shortcode('yugo_account', 'yugo_account_shortcode_router');
