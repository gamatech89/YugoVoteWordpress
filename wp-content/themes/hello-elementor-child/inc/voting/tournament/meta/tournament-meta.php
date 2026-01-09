<?php
/**
 * Tournament Metabox - Tab-based UI
 * Clean, organized configuration with tabs
 */

if (!defined('ABSPATH')) exit;

function yuv_add_tournament_metabox() {
    add_meta_box(
        'yuv_tournament_settings',
        'Podešavanja Turnira',
        'yuv_tournament_metabox_callback',
        'yuv_tournament',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuv_add_tournament_metabox');

function yuv_tournament_metabox_callback($post) {
    wp_nonce_field('yuv_tournament_meta_save', 'yuv_tournament_meta_nonce');

    // Enqueue media uploader
    wp_enqueue_media();

    // Enqueue admin script
    wp_enqueue_script(
        'yuv-tournament-meta-admin',
        get_stylesheet_directory_uri() . '/js/admin/admin-tournament-meta.js',
        ['jquery'],
        '1.0.4',
        true
    );
    
    // Localize script
    wp_localize_script('yuv-tournament-meta-admin', 'yuvTournamentMeta', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yuv_search_items'),
        'postId' => $post->ID
    ]);

    // Get existing values
    $start_date = get_post_meta($post->ID, '_yuv_start_date', true);
    $tournament_size = (int) (get_post_meta($post->ID, '_yuv_tournament_size', true) ?: 16);
    $round_interval = (int) (get_post_meta($post->ID, '_yuv_round_interval', true) ?: 24);
    $contestants = get_post_meta($post->ID, '_yuv_contestants', true);
    $contestants = is_array($contestants) ? $contestants : [];
    $contestants_filled = count(array_filter($contestants, function($c) { return !empty($c['name']); }));
    $bracket_created = get_post_meta($post->ID, '_yuv_bracket_created', true);
    $bracket_lists = get_post_meta($post->ID, '_yuv_bracket_lists', true);
    $bracket_lists = is_array($bracket_lists) ? $bracket_lists : [];
    
    // Get categories for filter (voting_item_category for filtering voting_items)
    $categories = get_terms([
        'taxonomy' => 'voting_item_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    ?>
    <div class="yuv-tournament-meta">
        <style>
            .yuv-tournament-meta {
                padding: 15px 20px 20px;
                max-width: 1100px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            /* Tab Navigation */
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
            .yuv-tab-btn:hover {
                color: #2271b1;
                background: #f6f7f7;
            }
            .yuv-tab-btn.active {
                color: #2271b1;
                border-bottom-color: #2271b1;
                background: #fff;
            }
            
            /* Tab Content */
            .yuv-tab-content {
                display: none;
                animation: fadeIn 0.3s;
            }
            .yuv-tab-content.active {
                display: block;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Section Styling */
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
            
            /* Form Elements */
            .yuv-meta-row {
                margin-bottom: 20px;
            }
            .yuv-meta-row:last-child {
                margin-bottom: 0;
            }
            .yuv-meta-row label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #1d2327;
                font-size: 13px;
            }
            .yuv-meta-row select,
            .yuv-meta-row input[type="datetime-local"],
            .yuv-meta-row input[type="number"] {
                width: 100%;
                max-width: 400px;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-meta-row select:focus,
            .yuv-meta-row input:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .yuv-meta-row .description {
                margin: 6px 0 0 0;
                color: #646970;
                font-size: 12px;
                font-style: italic;
            }
            
            /* Rounds Info */
            .yuv-rounds-info {
                background: #f0f0f1;
                padding: 16px;
                border-radius: 4px;
                margin-top: 16px;
            }
            .yuv-rounds-info h4 {
                margin: 0 0 12px 0;
                font-size: 13px;
                font-weight: 600;
                color: #1d2327;
            }
            .yuv-round-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #dcdcde;
            }
            .yuv-round-item:last-child {
                border-bottom: none;
            }
            .yuv-round-name {
                font-weight: 600;
                color: #1d2327;
            }
            .yuv-round-time {
                color: #646970;
                font-size: 12px;
            }
            
            /* Contestants */
            .yuv-contestants-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
                padding-bottom: 12px;
                border-bottom: 1px solid #dcdcde;
            }
            .yuv-contestant-counter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: #f0f0f1;
                border-radius: 4px;
                font-weight: 600;
                font-size: 13px;
            }
            .yuv-contestant-counter .count {
                color: #2271b1;
                font-size: 18px;
            }
            .yuv-contestant-counter .max {
                color: #646970;
            }
            
            .yuv-category-filter {
                margin-bottom: 12px;
            }
            .yuv-category-filter label {
                display: inline-block;
                margin-right: 8px;
                font-weight: 600;
                font-size: 13px;
            }
            .yuv-category-filter select {
                min-width: 250px;
                padding: 6px 10px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            
            .yuv-search-wrapper {
                position: relative;
                margin-bottom: 16px;
            }
            .yuv-search-input {
                width: 100%;
                padding: 10px 14px;
                border: 2px solid #2271b1;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-search-input:focus {
                outline: none;
                border-color: #135e96;
                box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
            }
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
            .yuv-search-result-item:hover {
                background: #f6f7f7;
            }
            .yuv-search-result-image {
                width: 45px;
                height: 45px;
                object-fit: cover;
                border-radius: 3px;
                flex-shrink: 0;
            }
            .yuv-search-result-info h4 {
                margin: 0 0 2px 0;
                font-size: 13px;
                font-weight: 600;
            }
            .yuv-search-result-info p {
                margin: 0;
                font-size: 11px;
                color: #646970;
            }
            
            .yuv-contestant-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 12px;
            }
            .yuv-contestant-item {
                display: flex;
                gap: 12px;
                padding: 12px;
                background: #f9f9f9;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                position: relative;
            }
            .yuv-contestant-item.empty {
                opacity: 0.6;
            }
            .yuv-contestant-item:hover {
                background: #f6f7f7;
                border-color: #c3c4c7;
            }
            .yuv-contestant-image-col {
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .yuv-contestant-image-preview {
                width: 80px;
                height: 80px;
                border: 2px dashed #c3c4c7;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                overflow: hidden;
            }
            .yuv-contestant-image-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .yuv-contestant-image-preview.empty {
                color: #a7aaad;
                font-size: 10px;
                text-align: center;
                padding: 6px;
            }
            .yuv-select-image-btn {
                width: 80px;
                padding: 4px 8px;
                font-size: 11px;
                text-align: center;
            }
            .yuv-contestant-fields {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .yuv-contestant-fields input[type="text"],
            .yuv-contestant-fields textarea {
                width: 100%;
                padding: 7px 10px;
                border: 1px solid #c3c4c7;
                border-radius: 3px;
                font-size: 13px;
            }
            .yuv-contestant-fields input[type="text"]:focus,
            .yuv-contestant-fields textarea:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .yuv-contestant-fields textarea {
                min-height: 50px;
                resize: vertical;
                font-family: inherit;
            }
            .yuv-contestant-clear {
                position: absolute;
                top: 10px;
                right: 10px;
                background: #dc3545;
                color: #fff;
                border: none;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            .yuv-contestant-clear:hover {
                background: #a02622;
                transform: scale(1.1);
            }
            
            /* Bracket Status */
            .yuv-bracket-status {
                padding: 16px 20px;
                background: #e7f3ff;
                border-left: 4px solid #2271b1;
                border-radius: 4px;
                margin-bottom: 16px;
            }
            .yuv-bracket-status.created {
                background: #d4edda;
                border-color: #28a745;
            }
            .yuv-bracket-status h3 {
                margin: 0 0 8px 0;
                font-size: 14px;
                font-weight: 600;
            }
            .yuv-bracket-status p {
                margin: 0;
                font-size: 13px;
            }
            .yuv-bracket-list {
                margin-top: 12px;
            }
            .yuv-bracket-list h4 {
                margin: 10px 0 6px 0;
                font-size: 12px;
                font-weight: 600;
            }
            .yuv-bracket-link {
                display: inline-block;
                padding: 5px 10px;
                background: #2271b1;
                color: #fff;
                text-decoration: none;
                border-radius: 3px;
                margin-right: 5px;
                margin-bottom: 5px;
                font-size: 11px;
            }
            .yuv-bracket-link:hover {
                background: #135e96;
                color: #fff;
            }
        </style>

        <?php if ($bracket_created && !empty($bracket_lists)): ?>
            <div class="yuv-bracket-status created">
                <h3>✅ Bracket kreiran</h3>
                <p><strong>Turnir je aktivan i ne može se menjati!</strong></p>
            </div>
        <?php else: ?>
            <div class="yuv-bracket-status">
                <h3>⚠️ Bracket još nije kreiran</h3>
                <p>Popunite sve podatke i kliknite "Publish" ili "Update".</p>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="yuv-tab-nav">
            <button type="button" class="yuv-tab-btn active" data-tab="config">
                ⚙️ Osnovna konfiguracija
            </button>
            <button type="button" class="yuv-tab-btn" data-tab="schedule">
                ⏱️ Raspored krugova
            </button>
            <button type="button" class="yuv-tab-btn" data-tab="contestants">
                👥 Takmičari (<span id="contestant-counter"><?php echo $contestants_filled; ?></span>/<span id="contestant-max"><?php echo $tournament_size; ?></span>)
            </button>
            <?php if ($bracket_created): ?>
            <button type="button" class="yuv-tab-btn" data-tab="bracket">
                🏆 Bracket
            </button>
            <?php endif; ?>
        </div>

        <!-- TAB 1: Osnovna konfiguracija -->
        <div class="yuv-tab-content active" data-tab="config">
            <div class="yuv-meta-section">
                <div class="yuv-meta-row">
                    <label for="yuv_tournament_size">🏆 Broj takmičara:</label>
                    <select id="yuv_tournament_size" name="yuv_tournament_size" <?php echo $bracket_created ? 'disabled' : ''; ?>>
                        <option value="8" <?php selected($tournament_size, 8); ?>>8 takmičara (3 kruga)</option>
                        <option value="16" <?php selected($tournament_size, 16); ?>>16 takmičara (4 kruga)</option>
                    </select>
                    <?php if ($bracket_created): ?>
                        <p class="description">Ne može se menjati nakon kreiranja bracket-a.</p>
                        <input type="hidden" name="yuv_tournament_size" value="<?php echo $tournament_size; ?>">
                    <?php else: ?>
                        <p class="description" id="tournament-size-info">
                            <?php echo $tournament_size == 16 ? 
                                '4 kruga: Osmina finala → Četvrtfinale → Polufinale → Finale' : 
                                '3 kruga: Četvrtfinale → Polufinale → Finale'; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="yuv-meta-row">
                    <label for="yuv_start_date">📅 Datum i vreme početka turnira:</label>
                    <input type="datetime-local" 
                           id="yuv_start_date" 
                           name="yuv_start_date" 
                           value="<?php echo esc_attr($start_date); ?>"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <?php if ($bracket_created): ?>
                        <p class="description">Ne može se menjati nakon kreiranja bracket-a.</p>
                    <?php else: ?>
                        <p class="description">Postavite tačan datum i vreme kada turnir počinje.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB 2: Raspored krugova -->
        <div class="yuv-tab-content" data-tab="schedule">
            <div class="yuv-meta-section">
                <div class="yuv-meta-row">
                    <label for="yuv_round_interval">⏱️ Interval između krugova (u satima):</label>
                    <input type="number" 
                           id="yuv_round_interval" 
                           name="yuv_round_interval" 
                           value="<?php echo esc_attr($round_interval); ?>" 
                           min="1"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <p class="description">
                        Svaki novi krug počinje nakon isteka ovog vremena. Preporučeno: 24h ili više.
                    </p>
                </div>

                <?php if (!empty($start_date)): ?>
                <div class="yuv-rounds-info" id="rounds-schedule-preview">
                    <h4>📅 Automatski raspored krugova:</h4>
                    <?php
                    $start_timestamp = strtotime($start_date);
                    $interval_seconds = $round_interval * 3600;
                    $rounds = [];
                    
                    if ($tournament_size == 16) {
                        $rounds = [
                            ['name' => 'Osmina finala', 'offset' => 0],
                            ['name' => 'Četvrtfinale', 'offset' => 1],
                            ['name' => 'Polufinale', 'offset' => 2],
                            ['name' => 'Finale', 'offset' => 3]
                        ];
                    } else {
                        $rounds = [
                            ['name' => 'Četvrtfinale', 'offset' => 0],
                            ['name' => 'Polufinale', 'offset' => 1],
                            ['name' => 'Finale', 'offset' => 2]
                        ];
                    }
                    
                    foreach ($rounds as $round) {
                        $round_start = $start_timestamp + ($round['offset'] * $interval_seconds);
                        $round_end = $round_start + $interval_seconds;
                        ?>
                        <div class="yuv-round-item">
                            <span class="yuv-round-name"><?php echo $round['name']; ?></span>
                            <span class="yuv-round-time">
                                <?php echo date('d.m.Y H:i', $round_start); ?> - <?php echo date('d.m.Y H:i', $round_end); ?>
                            </span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php else: ?>
                    <p class="description" style="margin-top: 16px; padding: 12px; background: #fff3cd; border-left: 3px solid #ffc107;">
                        ⚠️ Postavite datum početka turnira u "Osnovna konfiguracija" tabu da bi se prikazao raspored.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 3: Takmičari -->
        <div class="yuv-tab-content" data-tab="contestants">
            <div class="yuv-meta-section">
                <?php if (!$bracket_created): ?>
                    <div class="yuv-category-filter">
                        <label for="yuv-category-filter">Filteruj po kategoriji:</label>
                        <select id="yuv-category-filter">
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
                               id="yuv-candidate-search" 
                               class="yuv-search-input" 
                               placeholder="🔍 Pretraži postojeće kandidate..."
                               autocomplete="off">
                        <div id="yuv-search-results" class="yuv-search-results"></div>
                    </div>
                <?php endif; ?>

                <ul class="yuv-contestant-list" id="yuv-contestant-list">
                    <?php 
                    // Generate exactly tournament_size rows
                    for ($i = 0; $i < $tournament_size; $i++) {
                        $contestant = $contestants[$i] ?? [];
                        yuv_render_contestant_row(
                            $i,
                            $contestant['name'] ?? '',
                            $contestant['description'] ?? '',
                            $contestant['image_id'] ?? '',
                            $contestant['image_url'] ?? '',
                            $bracket_created
                        );
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- TAB 4: Bracket (only if created) -->
        <?php if ($bracket_created && !empty($bracket_lists)): ?>
        <div class="yuv-tab-content" data-tab="bracket">
            <div class="yuv-meta-section">
                <?php if (!empty($bracket_lists['of'])): ?>
                <div class="yuv-bracket-list">
                    <h4>Osmina finala:</h4>
                    <?php foreach ($bracket_lists['of'] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            OF Meč <?php echo get_post_meta($list_id, '_yuv_match_number', true); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="yuv-bracket-list">
                    <h4>Četvrtfinale:</h4>
                    <?php foreach ($bracket_lists['qf'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            QF Meč <?php echo get_post_meta($list_id, '_yuv_match_number', true); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="yuv-bracket-list">
                    <h4>Polufinale:</h4>
                    <?php foreach ($bracket_lists['sf'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            SF Meč <?php echo get_post_meta($list_id, '_yuv_match_number', true); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="yuv-bracket-list">
                    <h4>Finale:</h4>
                    <?php foreach ($bracket_lists['final'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            FINALE
                        </a>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 20px; padding: 12px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 13px;">⚙️ Ručna kontrola</h4>
                    <button type="button" id="yuv-manual-advance-btn" class="button button-secondary">
                        Pokreni Napredovanje Odmah
                    </button>
                    <p class="description" style="margin: 8px 0 0 0;">
                        Proveri sve mečeve i pomeri pobednike u sledeće runde.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

        // Tournament size change handler
        $('#yuv_tournament_size').on('change', function() {
            const newSize = parseInt($(this).val());
            $('#contestant-max').text(newSize);
            $('#tournament-size-info').text(
                newSize === 16 ? 
                '4 kruga: Osmina finala → Četvrtfinale → Polufinale → Finale' :
                '3 kruga: Četvrtfinale → Polufinale → Finale'
            );
            
            // Reload page to regenerate contestant slots
            if (confirm('Promena broja takmičara će osvežiti stranicu. Nesačuvane izmene će biti izgubljene. Nastaviti?')) {
                // Save tournament size first, then reload
                $('#yuv_tournament_size').closest('form').append('<input type="hidden" name="quick_save_size" value="1">');
                $('#publish').click();
            }
        });

        // Update schedule preview when interval changes
        $('#yuv_round_interval').on('input', function() {
            // This would require AJAX or page reload to update
            // For now, just show message
            if (!$('#schedule-update-notice').length) {
                $(this).after('<p id="schedule-update-notice" class="description" style="color: #d63638;">Sačuvajte izmene da bi se ažurirao raspored.</p>');
            }
        });
    });
    </script>
    <?php
}

/**
 * Helper function to render a contestant row
 */
function yuv_render_contestant_row($index, $name, $description, $image_id, $image_url, $readonly = false) {
    if ($image_id && !$image_url) {
        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
    }
    
    $has_image = !empty($image_url);
    $is_empty = empty($name);
    ?>
    <li class="yuv-contestant-item <?php echo $is_empty ? 'empty' : ''; ?>" data-index="<?php echo esc_attr($index); ?>">
        <div class="yuv-contestant-image-col">
            <div class="yuv-contestant-image-preview <?php echo $has_image ? '' : 'empty'; ?>">
                <?php if ($has_image): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>">
                <?php else: ?>
                    Slika
                <?php endif; ?>
            </div>
            <?php if (!$readonly): ?>
                <button type="button" class="button yuv-select-image-btn">
                    <?php echo $has_image ? 'Promeni' : 'Dodaj'; ?>
                </button>
            <?php endif; ?>
            <input type="hidden" 
                   name="yuv_contestants[<?php echo esc_attr($index); ?>][image_id]" 
                   value="<?php echo esc_attr($image_id); ?>">
            <input type="hidden" 
                   name="yuv_contestants[<?php echo esc_attr($index); ?>][image_url]" 
                   value="<?php echo esc_url($image_url); ?>">
        </div>

        <div class="yuv-contestant-fields">
            <input type="text" 
                   name="yuv_contestants[<?php echo esc_attr($index); ?>][name]" 
                   placeholder="Ime takmičara" 
                   value="<?php echo esc_attr($name); ?>"
                   <?php echo $readonly ? 'readonly' : ''; ?>>
            
            <textarea 
                name="yuv_contestants[<?php echo esc_attr($index); ?>][description]" 
                placeholder="Kratak opis"
                <?php echo $readonly ? 'readonly' : ''; ?>><?php echo esc_textarea($description); ?></textarea>
        </div>

        <?php if (!$readonly && !$is_empty): ?>
            <button type="button" class="yuv-contestant-clear" title="Obriši">×</button>
        <?php endif; ?>
    </li>
    <?php
}

/**
 * Save tournament meta
 */
function yuv_save_tournament_meta($post_id) {
    if (!isset($_POST['yuv_tournament_meta_nonce']) || 
        !wp_verify_nonce($_POST['yuv_tournament_meta_nonce'], 'yuv_tournament_meta_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'yuv_tournament') return;

    $bracket_created = get_post_meta($post_id, '_yuv_bracket_created', true);

    // Save values
    if (isset($_POST['yuv_start_date'])) {
        update_post_meta($post_id, '_yuv_start_date', sanitize_text_field($_POST['yuv_start_date']));
    }

    if (isset($_POST['yuv_round_interval'])) {
        update_post_meta($post_id, '_yuv_round_interval', intval($_POST['yuv_round_interval']));
    }

    if (isset($_POST['yuv_tournament_size'])) {
        update_post_meta($post_id, '_yuv_tournament_size', intval($_POST['yuv_tournament_size']));
    }

    if (isset($_POST['yuv_contestants']) && is_array($_POST['yuv_contestants'])) {
        $contestants = [];
        foreach ($_POST['yuv_contestants'] as $contestant) {
            // Save all slots, even empty ones
            $contestants[] = [
                'name' => sanitize_text_field($contestant['name'] ?? ''),
                'description' => sanitize_textarea_field($contestant['description'] ?? ''),
                'image_id' => intval($contestant['image_id'] ?? 0),
                'image_url' => esc_url_raw($contestant['image_url'] ?? ''),
            ];
        }
        update_post_meta($post_id, '_yuv_contestants', $contestants);
    }

    // Create bracket if conditions met
    if (!$bracket_created) {
        $tournament_size = (int) get_post_meta($post_id, '_yuv_tournament_size', true) ?: 16;
        $contestants = get_post_meta($post_id, '_yuv_contestants', true);
        $start_date = get_post_meta($post_id, '_yuv_start_date', true);
        
        // Count filled contestants
        $filled_count = 0;
        if (is_array($contestants)) {
            foreach ($contestants as $c) {
                if (!empty($c['name'])) $filled_count++;
            }
        }
        
        if ($filled_count === $tournament_size && !empty($start_date)) {
            require_once get_stylesheet_directory() . '/inc/voting/tournament/classes/class-tournament-manager.php';
            $manager = new YUV_Tournament_Manager();
            $result = $manager->create_bracket($post_id);
            
            if ($result['success']) {
                update_post_meta($post_id, '_yuv_bracket_created', true);
                update_post_meta($post_id, '_yuv_bracket_lists', $result['lists']);
            }
        }
    }
}
add_action('save_post', 'yuv_save_tournament_meta');
