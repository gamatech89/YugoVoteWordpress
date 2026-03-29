<?php
/**
 * Showdown Metabox - Admin UI for managing showdown items
 */

if (!defined('ABSPATH')) exit;

function yuv_add_showdown_metabox() {
    add_meta_box(
        'yuv_showdown_settings',
        'Podešavanja Showdown-a',
        'yuv_showdown_metabox_callback',
        'yuv_showdown',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuv_add_showdown_metabox');

function yuv_showdown_metabox_callback($post) {
    wp_nonce_field('yuv_showdown_meta_save', 'yuv_showdown_meta_nonce');

    // Enqueue media uploader
    wp_enqueue_media();

    // Enqueue admin script
    wp_enqueue_script(
        'yuv-showdown-meta-admin',
        get_stylesheet_directory_uri() . '/js/admin/admin-showdown-meta.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    wp_localize_script('yuv-showdown-meta-admin', 'yuvShowdownMeta', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('yuv_showdown_search'),
        'postId'  => $post->ID
    ]);

    // Get existing values
    $status = get_post_meta($post->ID, '_yuv_showdown_status', true) ?: 'draft';
    $items = get_post_meta($post->ID, '_yuv_showdown_items', true);
    $items = is_array($items) ? $items : [];
    $items_filled = count(array_filter($items, function($c) { return !empty($c['name']); }));
    
    // Get categories for filter
    $categories = get_terms([
        'taxonomy'   => 'voting_item_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC'
    ]);

    // Get session count
    $manager = new YUV_Showdown_Manager();
    $session_count = $manager->get_session_count($post->ID);

    ?>
    <div class="yuv-showdown-meta">
        <style>
            .yuv-showdown-meta {
                padding: 15px 20px 20px;
                max-width: 1100px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .yuv-tab-nav {
                display: flex;
                border-bottom: 2px solid #c3c4c7;
                margin-bottom: 20px;
                gap: 4px;
            }
            .yuv-tab-btn {
                padding: 12px 24px;
                background: transparent;
                border: none;
                border-bottom: 3px solid transparent;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                color: #646970;
                transition: all 0.2s;
                position: relative;
                top: 2px;
            }
            .yuv-tab-btn:hover { color: #2271b1; background: #f6f7f7; }
            .yuv-tab-btn.active { color: #2271b1; border-bottom-color: #2271b1; background: #fff; }
            .yuv-tab-content { display: none; animation: yuvFadeIn 0.3s; }
            .yuv-tab-content.active { display: block; }
            @keyframes yuvFadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .yuv-meta-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .yuv-meta-section h3 {
                margin: 0 0 16px 0;
                color: #1d2327;
                font-size: 15px;
                font-weight: 600;
            }
            .yuv-meta-row { margin-bottom: 20px; }
            .yuv-meta-row:last-child { margin-bottom: 0; }
            .yuv-meta-row label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #1d2327;
                font-size: 13px;
            }
            .yuv-meta-row select {
                width: 100%;
                max-width: 400px;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-meta-row .description {
                margin: 6px 0 0 0;
                color: #646970;
                font-size: 12px;
                font-style: italic;
            }
            .yuv-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 16px;
            }
            .yuv-status-badge.active { background: #d4edda; color: #155724; }
            .yuv-status-badge.completed { background: #cce5ff; color: #004085; }
            .yuv-status-badge.draft { background: #f0f0f1; color: #646970; }
            
            .yuv-stats-bar {
                display: flex;
                gap: 20px;
                padding: 12px 16px;
                background: #f0f6fc;
                border-radius: 4px;
                margin-bottom: 16px;
            }
            .yuv-stat-item { text-align: center; }
            .yuv-stat-value { font-size: 22px; font-weight: 700; color: #2271b1; display: block; }
            .yuv-stat-label { font-size: 11px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px; }
            
            .yuv-category-filter { margin-bottom: 12px; }
            .yuv-category-filter label { display: inline-block; margin-right: 8px; font-weight: 600; font-size: 13px; }
            .yuv-category-filter select { min-width: 250px; padding: 6px 10px; border: 1px solid #8c8f94; border-radius: 4px; }
            
            .yuv-search-wrapper { position: relative; margin-bottom: 16px; }
            .yuv-search-input {
                width: 100%;
                padding: 10px 14px;
                border: 2px solid #2271b1;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-search-input:focus { outline: none; border-color: #135e96; box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1); }
            .yuv-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                max-height: 350px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: none;
                margin-top: 4px;
            }
            .yuv-search-result-item {
                padding: 10px 12px;
                border-bottom: 1px solid #f0f0f1;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: background 0.15s;
            }
            .yuv-search-result-item:hover { background: #f6f7f7; }
            .yuv-search-result-image { width: 45px; height: 45px; object-fit: cover; border-radius: 3px; flex-shrink: 0; }
            .yuv-search-result-info h4 { margin: 0 0 2px 0; font-size: 13px; font-weight: 600; }
            .yuv-search-result-info p { margin: 0; font-size: 11px; color: #646970; }
            
            .yuv-items-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 12px; }
            .yuv-item-row {
                display: flex;
                gap: 12px;
                padding: 12px;
                background: #f9f9f9;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                position: relative;
            }
            .yuv-item-row:hover { background: #f6f7f7; border-color: #c3c4c7; }
            .yuv-item-image-col { flex-shrink: 0; display: flex; flex-direction: column; gap: 6px; }
            .yuv-item-image-preview {
                width: 80px; height: 80px;
                border: 2px dashed #c3c4c7;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                overflow: hidden;
            }
            .yuv-item-image-preview img { width: 100%; height: 100%; object-fit: cover; }
            .yuv-item-image-preview.empty { color: #a7aaad; font-size: 10px; text-align: center; padding: 6px; }
            .yuv-select-image-btn { width: 80px; padding: 4px 8px; font-size: 11px; text-align: center; }
            .yuv-item-fields { flex: 1; display: flex; flex-direction: column; gap: 8px; }
            .yuv-item-fields input[type="text"],
            .yuv-item-fields textarea {
                width: 100%;
                padding: 7px 10px;
                border: 1px solid #c3c4c7;
                border-radius: 3px;
                font-size: 13px;
            }
            .yuv-item-fields input[type="text"]:focus,
            .yuv-item-fields textarea:focus { border-color: #2271b1; outline: none; box-shadow: 0 0 0 1px #2271b1; }
            .yuv-item-fields textarea { min-height: 50px; resize: vertical; font-family: inherit; }
            .yuv-item-remove {
                position: absolute;
                top: 10px;
                right: 10px;
                background: #dc3545;
                color: #fff;
                border: none;
                width: 24px; height: 24px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            .yuv-item-remove:hover { background: #a02622; transform: scale(1.1); }
            .yuv-add-item-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 14px;
                background: #f0f6fc;
                border: 2px dashed #2271b1;
                border-radius: 8px;
                color: #2271b1;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                margin-top: 12px;
            }
            .yuv-add-item-btn:hover { background: #e7f3ff; border-color: #135e96; }
        </style>

        <!-- Status Badge -->
        <div class="yuv-status-badge <?php echo esc_attr($status); ?>">
            <?php
            $status_labels = ['draft' => '📝 Draft', 'active' => '🟢 Aktivan', 'completed' => '🏁 Završen'];
            echo $status_labels[$status] ?? '📝 Draft';
            ?>
        </div>

        <!-- Stats Bar -->
        <div class="yuv-stats-bar">
            <div class="yuv-stat-item">
                <span class="yuv-stat-value" id="items-count"><?php echo $items_filled; ?></span>
                <span class="yuv-stat-label">Učesnika</span>
            </div>
            <div class="yuv-stat-item">
                <span class="yuv-stat-value"><?php echo $session_count; ?></span>
                <span class="yuv-stat-label">Odigranih</span>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="yuv-tab-nav">
            <button type="button" class="yuv-tab-btn active" data-tab="config">⚙️ Podešavanja</button>
            <button type="button" class="yuv-tab-btn" data-tab="items">
                👥 Učesnici (<span id="item-counter"><?php echo $items_filled; ?></span>)
            </button>
        </div>

        <!-- TAB 1: Config -->
        <div class="yuv-tab-content active" data-tab="config">
            <div class="yuv-meta-section">
                <div class="yuv-meta-row">
                    <label for="yuv_showdown_status">📊 Status:</label>
                    <select id="yuv_showdown_status" name="yuv_showdown_status">
                        <option value="draft" <?php selected($status, 'draft'); ?>>📝 Draft — Nije vidljiv</option>
                        <option value="active" <?php selected($status, 'active'); ?>>🟢 Aktivan — Korisnici mogu igrati</option>
                        <option value="completed" <?php selected($status, 'completed'); ?>>🏁 Završen — Prikazan u arhivi</option>
                    </select>
                    <p class="description">Samo jedan Showdown može biti aktivan u isto vreme.</p>
                </div>
            </div>
        </div>

        <!-- TAB 2: Items -->
        <div class="yuv-tab-content" data-tab="items">
            <div class="yuv-meta-section">
                <div class="yuv-category-filter">
                    <label for="yuv-sd-category-filter">Filtriraj po kategoriji:</label>
                    <select id="yuv-sd-category-filter">
                        <option value="">Sve kategorije</option>
                        <?php 
                        if (!empty($categories) && !is_wp_error($categories)) {
                            foreach ($categories as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="yuv-search-wrapper">
                    <input type="text" 
                           id="yuv-sd-candidate-search" 
                           class="yuv-search-input" 
                           placeholder="🔍 Pretraži postojeće kandidate..."
                           autocomplete="off">
                    <div id="yuv-sd-search-results" class="yuv-search-results"></div>
                </div>

                <ul class="yuv-items-list" id="yuv-sd-items-list">
                    <?php 
                    foreach ($items as $i => $item) {
                        if (empty($item['name'])) continue;
                        yuv_render_showdown_item_row($i, $item['name'], $item['description'] ?? '', $item['image_id'] ?? '', $item['image_url'] ?? '');
                    }
                    ?>
                </ul>

                <button type="button" class="yuv-add-item-btn" id="yuv-sd-add-item">
                    ➕ Dodaj učesnika ručno
                </button>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Tab switching
        $('.yuv-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            $('.yuv-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.yuv-tab-content').removeClass('active');
            $(`.yuv-tab-content[data-tab="${tab}"]`).addClass('active');
        });
    });
    </script>
    <?php
}

/**
 * Render a single showdown item row
 */
function yuv_render_showdown_item_row($index, $name, $description, $image_id, $image_url) {
    if ($image_id && !$image_url) {
        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
    }
    $has_image = !empty($image_url);
    ?>
    <li class="yuv-item-row" data-index="<?php echo esc_attr($index); ?>">
        <div class="yuv-item-image-col">
            <div class="yuv-item-image-preview <?php echo $has_image ? '' : 'empty'; ?>">
                <?php if ($has_image): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>">
                <?php else: ?>
                    Slika
                <?php endif; ?>
            </div>
            <button type="button" class="button yuv-select-image-btn">
                <?php echo $has_image ? 'Promeni' : 'Dodaj'; ?>
            </button>
            <input type="hidden" name="yuv_sd_items[<?php echo esc_attr($index); ?>][image_id]" value="<?php echo esc_attr($image_id); ?>">
            <input type="hidden" name="yuv_sd_items[<?php echo esc_attr($index); ?>][image_url]" value="<?php echo esc_url($image_url); ?>">
        </div>
        <div class="yuv-item-fields">
            <input type="text" 
                   name="yuv_sd_items[<?php echo esc_attr($index); ?>][name]" 
                   placeholder="Ime učesnika" 
                   value="<?php echo esc_attr($name); ?>">
            <textarea 
                name="yuv_sd_items[<?php echo esc_attr($index); ?>][description]" 
                placeholder="Kratak opis"><?php echo esc_textarea($description); ?></textarea>
        </div>
        <button type="button" class="yuv-item-remove" title="Obriši">×</button>
    </li>
    <?php
}

/**
 * Save showdown meta
 */
function yuv_save_showdown_meta($post_id) {
    if (!isset($_POST['yuv_showdown_meta_nonce']) || 
        !wp_verify_nonce($_POST['yuv_showdown_meta_nonce'], 'yuv_showdown_meta_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'yuv_showdown') return;

    // Save status
    if (isset($_POST['yuv_showdown_status'])) {
        $new_status = sanitize_text_field($_POST['yuv_showdown_status']);
        
        // If setting to active, deactivate all other showdowns
        if ($new_status === 'active') {
            $other_active = get_posts([
                'post_type'      => 'yuv_showdown',
                'posts_per_page' => -1,
                'post__not_in'   => [$post_id],
                'meta_query'     => [
                    ['key' => '_yuv_showdown_status', 'value' => 'active']
                ]
            ]);
            foreach ($other_active as $other) {
                update_post_meta($other->ID, '_yuv_showdown_status', 'completed');
            }
        }
        
        update_post_meta($post_id, '_yuv_showdown_status', $new_status);
    }

    // Save items
    if (isset($_POST['yuv_sd_items']) && is_array($_POST['yuv_sd_items'])) {
        $items = [];
        foreach ($_POST['yuv_sd_items'] as $item) {
            $name = sanitize_text_field($item['name'] ?? '');
            if (empty($name)) continue;
            
            $items[] = [
                'name'        => $name,
                'description' => sanitize_textarea_field($item['description'] ?? ''),
                'image_id'    => intval($item['image_id'] ?? 0),
                'image_url'   => esc_url_raw($item['image_url'] ?? ''),
            ];
        }
        update_post_meta($post_id, '_yuv_showdown_items', $items);
    }
}
add_action('save_post', 'yuv_save_showdown_meta');
