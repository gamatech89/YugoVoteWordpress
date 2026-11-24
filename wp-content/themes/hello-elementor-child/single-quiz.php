<?php
/**
 * Stranica kviza — izgled B („kartica, kao anketa“).
 * Dugme otvara postojeći prozor kviza (js/quizzes/main.js); stranica se ne otvara sama u prozoru.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()):
    the_post();
    $quiz_id = get_the_ID();
    $user_id = get_current_user_id();
    $d       = ygv_quiz_landing_data($quiz_id, $user_id);
    $title   = get_the_title($quiz_id);
    $cta     = [
        'locked' => ['icon' => 'lock', 'label' => 'Prijavi se da igraš'],
        'replay' => ['icon' => 'refresh', 'label' => 'Igraj ponovo'],
        'start'  => ['icon' => 'play', 'label' => 'Započni kviz'],
    ][$d['cta_state']];
    ?>
    <main id="content" class="site-main ygv-landing ygv-landing--quiz">

        <?php ygv_landing_hero('Kviz', ygv_breadcrumb_trail_for(get_post($quiz_id))); ?>

        <div class="ygv-landing-wrap">
            <article class="ygv-landing-card">
                <div class="ygv-landing-card__media">
                    <div class="ygv-landing-card__img">
                        <?php if (has_post_thumbnail($quiz_id)): ?>
                            <?php echo get_the_post_thumbnail($quiz_id, 'large', ['alt' => esc_attr(wp_strip_all_tags($title)), 'loading' => 'eager', 'fetchpriority' => 'high']); ?>
                        <?php else: ?>
                            <div class="ygv-landing-card__placeholder"
                                style="background:linear-gradient(135deg,<?php echo esc_attr($d['category_color']); ?> 0%,#1e2a5e 100%);">
                                <?php ygv_icon_e('brain', 48); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($d['category']): ?>
                            <span class="ygv-landing-card__badge" style="background:<?php echo esc_attr($d['category_color']); ?>;"><?php echo esc_html($d['category']->name); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ygv-landing-card__body">
                    <h1 class="ygv-landing-card__title"><?php echo esc_html($title); ?></h1>

                    <div class="ygv-landing-card__meta">
                        <span><?php ygv_icon_e('file-text', 15); ?><?php echo (int) $d['questions']; ?> pitanja</span>
                        <?php if ($d['difficulty'] !== ''): ?>
                            <span class="ygv-landing-card__diff" style="color:<?php echo esc_attr($d['difficulty_color']); ?>;"><?php ygv_icon_e('star', 15); ?><?php echo esc_html($d['difficulty']); ?></span>
                        <?php endif; ?>
                        <span><?php ygv_icon_e('clock', 15); ?><?php echo (int) $d['seconds']; ?> s po pitanju</span>
                        <span><?php ygv_icon_e('zap', 15); ?>+<?php echo (int) $d['xp']; ?> XP</span>
                    </div>

                    <?php if ($d['description'] !== ''): ?>
                        <p class="ygv-landing-card__desc"><?php echo esc_html($d['description']); ?></p>
                    <?php endif; ?>

                    <button type="button" class="ygv-landing-cta ygv-quiz-start-btn"
                        data-quiz-id="<?php echo (int) $quiz_id; ?>"
                        <?php echo $d['cta_state'] === 'locked' ? 'data-locked="true"' : ''; ?>>
                        <?php ygv_icon_e($cta['icon'], 18); ?>
                        <span><?php echo esc_html($cta['label']); ?></span>
                    </button>
                    <?php if ($user_id <= 0 && $d['guest_allowed']): ?>
                        <p class="ygv-landing-note">Igraš odmah, bez registracije</p>
                    <?php endif; ?>
                </div>
            </article>
        </div>

        <?php $related = ygv_quiz_related_ids($quiz_id); ?>
        <?php if ($related): ?>
            <section class="ygv-landing-more">
                <div class="ygv-landing-more__in">
                    <h2 class="ygv-landing-more__title"><?php ygv_icon_e('brain', 26); ?>Još kvizova</h2>
                    <div class="ygv-landing-more__grid">
                        <?php foreach ($related as $rid) { ygv_render_quiz_card($rid, $user_id); } ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    </main>
    <?php
endwhile;

get_footer();
