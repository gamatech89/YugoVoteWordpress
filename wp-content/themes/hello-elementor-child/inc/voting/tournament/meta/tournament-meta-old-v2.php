<?php
/**
 * Tournament Metabox - Complete Redesign
 * Clean, organized UI for tournament configuration
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
        '1.0.3',
        true
    );
    
    // Localize script with nonce
    wp_localize_script('yuv-tournament-meta-admin', 'yuvTournamentMeta', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yuv_search_items'),
        'postId' => $post->ID
    ]);

    // Get existing values
    $start_date = get_post_meta($post->ID, '_yuv_start_date', true);
    $tournament_size = (int) (get_post_meta($post->ID, '_yuv_tournament_size', true) ?: 16);
    $of_duration = (int) (get_post_meta($post->ID, '_yuv_round_duration_of', true) ?: 24);
    $qf_duration = (int) (get_post_meta($post->ID, '_yuv_round_duration_qf', true) ?: 24);
    $sf_duration = (int) (get_post_meta($post->ID, '_yuv_round_duration_sf', true) ?: 24);
    $final_duration = (int) (get_post_meta($post->ID, '_yuv_round_duration_final', true) ?: 24);
    $contestants = get_post_meta($post->ID, '_yuv_contestants', true);
    $contestants = is_array($contestants) ? $contestants : [];
    $contestants_count = count($contestants);
    $bracket_created = get_post_meta($post->ID, '_yuv_bracket_created', true);
    $bracket_lists = get_post_meta($post->ID, '_yuv_bracket_lists', true);
    $bracket_lists = is_array($bracket_lists) ? $bracket_lists : [];
    
    // Get categories for filter
    $categories = get_terms([
        'taxonomy' => 'voting_list_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    ?>
    <div class="yuv-tournament-meta">
        <style>
            .yuv-tournament-meta {
                padding: 20px;
                max-width: 1000px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            }
            
            /* Section Styling */
            .yuv-meta-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .yuv-meta-section h3 {
                margin: 0 0 20px 0;
                padding-bottom: 12px;
                border-bottom: 2px solid #2271b1;
                color: #1d2327;
                font-size: 16px;
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
                font-size: 14px;
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
                line-height: 1.5;
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
                font-size: 13px;
                font-style: italic;
            }
            
            /* Rounds Grid */
            .yuv-rounds-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
                margin-top: 16px;
            }
            .yuv-round-field {
                display: flex;
                flex-direction: column;
            }
            .yuv-round-field label {
                font-weight: 600;
                margin-bottom: 8px;
                font-size: 13px;
                color: #1d2327;
            }
            .yuv-round-field input {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-round-field input:focus {
                border-color: #2271b1;
                outline: none;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .yuv-round-field small {
                margin-top: 6px;
                color: #646970;
                font-size: 12px;
            }
            
            /* Contestants Section */
            .yuv-contestants-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
                flex-wrap: wrap;
                gap: 12px;
            }
            .yuv-contestant-counter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                background: #f0f0f1;
                border-radius: 4px;
                font-weight: 600;
                font-size: 14px;
            }
            .yuv-contestant-counter .count {
                color: #2271b1;
                font-size: 20px;
            }
            .yuv-contestant-counter .max {
                color: #50575e;
            }
            
            .yuv-add-contestant-btn {
                background: #2271b1;
                color: #fff;
                border: none;
                padding: 10px 18px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: all 0.2s;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .yuv-add-contestant-btn:hover:not(:disabled) {
                background: #135e96;
                box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            }
            .yuv-add-contestant-btn:disabled {
                background: #dcdcde;
                cursor: not-allowed;
                box-shadow: none;
            }
            
            /* Category Filter & Search */
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
                padding: 12px 16px;
                border: 2px solid #2271b1;
                border-radius: 4px;
                font-size: 14px;
                transition: all 0.2s;
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
                padding: 12px;
                border-bottom: 1px solid #f0f0f1;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: background 0.15s;
            }
            .yuv-search-result-item:last-child {
                border-bottom: none;
            }
            .yuv-search-result-item:hover {
                background: #f6f7f7;
            }
            .yuv-search-result-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 4px;
                flex-shrink: 0;
            }
            .yuv-search-result-info h4 {
                margin: 0 0 4px 0;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            .yuv-search-result-info p {
                margin: 0;
                font-size: 12px;
                color: #646970;
                line-height: 1.4;
            }
            
            /* Contestant List */
            .yuv-contestant-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .yuv-contestant-item {
                display: flex;
                gap: 16px;
                margin-bottom: 16px;
                padding: 16px;
                background: #f9f9f9;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                position: relative;
                transition: all 0.15s;
            }
            .yuv-contestant-item:hover {
                background: #f6f7f7;
                border-color: #c3c4c7;
            }
            .yuv-contestant-image-col {
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .yuv-contestant-image-preview {
                width: 100px;
                height: 100px;
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
                font-size: 11px;
                text-align: center;
                padding: 8px;
            }
            .yuv-select-image-btn {
                width: 100px;
                padding: 6px 10px;
                font-size: 12px;
                text-align: center;
            }
            .yuv-contestant-fields {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .yuv-contestant-fields input[type="text"],
            .yuv-contestant-fields textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                font-size: 14px;
                font-family: inherit;
            }
            .yuv-contestant-fields input[type="text"]:focus,
            .yuv-contestant-fields textarea:focus {
                outline: none;
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .yuv-contestant-fields textarea {
                min-height: 70px;
                resize: vertical;
            }
            .yuv-contestant-remove {
                position: absolute;
                top: 14px;
                right: 14px;
                background: #dc3545;
                color: #fff;
                border: none;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .yuv-contestant-remove:hover {
                background: #a02622;
                transform: scale(1.05);
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            /* Bracket Status */
            .yuv-bracket-status {
                padding: 16px 20px;
                background: #e7f3ff;
                border-left: 4px solid #2271b1;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .yuv-bracket-status.created {
                background: #d4edda;
                border-color: #28a745;
            }
            .yuv-bracket-status h3 {
                margin: 0 0 10px 0;
                font-size: 15px;
                font-weight: 600;
                color: #1d2327;
                border: none;
                padding: 0;
            }
            .yuv-bracket-status p {
                margin: 0;
                font-size: 14px;
            }
            .yuv-bracket-list {
                margin-top: 12px;
            }
            .yuv-bracket-list h4 {
                margin: 12px 0 8px 0;
                font-size: 13px;
                font-weight: 600;
                color: #1d2327;
            }
            .yuv-bracket-link {
                display: inline-block;
                padding: 6px 12px;
                background: #2271b1;
                color: #fff;
                text-decoration: none;
                border-radius: 3px;
                margin-right: 6px;
                margin-bottom: 6px;
                font-size: 12px;
                transition: background 0.2s;
            }
            .yuv-bracket-link:hover {
                background: #135e96;
                color: #fff;
            }
            .yuv-manual-advance {
                margin-top: 16px;
                padding: 12px;
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                border-radius: 4px;
            }
            .yuv-manual-advance h4 {
                margin: 0 0 8px 0;
                font-size: 14px;
                font-weight: 600;
            }
            .yuv-manual-advance .description {
                font-size: 13px;
                color: #646970;
                margin: 8px 0 0 0;
            }
        </style>

        <?php if ($bracket_created && !empty($bracket_lists)): ?>
            <div class="yuv-bracket-status created">
                <h3>✅ Bracket kreiran</h3>
                <p><strong>Turnir je u toku!</strong></p>
                
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

                <div class="yuv-manual-advance">
                    <h4>⚙️ Ručna kontrola</h4>
                    <button type="button" id="yuv-manual-advance-btn" class="button button-secondary">
                        Pokreni Napredovanje Odmah (Bypass Cron)
                    </button>
                    <p class="description">Ovo će proveriti sve mečeve i pomeriti pobednike u sledeće runde.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="yuv-bracket-status">
                <h3>⚠️ Bracket još nije kreiran</h3>
                <p class="yuv-bracket-info">
                    Nakon što dodate <?php echo $tournament_size; ?> takmičara i datum početka, 
                    kliknite "Publish" ili "Update" da bi se automatski kreirao bracket.
                </p>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Tournament Configuration -->
        <div class="yuv-meta-section">
            <h3>⚙️ Osnovna konfiguracija</h3>
            
            <!-- Tournament Size - FIRST! -->
            <div class="yuv-meta-row">
                <label for="yuv_tournament_size">🏆 Broj takmičara:</label>
                <select id="yuv_tournament_size" name="yuv_tournament_size" <?php echo $bracket_created ? 'disabled' : ''; ?>>
                    <option value="8" <?php selected($tournament_size, 8); ?>>8 takmičara (3 kruga)</option>
                    <option value="16" <?php selected($tournament_size, 16); ?>>16 takmičara (4 kruga)</option>
                </select>
                <?php if ($bracket_created): ?>
                    <p class="description">Broj takmičara se ne može menjati nakon kreiranja bracket-a.</p>
                    <input type="hidden" name="yuv_tournament_size" value="<?php echo $tournament_size; ?>">
                <?php else: ?>
                    <p class="description" id="tournament-size-info">
                        <?php echo $tournament_size == 16 ? 
                            '4 kruga: Osmina finala → Četvrtfinale → Polufinale → Finale' : 
                            '3 kruga: Četvrtfinale → Polufinale → Finale'; ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Start Date -->
            <div class="yuv-meta-row">
                <label for="yuv_start_date">📅 Datum početka turnira:</label>
                <input type="datetime-local" 
                       id="yuv_start_date" 
                       name="yuv_start_date" 
                       value="<?php echo esc_attr($start_date); ?>"
                       <?php echo $bracket_created ? 'readonly' : ''; ?>>
                <?php if ($bracket_created): ?>
                    <p class="description">Datum se ne može menjati nakon kreiranja bracket-a.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 2: Round Durations -->
        <div class="yuv-meta-section">
            <h3>⏱️ Trajanje krugova (u satima)</h3>
            
            <div class="yuv-rounds-grid">
                <!-- Octofinals (only for 16 players) -->
                <div class="yuv-round-field" id="of-duration-field" style="<?php echo $tournament_size == 8 ? 'display:none;' : ''; ?>">
                    <label for="yuv_round_duration_of">Osmina finala</label>
                    <input type="number" 
                           id="yuv_round_duration_of" 
                           name="yuv_round_duration_of" 
                           value="<?php echo esc_attr($of_duration); ?>" 
                           min="1"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <small>Krug 1: 8 mečeva istovremeno</small>
                </div>

                <!-- Quarterfinals -->
                <div class="yuv-round-field">
                    <label for="yuv_round_duration_qf">Četvrtfinale</label>
                    <input type="number" 
                           id="yuv_round_duration_qf" 
                           name="yuv_round_duration_qf" 
                           value="<?php echo esc_attr($qf_duration); ?>" 
                           min="1"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <small id="qf-round-label"><?php echo $tournament_size == 16 ? 'Krug 2' : 'Krug 1'; ?>: 4 meča istovremeno</small>
                </div>

                <!-- Semifinals -->
                <div class="yuv-round-field">
                    <label for="yuv_round_duration_sf">Polufinale</label>
                    <input type="number" 
                           id="yuv_round_duration_sf" 
                           name="yuv_round_duration_sf" 
                           value="<?php echo esc_attr($sf_duration); ?>" 
                           min="1"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <small id="sf-round-label"><?php echo $tournament_size == 16 ? 'Krug 3' : 'Krug 2'; ?>: 2 meča istovremeno</small>
                </div>

                <!-- Final -->
                <div class="yuv-round-field">
                    <label for="yuv_round_duration_final">Finale</label>
                    <input type="number" 
                           id="yuv_round_duration_final" 
                           name="yuv_round_duration_final" 
                           value="<?php echo esc_attr($final_duration); ?>" 
                           min="1"
                           <?php echo $bracket_created ? 'readonly' : ''; ?>>
                    <small id="final-round-label"><?php echo $tournament_size == 16 ? 'Krug 4' : 'Krug 3'; ?>: Finalni meč</small>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Contestants -->
        <div class="yuv-meta-section">
            <h3>👥 Takmičari</h3>
            
            <div class="yuv-contestants-header">
                <div class="yuv-contestant-counter">
                    <span>Dodato:</span>
                    <span class="count" id="contestant-counter"><?php echo $contestants_count; ?></span>
                    <span class="max">/ <span id="contestant-max"><?php echo $tournament_size; ?></span></span>
                </div>
                
                <?php if (!$bracket_created): ?>
                    <button type="button" class="yuv-add-contestant-btn" id="yuv-add-contestant-btn">
                        + Dodaj novog takmičara
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!$bracket_created): ?>
                <!-- Category Filter -->
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
                
                <!-- Search Existing Voting Items -->
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
                // Render existing contestants
                if (!empty($contestants)) {
                    foreach ($contestants as $index => $contestant) {
                        yuv_render_contestant_row(
                            $index,
                            $contestant['name'] ?? '',
                            $contestant['description'] ?? '',
                            $contestant['image_id'] ?? '',
                            $contestant['image_url'] ?? '',
                            $bracket_created
                        );
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Helper function to render a contestant row
 */
function yuv_render_contestant_row($index, $name, $description, $image_id, $image_url, $readonly = false) {
    // Get image URL from ID if not provided
    if ($image_id && !$image_url) {
        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
    }
    
    $has_image = !empty($image_url);
    ?>
    <li class="yuv-contestant-item" data-index="<?php echo esc_attr($index); ?>">
        <div class="yuv-contestant-image-col">
            <div class="yuv-contestant-image-preview <?php echo $has_image ? '' : 'empty'; ?>">
                <?php if ($has_image): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>">
                <?php else: ?>
                    Nema slike
                <?php endif; ?>
            </div>
            <?php if (!$readonly): ?>
                <button type="button" class="button yuv-select-image-btn">
                    <?php echo $has_image ? 'Promeni sliku' : 'Izbor slike'; ?>
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
                placeholder="Kratak opis / biografija"
                <?php echo $readonly ? 'readonly' : ''; ?>><?php echo esc_textarea($description); ?></textarea>
        </div>

        <?php if (!$readonly): ?>
            <button type="button" class="yuv-contestant-remove" title="Obriši kandidata">×</button>
        <?php endif; ?>
    </li>
    <?php
}

/**
 * Save tournament meta
 */
function yuv_save_tournament_meta($post_id) {
    // Security checks
    if (!isset($_POST['yuv_tournament_meta_nonce']) || 
        !wp_verify_nonce($_POST['yuv_tournament_meta_nonce'], 'yuv_tournament_meta_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'yuv_tournament') return;

    $bracket_created = get_post_meta($post_id, '_yuv_bracket_created', true);

    // Save start date
    if (isset($_POST['yuv_start_date'])) {
        update_post_meta($post_id, '_yuv_start_date', sanitize_text_field($_POST['yuv_start_date']));
    }

    // Save round durations
    if (isset($_POST['yuv_round_duration_of'])) {
        update_post_meta($post_id, '_yuv_round_duration_of', intval($_POST['yuv_round_duration_of']));
    }
    if (isset($_POST['yuv_round_duration_qf'])) {
        update_post_meta($post_id, '_yuv_round_duration_qf', intval($_POST['yuv_round_duration_qf']));
    }
    if (isset($_POST['yuv_round_duration_sf'])) {
        update_post_meta($post_id, '_yuv_round_duration_sf', intval($_POST['yuv_round_duration_sf']));
    }
    if (isset($_POST['yuv_round_duration_final'])) {
        update_post_meta($post_id, '_yuv_round_duration_final', intval($_POST['yuv_round_duration_final']));
    }

    // Save tournament size
    if (isset($_POST['yuv_tournament_size'])) {
        update_post_meta($post_id, '_yuv_tournament_size', intval($_POST['yuv_tournament_size']));
    }

    // Save contestants
    if (isset($_POST['yuv_contestants']) && is_array($_POST['yuv_contestants'])) {
        $contestants = [];
        foreach ($_POST['yuv_contestants'] as $contestant) {
            // Only save if name is not empty
            if (!empty($contestant['name'])) {
                $contestants[] = [
                    'name' => sanitize_text_field($contestant['name']),
                    'description' => sanitize_textarea_field($contestant['description'] ?? ''),
                    'image_id' => intval($contestant['image_id'] ?? 0),
                    'image_url' => esc_url_raw($contestant['image_url'] ?? ''),
                ];
            }
        }
        update_post_meta($post_id, '_yuv_contestants', $contestants);
    }

    // Create bracket if not already created
    if (!$bracket_created) {
        $tournament_size = (int) get_post_meta($post_id, '_yuv_tournament_size', true) ?: 16;
        $contestants = get_post_meta($post_id, '_yuv_contestants', true);
        $start_date = get_post_meta($post_id, '_yuv_start_date', true);
        
        // Only create bracket if we have the right number of contestants and a start date
        if (is_array($contestants) && count($contestants) === $tournament_size && !empty($start_date)) {
            require_once get_stylesheet_directory() . '/inc/voting/tournament/classes/class-tournament-manager.php';
            $manager = new Tournament_Manager();
            $result = $manager->create_bracket($post_id);
            
            if ($result['success']) {
                update_post_meta($post_id, '_yuv_bracket_created', true);
                update_post_meta($post_id, '_yuv_bracket_lists', $result['lists']);
            }
        }
    }
}
add_action('save_post', 'yuv_save_tournament_meta');
