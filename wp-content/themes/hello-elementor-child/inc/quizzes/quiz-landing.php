<?php
/**
 * Stranica kviza (/quiz/x/) i zajednički okvir „landing“ za kviz i anketu — izgled B („kartica, kao anketa“).
 *
 * Kviz se igra u postojećem prozoru: dugme ima klasu .ygv-quiz-start-btn, a globalni handler u
 * js/quizzes/main.js ga otvara. Stranica se nikad ne otvara sama u prozoru.
 * Spec: docs/superpowers/specs/2026-11-24-yugovote-seo-etapa2-design.md
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stanje dugmeta: 'locked' (gost, kviz samo za prijavljene), 'replay' (prijavljen, već igrao), 'start'.
 */
function ygv_quiz_cta_state(int $user_id, bool $guest_allowed, int $attempts): string {
    if ($user_id <= 0 && !$guest_allowed) {
        return 'locked';
    }
    if ($user_id > 0 && $attempts > 0) {
        return 'replay';
    }
    return 'start';
}

/**
 * Podaci za stranicu kviza.
 */
function ygv_quiz_landing_data(int $quiz_id, int $user_id): array {
    $description = trim((string) get_post_meta($quiz_id, '_quiz_description', true));
    if ($description === '') {
        $description = trim((string) get_post_field('post_excerpt', $quiz_id));
    }

    $difficulty = '';
    $diff_slug  = '';
    $level_id   = (int) get_post_meta($quiz_id, '_quiz_difficulty', true);
    if ($level_id) {
        $level = get_post($level_id);
        if ($level) {
            $difficulty = $level->post_title;
            $diff_slug  = $level->post_name;
        }
    }

    $terms    = get_the_terms($quiz_id, 'quiz_category');
    $category = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
    $cat_color = ($category && function_exists('ygv_get_quiz_category_color'))
        ? ygv_get_quiz_category_color($quiz_id) : '#6366f1';

    $guest_allowed = get_post_meta($quiz_id, '_allow_guest_play', true) === '1';
    $progress      = ygv_quiz_progress($user_id, $quiz_id);

    return [
        'title'            => get_the_title($quiz_id),
        'description'      => $description,
        'category'         => $category,
        'category_color'   => $cat_color,
        'questions'        => (int) (get_post_meta($quiz_id, '_num_questions', true) ?: 10),
        'seconds'          => (int) (get_post_meta($quiz_id, '_time_per_question', true) ?: 10),
        'xp'               => (int) (get_post_meta($quiz_id, '_quiz_xp_value', true) ?: 20),
        'difficulty'       => $difficulty,
        'difficulty_color' => ygv_quiz_difficulty_color($diff_slug),
        'guest_allowed'    => $guest_allowed,
        'cta_state'        => ygv_quiz_cta_state($user_id, $guest_allowed, $progress['attempts']),
    ];
}

/**
 * „Još kvizova“: najnoviji iz iste kategorije, pa dopuna najnovijim sa sajta; bez trenutnog i duplikata.
 *
 * @return list<int>
 */
function ygv_quiz_related_ids(int $quiz_id, int $limit = 3): array {
    $base = [
        'post_type'        => 'quiz',
        'post_status'      => 'publish',
        'orderby'          => 'date',
        'order'            => 'DESC',
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'suppress_filters' => true,
    ];
    $ids   = [];
    $terms = get_the_terms($quiz_id, 'quiz_category');
    if ($terms && !is_wp_error($terms)) {
        $ids = get_posts($base + [
            'posts_per_page' => $limit,
            'post__not_in'   => [$quiz_id],
            'tax_query'      => [['taxonomy' => 'quiz_category', 'field' => 'term_id', 'terms' => (int) $terms[0]->term_id]],
        ]);
    }
    if (count($ids) < $limit) {
        $ids = array_merge($ids, get_posts($base + [
            'posts_per_page' => $limit - count($ids),
            'post__not_in'   => array_merge([$quiz_id], $ids),
        ]));
    }
    return array_values(array_map('intval', array_unique($ids)));
}

/**
 * Vidljiva putanja (ista lista kao JSON-LD); poslednja stavka je trenutna stranica, bez linka.
 */
function ygv_landing_crumbs(array $trail): void {
    if (!$trail) {
        return;
    }
    $last = count($trail) - 1;
    echo '<nav class="ygv-landing-crumbs" aria-label="Putanja">';
    foreach (array_values($trail) as $i => $crumb) {
        if ($i > 0) {
            echo '<span class="ygv-landing-crumbs__sep" aria-hidden="true">›</span>';
        }
        if ($i === $last) {
            echo '<span class="ygv-landing-crumbs__current" aria-current="page">' . esc_html($crumb['name']) . '</span>';
        } else {
            echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['name']) . '</a>';
        }
    }
    echo '</nav>';
}

/**
 * Kratko indigo zaglavlje: putanja levo, oznaka („Kviz“, „Anketa“) desno.
 */
function ygv_landing_hero(string $pill, array $trail): void {
    ?>
    <section class="ygv-landing-hero">
        <div class="ygv-landing-hero__in">
            <?php ygv_landing_crumbs($trail); ?>
            <span class="ygv-landing-pill"><?php echo esc_html($pill); ?></span>
        </div>
    </section>
    <?php
}

add_action('wp_enqueue_scripts', function () {
    if (!is_singular(['quiz', 'voting_poll'])) {
        return;
    }
    $file = get_stylesheet_directory() . '/css/landing.css';
    wp_enqueue_style('ygv-landing', get_stylesheet_directory_uri() . '/css/landing.css', [], (string) filemtime($file));
});
