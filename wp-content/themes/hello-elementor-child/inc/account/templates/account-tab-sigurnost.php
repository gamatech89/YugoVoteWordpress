<?php if (!defined('ABSPATH')) exit;
$profile_url = admin_url('profile.php'); // WP profile page for password change
?>
<div class="ygv-card">
  <h3><?php echo esc_html__('Sigurnost', 'hello-elementor-child'); ?></h3>
  <ul class="ygv-list">
    <li>
      <span><?php echo esc_html__('Promena lozinke', 'hello-elementor-child'); ?></span>
      <a class="ygv-btn" href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noopener">
        <?php echo esc_html__('Otvori u WP profilu', 'hello-elementor-child'); ?>
      </a>
    </li>
    <li>
      <span><?php echo esc_html__('Odjava sa svih uređaja', 'hello-elementor-child'); ?></span>
      <span class="ygv-muted"><?php echo esc_html__('Uskoro', 'hello-elementor-child'); ?></span>
    </li>
    <li>
      <span><?php echo esc_html__('Dvofaktorska autentifikacija (2FA)', 'hello-elementor-child'); ?></span>
      <span class="ygv-muted"><?php echo esc_html__('Uskoro', 'hello-elementor-child'); ?></span>
    </li>
  </ul>
</div>
