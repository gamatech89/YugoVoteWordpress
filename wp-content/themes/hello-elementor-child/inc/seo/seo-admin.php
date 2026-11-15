<?php
/**
 * SEO — "SEO (Google)" kutija u adminu za postove + polja za kategorije.
 * Sva polja su opciona; prazno = automatski naslov/opis.
 */

if (!defined('ABSPATH')) {
    exit;
}

function ygv_seo_box_post_types(): array {
    return ['voting_list', 'voting_items', 'quiz', 'voting_poll', 'yuv_showdown', 'vip_person', 'page'];
}

function ygv_seo_box_taxonomies(): array {
    return ['voting_list_category', 'quiz_category', 'voting_item_category'];
}

/* -------------------------------------------------------------------------
 * Render
 * ---------------------------------------------------------------------- */

/**
 * Zajednička polja. $auto = ['title' => ..., 'desc' => ...] (naslov bez sufiksa).
 */
function ygv_seo_render_fields(string $title, string $desc, bool $noindex, array $auto, bool $forced_noindex = false): void {
    wp_nonce_field('ygv_seo_save', 'ygv_seo_nonce');
    ?>
    <div class="ygv-seo-box">
        <p>
            <label for="ygv_seo_title"><strong>Naslov za Google</strong> <span class="ygv-seo-opt">(opciono)</span></label>
            <input type="text" id="ygv_seo_title" name="ygv_seo_title" class="widefat" value="<?php echo esc_attr($title); ?>"
                   placeholder="<?php echo esc_attr($auto['title']); ?>" data-ygv-seo-count="50" />
            <span class="ygv-seo-counter" data-for="ygv_seo_title"></span>
            <span class="description">Bez " | YugoVote" — to se dodaje samo.</span>
        </p>
        <p>
            <label for="ygv_seo_desc"><strong>Opis za Google</strong> <span class="ygv-seo-opt">(opciono)</span></label>
            <textarea id="ygv_seo_desc" name="ygv_seo_desc" class="widefat" rows="4"
                      placeholder="<?php echo esc_attr($auto['desc']); ?>" data-ygv-seo-count="155"><?php echo esc_textarea($desc); ?></textarea>
            <span class="ygv-seo-counter" data-for="ygv_seo_desc"></span>
            <span class="description">Najbolje 120–155 znakova.</span>
        </p>
        <p>
            <label>
                <input type="checkbox" name="ygv_seo_noindex" value="1" <?php checked($noindex || $forced_noindex); ?> <?php disabled($forced_noindex); ?> />
                Sakrij iz Google-a
            </label>
            <?php if ($forced_noindex) : ?>
                <br><span class="description">Ovaj tip stranice je uvek sakriven iz Google-a.</span>
            <?php endif; ?>
        </p>
        <div class="ygv-seo-preview">
            <span class="ygv-seo-preview-label">Ako je prazno, prikazuje se:</span>
            <span class="ygv-seo-preview-title"><?php echo esc_html($auto['title'] . YGV_SEO_SUFFIX); ?></span>
            <span class="ygv-seo-preview-desc"><?php echo esc_html($auto['desc']); ?></span>
        </div>
    </div>
    <?php
}

function ygv_seo_post_auto(WP_Post $post): array {
    if ($post->post_type === 'page' && (int) get_option('page_on_front') === (int) $post->ID) {
        $home = ygv_seo_static_copy()['home'];
        return ['title' => $home['title'], 'desc' => $home['desc']];
    }
    return ['title' => ygv_seo_auto_title_post($post), 'desc' => ygv_seo_truncate(ygv_seo_auto_desc_post($post))];
}

function ygv_seo_render_post_box(WP_Post $post): void {
    $forced = in_array($post->post_type, ygv_seo_noindex_post_types(), true)
        || ($post->post_type === 'page' && in_array($post->post_name, ygv_seo_noindex_page_slugs(), true));
    ygv_seo_render_fields(
        ygv_seo_manual('post', $post->ID, 'title'),
        ygv_seo_manual('post', $post->ID, 'desc'),
        get_post_meta($post->ID, '_ygv_seo_noindex', true) === '1',
        ygv_seo_post_auto($post),
        $forced
    );
}

function ygv_seo_render_term_fields(WP_Term $term): void {
    ?>
    <tr class="form-field ygv-seo-term">
        <th scope="row">SEO (Google)</th>
        <td>
            <?php
            ygv_seo_render_fields(
                ygv_seo_manual('term', $term->term_id, 'title'),
                ygv_seo_manual('term', $term->term_id, 'desc'),
                get_term_meta($term->term_id, '_ygv_seo_noindex', true) === '1',
                ['title' => ygv_seo_auto_title_term($term), 'desc' => ygv_seo_auto_desc_term($term)]
            );
            ?>
        </td>
    </tr>
    <?php
}

