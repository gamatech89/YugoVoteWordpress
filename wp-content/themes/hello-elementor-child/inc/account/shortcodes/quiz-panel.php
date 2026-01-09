<?php
if (!defined('ABSPATH')) exit;

/**
 * [yugo_account_panel]
 * Tokens + overall + per-category panel for the “Kvizovi” tab.
 */
function ygv_account_panel_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="cs-card">' .
            esc_html__('Morate biti prijavljeni da biste videli napredak i tokene.', 'hello-elementor-child') .
        '</div>';
    }

    // Enqueue panel JS and pass REST info (no jQuery)
    if (!wp_script_is('ygv-account', 'enqueued')) {
        wp_enqueue_script(
            'ygv-account',
            get_stylesheet_directory_uri() . '/js/account/ygv-account.js',
            [],
            defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : '1.0.0',
            true
        );
        wp_localize_script('ygv-account', 'YGV_ACCOUNT', [
            'restRoot' => esc_url_raw( rest_url('yugovote/v1') ),
            'nonce'    => wp_create_nonce('wp_rest'),
        ]);
    }

    ob_start(); ?>
    <div id="ygv-account" class="cs-account">
      <div class="cs-row">
        <div class="cs-card" style="flex:1;">
          <h3><?php echo esc_html__('Tokeni', 'hello-elementor-child'); ?></h3>
          <div class="cs-inline">
            <div><strong id="ygv-tokens">--</strong>/<span id="ygv-tokens-max">--</span></div>
            <div class="cs-muted">·</div>
            <div class="cs-muted">
              <?php echo esc_html__('Sledeće punjenje za', 'hello-elementor-child'); ?>:
              <span id="ygv-next-in">--:--</span>
            </div>
          </div>
          <div class="cs-meter" style="margin:10px 0 4px;"><span id="ygv-tokens-bar"></span></div>
          <div class="cs-inline" style="gap:12px;margin-top:8px;">
            <button class="cs-btn" id="ygv-spend-8"><?php echo esc_html__('Potroši 8 tokena (test)', 'hello-elementor-child'); ?></button>
            <span class="cs-muted" id="ygv-spend-result"></span>
          </div>
        </div>

        <div class="cs-card" style="flex:1;">
          <h3><?php echo esc_html__('Ukupan nivo', 'hello-elementor-child'); ?></h3>
          <div class="cs-inline" style="gap:12px;">
            <div><strong id="ygv-overall-level">--</strong></div>
            <div class="cs-muted">XP: <span id="ygv-overall-xp">--</span></div>
          </div>
        </div>
      </div>

      <div class="cs-card">
        <h3><?php echo esc_html__('Nivoi po kategorijama', 'hello-elementor-child'); ?></h3>
        <ul id="ygv-cats" class="cs-list"></ul>
      </div>

      <div class="cs-card">
        <h3><?php echo esc_html__('Brzi test', 'hello-elementor-child'); ?></h3>
        <div class="cs-grid">
          <div>
            <label for="ygv-quiz-id"><strong><?php echo esc_html__('ID kviza', 'hello-elementor-child'); ?></strong></label>
            <input id="ygv-quiz-id" type="number" min="1" placeholder="123"
                   style="width:100%;padding:8px;border:1px solid #e1e4ea;border-radius:8px">
            <div class="cs-inline" style="gap:12px;margin-top:8px;">
              <button class="cs-btn primary" id="ygv-start"><?php echo esc_html__('Pokreni kviz', 'hello-elementor-child'); ?></button>
              <span class="cs-muted" id="ygv-start-result"></span>
            </div>
          </div>
          <div>
            <label><strong><?php echo esc_html__('Predaja pokušaja', 'hello-elementor-child'); ?></strong></label>
            <div class="cs-inline" style="gap:8px;flex-wrap:wrap">
              <input id="ygv-attempt-id" type="number" min="1" placeholder="attempt_id"
                     style="padding:8px;border:1px solid #e1e4ea;border-radius:8px">
              <input id="ygv-correct" type="number" min="0" placeholder="tačnih"
                     style="padding:8px;border:1px solid #e1e4ea;border-radius:8px">
              <input id="ygv-total" type="number" min="1" placeholder="ukupno"
                     style="padding:8px;border:1px solid #e1e4ea;border-radius:8px">
              <button class="cs-btn" id="ygv-submit"><?php echo esc_html__('Predaj', 'hello-elementor-child'); ?></button>
              <span class="cs-muted" id="ygv-submit-result"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yugo_account_panel', 'ygv_account_panel_shortcode');
