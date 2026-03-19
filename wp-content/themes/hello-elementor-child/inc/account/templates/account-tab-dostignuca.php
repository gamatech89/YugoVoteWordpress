<?php if (!defined('ABSPATH')) exit;

// Ensure icons are loaded
if (!function_exists('ygv_icon_e')) {
    require_once get_stylesheet_directory() . '/inc/icons.php';
}
?>

<div class="ygv-achievements">
    <div class="ygv-card" style="text-align: center; padding: 48px 24px;">
        <div style="margin-bottom: 16px;">
            <?php ygv_icon_e('wrench', 48); ?>
        </div>
        <h3 style="margin: 0 0 8px; font-size: 20px; color: #1a1a1a;">
            <?php echo esc_html__('Sistem dostignuća je privremeno onemogućen', 'hello-elementor-child'); ?>
        </h3>
        <p style="margin: 0; color: #6b7280; font-size: 15px; max-width: 400px; margin: 0 auto;">
            <?php echo esc_html__('Radimo na poboljšanjima sistema nivoa i dostignuća. Vaša postojeća dostignuća su sačuvana i biće ponovo vidljiva uskoro.', 'hello-elementor-child'); ?>
        </p>
    </div>
</div>