add_action('add_meta_boxes', function () {
    foreach (ygv_seo_box_post_types() as $pt) {
        add_meta_box('ygv_seo_box', 'SEO (Google)', 'ygv_seo_render_post_box', $pt, 'side', 'default');
    }
});

foreach (ygv_seo_box_taxonomies() as $ygv_seo_tax) {
    add_action("{$ygv_seo_tax}_edit_form_fields", 'ygv_seo_render_term_fields', 20);
    add_action("edited_{$ygv_seo_tax}", 'ygv_seo_save_term');
}
unset($ygv_seo_tax);

/* -------------------------------------------------------------------------
 * Save
 * ---------------------------------------------------------------------- */

function ygv_seo_nonce_ok(): bool {
    return isset($_POST['ygv_seo_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ygv_seo_nonce'])), 'ygv_seo_save');
}

/**
 * Upisuje/briše meta iz $_POST. $kind = 'post' | 'term'.
 */
function ygv_seo_store(string $kind, int $id): void {
    foreach (['title', 'desc'] as $field) {
        $raw = isset($_POST['ygv_seo_' . $field]) ? wp_unslash($_POST['ygv_seo_' . $field]) : '';
        $val = sanitize_text_field((string) $raw);
        $key = '_ygv_seo_' . $field;
        if ($val === '') {
            $kind === 'term' ? delete_term_meta($id, $key) : delete_post_meta($id, $key);
        } else {
            $kind === 'term' ? update_term_meta($id, $key, $val) : update_post_meta($id, $key, $val);
        }
    }
    $noindex = !empty($_POST['ygv_seo_noindex']);
    if ($noindex) {
        $kind === 'term' ? update_term_meta($id, '_ygv_seo_noindex', '1') : update_post_meta($id, '_ygv_seo_noindex', '1');
    } else {
        $kind === 'term' ? delete_term_meta($id, '_ygv_seo_noindex') : delete_post_meta($id, '_ygv_seo_noindex');
    }
}

function ygv_seo_save_post($post_id): void {
    $post_id = (int) $post_id;
    if (!ygv_seo_nonce_ok()) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return;
    }
    if (!in_array(get_post_type($post_id), ygv_seo_box_post_types(), true)) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    ygv_seo_store('post', $post_id);
}
add_action('save_post', 'ygv_seo_save_post');

function ygv_seo_save_term($term_id): void {
    $term_id = (int) $term_id;
    if (!ygv_seo_nonce_ok() || !current_user_can('edit_term', $term_id)) {
        return;
    }
    ygv_seo_store('term', $term_id);

    // LiteSpeed ne čisti keš na izmenu kategorije — bez ovoga promena se ne vidi do isteka keša.
    $link = get_term_link($term_id);
    if (!is_wp_error($link)) {
        do_action('litespeed_purge_url', $link);
    }
}

/* -------------------------------------------------------------------------
 * Assets (samo na ekranima za uređivanje pokrivenih tipova)
 * ---------------------------------------------------------------------- */

function ygv_seo_is_box_screen(): bool {
    if (!function_exists('get_current_screen')) {
        return false;
    }
    $s = get_current_screen();
    if (!$s) {
        return false;
    }
    if ($s->base === 'post' && in_array($s->post_type, ygv_seo_box_post_types(), true)) {
        return true;
    }
    return $s->base === 'term' && in_array($s->taxonomy, ygv_seo_box_taxonomies(), true);
}

add_action('admin_enqueue_scripts', function () {
    if (!ygv_seo_is_box_screen()) {
        return;
    }
    wp_enqueue_style(
        'ygv-seo-admin',
        get_stylesheet_directory_uri() . '/css/seo-admin.css',
        [],
        defined('HELLO_ELEMENTOR_CHILD_VERSION') ? HELLO_ELEMENTOR_CHILD_VERSION : null
    );
});

add_action('admin_footer', function () {
    if (!ygv_seo_is_box_screen()) {
        return;
    }
    ?>
    <script>
    (function () {
        document.querySelectorAll('[data-ygv-seo-count]').forEach(function (el) {
            var max = parseInt(el.getAttribute('data-ygv-seo-count'), 10);
            var out = document.querySelector('.ygv-seo-counter[data-for="' + el.id + '"]');
            if (!out) { return; }
            var update = function () {
                var n = Array.from(el.value).length;
                out.textContent = n + ' / ' + max;
                out.classList.toggle('is-over', n > max);
            };
            el.addEventListener('input', update);
            update();
        });
    })();
    </script>
    <?php
});
