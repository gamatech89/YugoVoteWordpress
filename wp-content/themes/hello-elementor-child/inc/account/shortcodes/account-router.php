<?php
if (!defined('ABSPATH')) exit;

/** URL helper for the Moj nalog page */
function ygv_account_page_url(array $args = []): string {
    // Change slug if your page is not /moj-nalog
    $page = get_page_by_path('moj-nalog');
    $url  = $page ? get_permalink($page->ID) : home_url('/');
    return $args ? add_query_arg($args, $url) : $url;
}

/** Tabs config (filterable) - Reordered with Profile first, includes icons */
function ygv_account_nav_items(): array {
    // key => [label, tabKey, icon]
    $items = [
        'profil'      => [__('Profil', 'hello-elementor-child'), 'profil', 'user'],
        'kvizovi'     => [__('Kvizovi', 'hello-elementor-child'), 'kvizovi', 'gamepad'],
        'liste'       => [__('Moje Liste', 'hello-elementor-child'), 'liste', 'clipboard-list'],
        'dostignuca'  => [__('Dostignuća', 'hello-elementor-child'), 'dostignuca', 'trophy'],
        'podesavanja' => [__('Podešavanja', 'hello-elementor-child'), 'podesavanja', 'settings'],
        'sigurnost'   => [__('Sigurnost', 'hello-elementor-child'), 'sigurnost', 'shield'],
    ];
    return apply_filters('ygv_account_nav_items', $items);
}

/** Get active tab info */
function ygv_account_get_active_tab_info(string $active): array {
    $items = ygv_account_nav_items();
    foreach ($items as $key => $item) {
        if ($item[1] === $active) {
            return ['label' => $item[0], 'icon' => $item[2]];
        }
    }
    // Fallback to first tab
    $first = reset($items);
    return ['label' => $first[0], 'icon' => $first[2]];
}

/** Render tab nav with icons + mobile dropdown */
function ygv_account_render_nav(string $active): string {
    $items = ygv_account_nav_items();
    $active_info = ygv_account_get_active_tab_info($active);
    ob_start(); ?>
    
    <!-- Mobile dropdown trigger (hidden on desktop) -->
    <button class="cs-acc-nav-mobile-trigger" type="button" aria-expanded="false" aria-controls="cs-acc-nav-dropdown">
      <span class="cs-acc-nav-mobile-trigger__content">
        <?php ygv_icon_e($active_info['icon'], 18); ?>
        <span class="cs-acc-nav-mobile-trigger__label"><?php echo esc_html($active_info['label']); ?></span>
      </span>
      <?php ygv_icon_e('chevron-down', 18, 'cs-acc-nav-mobile-trigger__chevron'); ?>
    </button>
    
    <!-- Mobile overlay backdrop -->
    <div class="cs-acc-nav-overlay"></div>
    
    <!-- Desktop nav (chip style) -->
    <nav class="cs-acc-nav" role="navigation" aria-label="<?php esc_attr_e('Account navigation', 'hello-elementor-child'); ?>">
      <?php foreach ($items as $key => [$label, $tab, $icon]): 
        $href = esc_url( ygv_account_page_url(['tab'=>$tab]) );
        $is   = ($active === $tab);
      ?>
        <a class="cs-chip<?php echo $is ? ' is-active':''; ?>" href="<?php echo $href; ?>" <?php echo $is ? 'aria-current="page"' : ''; ?>>
          <?php ygv_icon_e($icon, 16); ?>
          <span class="cs-chip__label"><?php echo esc_html($label); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    
    <!-- Mobile bottom sheet dropdown -->
    <div class="cs-acc-nav-mobile-dropdown" id="cs-acc-nav-dropdown" aria-hidden="true">
      <div class="cs-acc-nav-mobile-dropdown__header">
        <span class="cs-acc-nav-mobile-dropdown__title"><?php esc_html_e('Navigacija', 'hello-elementor-child'); ?></span>
        <button class="cs-acc-nav-mobile-dropdown__close" type="button" aria-label="<?php esc_attr_e('Zatvori', 'hello-elementor-child'); ?>">
          <?php ygv_icon_e('x', 20); ?>
        </button>
      </div>
      <div class="cs-acc-nav-mobile-dropdown__items">
        <?php foreach ($items as $key => [$label, $tab, $icon]): 
          $href = esc_url( ygv_account_page_url(['tab'=>$tab]) );
          $is   = ($active === $tab);
        ?>
          <a class="cs-acc-nav-mobile-item<?php echo $is ? ' is-active':''; ?>" href="<?php echo $href; ?>" <?php echo $is ? 'aria-current="page"' : ''; ?>>
            <?php ygv_icon_e($icon, 20); ?>
            <span class="cs-acc-nav-mobile-item__label"><?php echo esc_html($label); ?></span>
            <?php if ($is): ?>
              <?php ygv_icon_e('check', 18, 'cs-acc-nav-mobile-item__check'); ?>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    
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
    
    // Hidden tabs that don't show in nav (like create-list form)
    $hidden_tabs = ['kreiraj-listu'];
    
    // Only show nav for regular tabs
    $nav = in_array($tab, $hidden_tabs) ? '' : ygv_account_render_nav($tab);

    $base = get_stylesheet_directory() . '/inc/account/templates/';
    $file = $base . 'account-tab-' . $tab . '.php';
    if (!file_exists($file)) $file = $base . 'account-tab-kvizovi.php';

    ob_start();
    echo $nav;
    include $file;
    return ob_get_clean();
}
add_shortcode('yugo_account', 'yugo_account_shortcode_router');
