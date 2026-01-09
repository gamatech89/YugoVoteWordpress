<?php if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
$gender  = get_user_meta($user->ID, '_user_gender', true);
$dob     = get_user_meta($user->ID, '_user_dob', true);
$country = get_user_meta($user->ID, '_user_country', true);
$poi     = (array) get_user_meta($user->ID, '_user_points_of_interest', true);

$poi_names = [];
if ($poi) {
    foreach ($poi as $tid) {
        $term = get_term((int)$tid, 'voting_list_category');
        if ($term && !is_wp_error($term)) $poi_names[] = $term->name;
    }
}
$edit_url = home_url('/kompletiranje-naloga/');
?>
<div class="ygv-card">
  <h3><?php echo esc_html__('Profil', 'hello-elementor-child'); ?></h3>
  <p><strong><?php echo esc_html__('Ime', 'hello-elementor-child'); ?>:</strong> <?php echo esc_html($user->display_name); ?></p>
  <p><strong><?php echo esc_html__('Email', 'hello-elementor-child'); ?>:</strong> <?php echo esc_html($user->user_email); ?></p>
  <p><strong><?php echo esc_html__('Pol', 'hello-elementor-child'); ?>:</strong> <?php echo $gender ? esc_html($gender) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
  <p><strong><?php echo esc_html__('Datum rođenja', 'hello-elementor-child'); ?>:</strong> <?php echo $dob ? esc_html($dob) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
  <p><strong><?php echo esc_html__('Država', 'hello-elementor-child'); ?>:</strong> <?php echo $country ? esc_html($country) : '<span class="ygv-muted">'.esc_html__('nije postavljeno', 'hello-elementor-child').'</span>'; ?></p>
  <p><strong><?php echo esc_html__('Interesovanja', 'hello-elementor-child'); ?>:</strong>
    <?php echo $poi_names ? esc_html(implode(', ', $poi_names)) : '<span class="ygv-muted">'.esc_html__('nema sačuvanih', 'hello-elementor-child').'</span>'; ?>
  </p>
  <p><a class="ygv-btn" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Uredi profil', 'hello-elementor-child'); ?></a></p>
</div>
