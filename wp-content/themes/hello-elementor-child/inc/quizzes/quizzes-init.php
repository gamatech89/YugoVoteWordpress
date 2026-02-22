<?php
/**
 * Quizzes Feature Initializer
 *
 * Loads all necessary files for the quizzes functionality.
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

// Base path
$quizzes_inc_path = trailingslashit( get_stylesheet_directory() ) . 'inc/quizzes/';

// --- CPTs & Taxonomies ---
require_once $quizzes_inc_path . 'cpts/cpt-quiz.php';
require_once $quizzes_inc_path . 'cpts/cpt-quiz-level.php';
require_once $quizzes_inc_path . 'cpts/cpt-questions.php';
require_once $quizzes_inc_path . 'cpts/taxonomy-question-category.php';
require_once $quizzes_inc_path . 'cpts/taxonomy-quiz-category.php';

// --- Meta Boxes ---
if (class_exists('HelloElementorChild\\Quizzes\\Meta\\QuizMetaBox')) {
    HelloElementorChild\Quizzes\Meta\QuizMetaBox::register();
}
if (class_exists('HelloElementorChild\\Quizzes\\Meta\\QuestionMetaBox')) {
    HelloElementorChild\Quizzes\Meta\QuestionMetaBox::register();
}
if (class_exists('HelloElementorChild\\Quizzes\\Meta\\QuizLevelMetaBox')) {
    HelloElementorChild\Quizzes\Meta\QuizLevelMetaBox::register();
}
if (class_exists('HelloElementorChild\\Quizzes\\Meta\\QuizCategoryMeta')) {
    HelloElementorChild\Quizzes\Meta\QuizCategoryMeta::register();
}

// --- Services (autoloaded via PSR-4) ---

// --- Shortcodes ---
$levels_sc = $quizzes_inc_path . 'shortcodes/levels-per-category.php';
if (file_exists($levels_sc)) require_once $levels_sc;

// ✅ NEW: Quiz Grid Shortcode
$quiz_grid_sc = $quizzes_inc_path . 'shortcodes/quiz-grid-shortcode.php';
if (file_exists($quiz_grid_sc)) require_once $quiz_grid_sc;

// ✅ NEW: Quiz Grid AJAX Handler
$quiz_ajax_grid = $quizzes_inc_path . 'api/quiz-ajax-grid.php';
if (file_exists($quiz_ajax_grid)) require_once $quiz_ajax_grid;

// Accessors
if (!function_exists('ygv_tokens')) {
    function ygv_tokens(): HelloElementorChild\Quizzes\Services\TokenService {
        static $s = null; return $s ?: $s = new HelloElementorChild\Quizzes\Services\TokenService();
    }
}
if (!function_exists('ygv_progress')) {
    function ygv_progress(): HelloElementorChild\Quizzes\Services\ProgressService {
        static $s = null; return $s ?: $s = new HelloElementorChild\Quizzes\Services\ProgressService();
    }
}

// --- API Endpoints (guarded) ---
// GET /yugovote/v1/quiz/{id}
if (file_exists($quizzes_inc_path . 'api/quiz-endpoints.php')) {
    require_once $quizzes_inc_path . 'api/quiz-endpoints.php';
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('YGV Quizzes: api/quiz-endpoints.php not found');
}

// POST /yugovote/v1/quiz/{id}/start and /submit
if (file_exists($quizzes_inc_path . 'api/quiz-gate-endpoints.php')) {
    require_once $quizzes_inc_path . 'api/quiz-gate-endpoints.php';
} elseif (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('YGV Quizzes: api/quiz-gate-endpoints.php not found');
}

// --- Admin (columns, filters) ---
if (file_exists($quizzes_inc_path . 'admin/question-columns.php')) {
    require_once $quizzes_inc_path . 'admin/question-columns.php';
}

// --- Helpers ---
if (file_exists($quizzes_inc_path . 'helpers/helper-functions.php')) {
    require_once $quizzes_inc_path . 'helpers/helper-functions.php';
}

// --- Frontend scripts for quizzes (if any) ---
if (file_exists($quizzes_inc_path . 'quizzes-scripts.php')) {
    require_once $quizzes_inc_path . 'quizzes-scripts.php';
}

// --- Ensure every user gets a wallet ---
add_action('user_register', function ($user_id) {
    ygv_tokens()->ensure_wallet((int) $user_id);
}, 10);

add_action('wp_login', function ($user_login, $user) {
    ygv_tokens()->ensure_wallet((int) $user->ID);
}, 10, 2);

add_filter('ygv_tokens_user_max', function ($default_max, $user_id) {
    $level = 0;

    if (function_exists('ygv_progress') && method_exists(ygv_progress(), 'get_overall_progress')) {
        $overall = ygv_progress()->get_overall_progress((int)$user_id);
        $level = is_array($overall) ? (int)($overall['overall_level'] ?? 0) : (int)$overall;
    } else {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT overall_level FROM {$wpdb->prefix}ygv_user_overall_progress WHERE user_id=%d", $user_id),
            ARRAY_A
        );
        if ($row) $level = (int)$row['overall_level'];
    }

    $base      = (int) apply_filters('ygv_tokens_default_max', 48);
    $per_level = 2; // tweak if you want
    return max($default_max, $base + $level * $per_level);
}, 10, 2);

// --- Defaults (filters keep config centralized) ---
add_filter('ygv_tokens_default_max', function ($v) { return 48; });
add_filter('ygv_tokens_default_regen_rate', function ($v) { return 2; });
add_filter('ygv_tokens_default_regen_interval_minutes', function ($v) { return 60; });




