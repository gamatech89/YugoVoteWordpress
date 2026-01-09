<?php
/**
 * Frontend List Submission
 * Allows users to create voting lists from the frontend
 */

if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [yugo_create_list]
 */
function yugo_create_list_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="ygv-card"><p>' . sprintf(
            __('Morate biti prijavljeni da biste kreirali listu. <a href="%s">Prijavite se</a>.', 'hello-elementor-child'),
            wp_login_url(get_permalink())
        ) . '</p></div>';
    }
    
    $user_id = get_current_user_id();
    
    // Check if user can create lists
    $can_create = ['can_create' => false, 'reason' => __('Moraš dostići određeni nivo.', 'hello-elementor-child')];
    if (function_exists('ygv_can_user_create_list')) {
        $can_create = ygv_can_user_create_list($user_id);
    }
    
    if (!$can_create['can_create']) {
        return '<div class="ygv-card">
            <div class="ygv-info-box">
                <span class="ygv-info-icon">' . ygv_icon('lock', 24) . '</span>
                <div class="ygv-info-content">
                    <strong>' . esc_html__('Kreiranje lista je zaključano', 'hello-elementor-child') . '</strong>
                    <p>' . esc_html($can_create['reason']) . '</p>
                    <p class="ygv-info-hint">' . esc_html__('Rešavaj kvizove i glasaj da zaradiš XP i otključaš ovu funkciju!', 'hello-elementor-child') . '</p>
                </div>
            </div>
        </div>';
    }
    
    // Get parent list categories
    $list_categories = get_terms([
        'taxonomy' => 'voting_list_category',
        'parent' => 0,
        'hide_empty' => false,
    ]);
    
    // Get voting item categories (for filtering items)
    $item_categories = get_terms([
        'taxonomy' => 'voting_item_category',
        'hide_empty' => false,
    ]);
    
    // Get level config
    $level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
    $required_level = $level_config['list_creation_category_level'] ?? 10;
    
    // Check which categories user can create in
    global $wpdb;
    $t_cat = $wpdb->prefix . 'ygv_user_category_progress';
    $available_categories = [];
    
    foreach ($list_categories as $cat) {
        $user_cat_level = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT level FROM {$t_cat} WHERE user_id = %d AND category_term_id = %d",
            $user_id,
            $cat->term_id
        )) ?: 1;
        
        if ($user_cat_level >= $required_level) {
            $available_categories[] = $cat;
        }
    }
    
    ob_start();
    ?>
    <style>
    /* Scoped styles for create list form */
    .ygv-create-list { max-width: 900px; margin: 0 auto; }
    .ygv-create-list .ygv-card { border: 1px solid #e6e7eb; border-radius: 12px; padding: 24px; background: #fff; }
    .ygv-create-list .ygv-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .ygv-create-list .ygv-card-header h2 { margin: 0; font-size: 24px; color: #1a1a1a; }
    .ygv-create-list .ygv-card-subtitle { color: #6b7280; margin: 0 0 24px; }
    .ygv-create-list .ygv-btn { display: inline-block; padding: 10px 16px; border-radius: 8px; border: 1px solid #d9dde4; background: #f8f9fb; cursor: pointer; text-decoration: none; color: #333; font-size: 14px; transition: all 0.2s; }
    .ygv-create-list .ygv-btn:hover { border-color: #6db24a; color: #6db24a; }
    .ygv-create-list .ygv-btn-primary { background: #6db24a; color: #fff !important; border-color: #6db24a; }
    .ygv-create-list .ygv-btn-primary:hover { background: #5a9940; }
    .ygv-create-list .ygv-form-row { display: flex; gap: 16px; margin-bottom: 20px; }
    .ygv-create-list .ygv-form-group { flex: 1; margin-bottom: 20px; }
    .ygv-create-list .ygv-form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
    .ygv-create-list .ygv-form-group label .required { color: #dc2626; }
    .ygv-create-list .ygv-input { width: 100%; padding: 12px 14px; border: 1px solid #d9dde4; border-radius: 8px; font-size: 14px; box-sizing: border-box; transition: border-color 0.2s; }
    .ygv-create-list .ygv-input:focus { outline: none; border-color: #6db24a; box-shadow: 0 0 0 3px rgba(109,178,74,0.1); }
    .ygv-create-list .ygv-textarea { resize: vertical; min-height: 80px; }
    .ygv-create-list .ygv-form-hint { display: block; margin-top: 6px; font-size: 13px; color: #9ca3af; }
    
    /* Item Selector */
    .ygv-create-list .ygv-item-selector { border: 1px solid #e6e7eb; border-radius: 12px; padding: 16px; background: #f8fafc; margin-bottom: 16px; overflow: hidden; }
    .ygv-create-list .ygv-item-search-row { display: flex; gap: 12px; margin-bottom: 16px; }
    .ygv-create-list .ygv-item-search-row select { width: 200px; flex-shrink: 0; }
    .ygv-create-list .ygv-item-search-row input { flex: 1; min-width: 0; }
    
    /* Available Items Grid */
    .ygv-create-list .ygv-available-items { max-height: 420px; overflow-y: auto; overflow-x: hidden; background: #fff; border: 1px solid #e6e7eb; border-radius: 10px; padding: 12px; }
    .ygv-create-list .ygv-items-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; width: 100%; box-sizing: border-box; }
    .ygv-create-list .ygv-item-card { border: 2px solid #e6e7eb; border-radius: 8px; padding: 8px; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; position: relative; display: flex; flex-direction: column; align-items: center; min-width: 0; box-sizing: border-box; }
    .ygv-create-list .ygv-item-card:hover { border-color: #6db24a; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .ygv-create-list .ygv-item-card.ygv-item-selected { border-color: #6db24a; background: #f0fdf4; }
    .ygv-create-list .ygv-item-card.ygv-item-disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
    .ygv-create-list .ygv-item-card img { width: 100%; height: 80px; object-fit: cover; border-radius: 6px; margin-bottom: 6px; display: block; }
    .ygv-create-list .ygv-item-no-thumb { width: 100%; height: 80px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 6px; flex-shrink: 0; }
    .ygv-create-list .ygv-item-title { font-size: 11px; color: #374151; font-weight: 500; line-height: 1.2; height: 26px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; width: 100%; }
    .ygv-create-list .ygv-item-check { position: absolute; top: 4px; right: 4px; background: #6db24a; color: #fff; width: 18px; height: 18px; border-radius: 50%; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    
    /* Selected Items */
    .ygv-create-list .ygv-selected-items-header { margin: 0 0 12px; padding: 12px 0; border-bottom: 2px solid #e6e7eb; }
    .ygv-create-list .ygv-selected-items-header strong { font-size: 15px; color: #1a1a1a; }
    .ygv-create-list .ygv-selected-items { min-height: 80px; border: 2px dashed #d1d5db; border-radius: 12px; padding: 16px; background: #fafbfc; }
    .ygv-create-list .ygv-empty-selection { text-align: center; color: #9ca3af; font-size: 14px; margin: 0; padding: 20px; }
    .ygv-create-list .ygv-selected-item { display: flex; align-items: center; gap: 14px; padding: 12px 14px; background: #fff; border: 1px solid #e6e7eb; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .ygv-create-list .ygv-selected-item:last-child { margin-bottom: 0; }
    .ygv-create-list .ygv-selected-num { width: 28px; height: 28px; background: linear-gradient(135deg, #6db24a 0%, #5a9940 100%); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .ygv-create-list .ygv-selected-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .ygv-create-list .ygv-selected-title { flex: 1; font-weight: 500; color: #1a1a1a; font-size: 14px; }
    .ygv-create-list .ygv-remove-item { background: #fee2e2; border: none; color: #dc2626; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
    .ygv-create-list .ygv-remove-item:hover { background: #dc2626; color: #fff; }
    
    /* Form Actions */
    .ygv-create-list .ygv-form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e6e7eb; }
    
    /* Messages */
    .ygv-create-list .ygv-message { margin-top: 20px; display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; }
    .ygv-create-list .ygv-success-banner { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
    .ygv-create-list .ygv-error-banner { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
    .ygv-create-list .ygv-message span { font-size: 20px; }
    
    /* Info Box */
    .ygv-create-list .ygv-info-box { display: flex; gap: 14px; padding: 16px; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; }
    .ygv-create-list .ygv-info-icon { font-size: 24px; }
    .ygv-create-list .ygv-info-content strong { display: block; color: #92400e; margin-bottom: 4px; }
    .ygv-create-list .ygv-info-content p { margin: 0; color: #a16207; font-size: 14px; }
    
    @media (max-width: 600px) {
        .ygv-create-list .ygv-form-row { flex-direction: column; gap: 0; }
        .ygv-create-list .ygv-item-search-row { flex-direction: column; }
        .ygv-create-list .ygv-item-search-row select { width: 100%; }
        .ygv-create-list .ygv-items-grid { grid-template-columns: repeat(2, 1fr); }
        .ygv-create-list .ygv-form-actions { flex-direction: column; }
        .ygv-create-list .ygv-btn { width: 100%; text-align: center; }
    }
    @media (min-width: 601px) and (max-width: 800px) {
        .ygv-create-list .ygv-items-grid { grid-template-columns: repeat(3, 1fr); }
    }
    </style>
    
    <div class="ygv-create-list">
        <div class="ygv-card">
            <div class="ygv-card-header">
                <h2><?php echo esc_html__('Kreiraj Novu Listu', 'hello-elementor-child'); ?></h2>
                <a href="<?php echo esc_url(ygv_account_page_url(['tab' => 'liste'])); ?>" class="ygv-btn">
                    ← <?php echo esc_html__('Nazad', 'hello-elementor-child'); ?>
                </a>
            </div>
            <p class="ygv-card-subtitle"><?php echo esc_html__('Napravi svoju Top 10 listu i podeli je sa zajednicom', 'hello-elementor-child'); ?></p>
            
            <form id="ygv-create-list-form" class="ygv-list-form" method="post">
                <?php wp_nonce_field('ygv_create_list', 'ygv_list_nonce'); ?>
                
                <div class="ygv-form-row">
                    <div class="ygv-form-group ygv-form-group-wide">
                        <label for="list_title"><?php echo esc_html__('Naslov Liste', 'hello-elementor-child'); ?> <span class="required">*</span></label>
                        <input type="text" name="list_title" id="list_title" class="ygv-input" required 
                               placeholder="<?php echo esc_attr__('npr. Top 10 Najboljih Filmova 2025', 'hello-elementor-child'); ?>">
                    </div>
                </div>
                
                <div class="ygv-form-row">
                    <div class="ygv-form-group">
                        <label for="list_category"><?php echo esc_html__('Kategorija Liste', 'hello-elementor-child'); ?> <span class="required">*</span></label>
                        <?php if (empty($available_categories)): ?>
                            <div class="ygv-info-box" style="margin: 0;">
                                <span class="ygv-info-icon"><?php ygv_icon_e('lock', 20); ?></span>
                                <div class="ygv-info-content">
                                    <strong><?php echo esc_html__('Nemaš otključane kategorije', 'hello-elementor-child'); ?></strong>
                                    <p><?php printf(esc_html__('Potreban je nivo %d u kategoriji da bi kreirao liste.', 'hello-elementor-child'), $required_level); ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <select name="list_category" id="list_category" class="ygv-input" required>
                                <option value=""><?php echo esc_html__('Izaberi kategoriju', 'hello-elementor-child'); ?></option>
                                <?php foreach ($available_categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ygv-form-group">
                        <label for="voting_scale"><?php echo esc_html__('Skala Glasanja', 'hello-elementor-child'); ?></label>
                        <select name="voting_scale" id="voting_scale" class="ygv-input">
                            <option value="10">1-10</option>
                            <option value="5">1-5</option>
                        </select>
                        <span class="ygv-form-hint"><?php echo esc_html__('Opseg ocena koje korisnici mogu dati', 'hello-elementor-child'); ?></span>
                    </div>
                </div>
                
                <div class="ygv-form-group">
                    <label for="list_description"><?php echo esc_html__('Opis (opciono)', 'hello-elementor-child'); ?></label>
                    <textarea name="list_description" id="list_description" class="ygv-input ygv-textarea" rows="3"
                              placeholder="<?php echo esc_attr__('Kratko objasni o čemu se radi tvoja lista...', 'hello-elementor-child'); ?>"></textarea>
                </div>
                
                <!-- Voting Items Selection -->
                <div class="ygv-form-group">
                    <label><?php echo esc_html__('Stavke za Glasanje', 'hello-elementor-child'); ?> <span class="required">*</span></label>
                    <p class="ygv-form-hint"><?php echo esc_html__('Izaberi 10 stavki iz postojeće baze. Korisnici će glasati za ove stavke.', 'hello-elementor-child'); ?></p>
                    
                    <div class="ygv-item-selector">
                        <div class="ygv-item-search-row">
                            <select id="ygv-item-category-filter" class="ygv-input">
                                <option value=""><?php echo esc_html__('Sve kategorije stavki', 'hello-elementor-child'); ?></option>
                                <?php foreach ($item_categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="ygv-item-search" class="ygv-input" placeholder="<?php echo esc_attr__('Pretraži stavke...', 'hello-elementor-child'); ?>">
                        </div>
                        
                        <div class="ygv-available-items" id="ygv-available-items">
                            <p class="ygv-muted"><?php echo esc_html__('Učitavanje stavki...', 'hello-elementor-child'); ?></p>
                        </div>
                    </div>
                    
                    <div class="ygv-selected-items-header">
                        <strong><?php echo esc_html__('Izabrane Stavke', 'hello-elementor-child'); ?> (<span id="ygv-selected-count">0</span>/10)</strong>
                    </div>
                    <div class="ygv-selected-items" id="ygv-selected-items">
                        <p class="ygv-empty-selection"><?php echo esc_html__('Klikni na stavke iznad da ih dodaš u listu', 'hello-elementor-child'); ?></p>
                    </div>
                    
                    <input type="hidden" name="voting_items" id="voting_items" value="[]">
                </div>
                
                <div class="ygv-form-actions">
                    <a href="<?php echo esc_url(ygv_account_page_url(['tab' => 'liste'])); ?>" class="ygv-btn">
                        <?php echo esc_html__('Odustani', 'hello-elementor-child'); ?>
                    </a>
                    <?php if (!empty($available_categories)): ?>
                    <button type="submit" class="ygv-btn ygv-btn-primary" id="ygv-submit-list">
                        <?php echo esc_html__('Kreiraj Listu', 'hello-elementor-child'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
            
            <div id="ygv-list-message" class="ygv-message" style="display: none;"></div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('ygv-create-list-form');
        const messageDiv = document.getElementById('ygv-list-message');
        const submitBtn = document.getElementById('ygv-submit-list');
        const availableItems = document.getElementById('ygv-available-items');
        const selectedItems = document.getElementById('ygv-selected-items');
        const selectedCount = document.getElementById('ygv-selected-count');
        const votingItemsInput = document.getElementById('voting_items');
        const categoryFilter = document.getElementById('ygv-item-category-filter');
        const searchInput = document.getElementById('ygv-item-search');
        
        let allItems = [];
        let selected = [];
        
        // Load items on page load
        loadItems();
        
        function loadItems(categoryId = '', search = '') {
            availableItems.innerHTML = '<p class="ygv-muted"><?php echo esc_js(__('Učitavanje...', 'hello-elementor-child')); ?></p>';
            
            const params = new URLSearchParams({
                action: 'ygv_get_voting_items',
                category: categoryId,
                search: search
            });
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?' + params)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allItems = data.data;
                        renderAvailableItems();
                    }
                });
        }
        
        function renderAvailableItems() {
            if (allItems.length === 0) {
                availableItems.innerHTML = '<p class="ygv-muted"><?php echo esc_js(__('Nema pronađenih stavki', 'hello-elementor-child')); ?></p>';
                return;
            }
            
            let html = '<div class="ygv-items-grid">';
            allItems.forEach(item => {
                const isSelected = selected.includes(item.id);
                const disabled = selected.length >= 10 && !isSelected;
                html += `
                    <div class="ygv-item-card ${isSelected ? 'ygv-item-selected' : ''} ${disabled ? 'ygv-item-disabled' : ''}" 
                         data-id="${item.id}" data-title="${item.title}">
                        ${item.thumbnail ? `<img src="${item.thumbnail}" alt="">` : '<div class="ygv-item-no-thumb"><?php echo esc_js(ygv_icon('image', 24)); ?></div>'}
                        <span class="ygv-item-title">${item.title}</span>
                        ${isSelected ? '<span class="ygv-item-check"><?php echo esc_js(ygv_icon('check', 16)); ?></span>' : ''}
                    </div>
                `;
            });
            html += '</div>';
            availableItems.innerHTML = html;
            
            // Add click handlers
            availableItems.querySelectorAll('.ygv-item-card').forEach(card => {
                card.addEventListener('click', () => toggleItem(card));
            });
        }
        
        function toggleItem(card) {
            const id = parseInt(card.dataset.id);
            const title = card.dataset.title;
            
            if (selected.includes(id)) {
                // Remove
                selected = selected.filter(i => i !== id);
            } else if (selected.length < 10) {
                // Add
                selected.push(id);
            }
            
            updateSelectedItems();
            renderAvailableItems();
        }
        
        function updateSelectedItems() {
            selectedCount.textContent = selected.length;
            votingItemsInput.value = JSON.stringify(selected);
            
            if (selected.length === 0) {
                selectedItems.innerHTML = '<p class="ygv-empty-selection"><?php echo esc_js(__('Klikni na stavke iznad da ih dodaš u listu', 'hello-elementor-child')); ?></p>';
                return;
            }
            
            let html = '';
            selected.forEach((id, index) => {
                const item = allItems.find(i => i.id === id);
                if (item) {
                    html += `
                        <div class="ygv-selected-item" data-id="${id}">
                            <span class="ygv-selected-num">${index + 1}</span>
                            ${item.thumbnail ? `<img src="${item.thumbnail}" alt="">` : ''}
                            <span class="ygv-selected-title">${item.title}</span>
                            <button type="button" class="ygv-remove-item" data-id="${id}">✕</button>
                        </div>
                    `;
                }
            });
            selectedItems.innerHTML = html;
            
            // Add remove handlers
            selectedItems.querySelectorAll('.ygv-remove-item').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.dataset.id);
                    selected = selected.filter(i => i !== id);
                    updateSelectedItems();
                    renderAvailableItems();
                });
            });
        }
        
        // Filter handlers
        categoryFilter.addEventListener('change', () => {
            loadItems(categoryFilter.value, searchInput.value);
        });
        
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadItems(categoryFilter.value, searchInput.value);
            }, 300);
        });
        
        // Form submission
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (selected.length < 10) {
                    messageDiv.className = 'ygv-message ygv-error-banner';
                    messageDiv.innerHTML = '<span><?php echo esc_js(ygv_icon('alert-triangle', 20)); ?></span><strong><?php echo esc_js(__('Moraš izabrati tačno 10 stavki.', 'hello-elementor-child')); ?></strong>';
                    messageDiv.style.display = 'flex';
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = '<?php echo esc_js(__('Kreiranje...', 'hello-elementor-child')); ?>';
                messageDiv.style.display = 'none';
                
                const formData = new FormData(form);
                formData.append('action', 'ygv_create_list');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.className = 'ygv-message ygv-success-banner';
                        messageDiv.innerHTML = '<span><?php echo esc_js(ygv_icon('check-circle', 20)); ?></span><strong>' + data.data.message + '</strong>';
                        messageDiv.style.display = 'flex';
                        
                        setTimeout(() => {
                            window.location.href = data.data.redirect || '<?php echo esc_url(ygv_account_page_url(['tab' => 'liste'])); ?>';
                        }, 2000);
                    } else {
                        messageDiv.className = 'ygv-message ygv-error-banner';
                        messageDiv.innerHTML = '<span><?php echo esc_js(ygv_icon('alert-triangle', 20)); ?></span><strong>' + data.data + '</strong>';
                        messageDiv.style.display = 'flex';
                        submitBtn.disabled = false;
                        submitBtn.textContent = '<?php echo esc_js(__('Kreiraj Listu', 'hello-elementor-child')); ?>';
                    }
                })
                .catch(error => {
                    messageDiv.className = 'ygv-message ygv-error-banner';
                    messageDiv.innerHTML = '<span><?php echo esc_js(ygv_icon('alert-triangle', 20)); ?></span><strong><?php echo esc_js(__('Greška pri komunikaciji sa serverom.', 'hello-elementor-child')); ?></strong>';
                    messageDiv.style.display = 'flex';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php echo esc_js(__('Kreiraj Listu', 'hello-elementor-child')); ?>';
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('yugo_create_list', 'yugo_create_list_shortcode');

/**
 * AJAX handler to get voting items
 */
function ygv_ajax_get_voting_items() {
    $category_id = intval($_GET['category'] ?? 0);
    $search = sanitize_text_field($_GET['search'] ?? '');
    
    $args = [
        'post_type' => 'voting_items',
        'posts_per_page' => 100,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ];
    
    if ($category_id) {
        $args['tax_query'] = [[
            'taxonomy' => 'voting_item_category',
            'field' => 'term_id',
            'terms' => $category_id,
        ]];
    }
    
    if ($search) {
        $args['s'] = $search;
    }
    
    $items = get_posts($args);
    $result = [];
    
    foreach ($items as $item) {
        $thumb = get_the_post_thumbnail_url($item->ID, 'thumbnail');
        $result[] = [
            'id' => $item->ID,
            'title' => $item->post_title,
            'thumbnail' => $thumb ?: '',
        ];
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_ygv_get_voting_items', 'ygv_ajax_get_voting_items');
add_action('wp_ajax_nopriv_ygv_get_voting_items', 'ygv_ajax_get_voting_items');

/**
 * AJAX handler for list creation
 */
function ygv_ajax_create_list() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['ygv_list_nonce'] ?? '', 'ygv_create_list')) {
        wp_send_json_error(__('Sigurnosna provera nije uspela.', 'hello-elementor-child'));
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Morate biti prijavljeni.', 'hello-elementor-child'));
    }
    
    $user_id = get_current_user_id();
    
    // Check permissions
    if (function_exists('ygv_can_user_create_list')) {
        $can_create = ygv_can_user_create_list($user_id);
        if (!$can_create['can_create']) {
            wp_send_json_error($can_create['reason']);
        }
    }
    
    // Validate inputs
    $title = sanitize_text_field($_POST['list_title'] ?? '');
    $category_id = intval($_POST['list_category'] ?? 0);
    $description = sanitize_textarea_field($_POST['list_description'] ?? '');
    $voting_scale = intval($_POST['voting_scale'] ?? 10);
    $voting_items = json_decode(stripslashes($_POST['voting_items'] ?? '[]'), true);
    
    if (empty($title)) {
        wp_send_json_error(__('Naslov je obavezan.', 'hello-elementor-child'));
    }
    
    if (empty($category_id)) {
        wp_send_json_error(__('Kategorija je obavezna.', 'hello-elementor-child'));
    }
    
    // Validate voting items
    if (!is_array($voting_items) || count($voting_items) !== 10) {
        wp_send_json_error(__('Moraš izabrati tačno 10 stavki.', 'hello-elementor-child'));
    }
    
    // Verify all items exist
    foreach ($voting_items as $item_id) {
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'voting_items') {
            wp_send_json_error(__('Nevažeća stavka izabrana.', 'hello-elementor-child'));
        }
    }
    
    // Check category level requirement
    $level_config = function_exists('ygv_get_level_config') ? ygv_get_level_config() : null;
    $required_level = $level_config['list_creation_category_level'] ?? 10;
    
    global $wpdb;
    $t_cat = $wpdb->prefix . 'ygv_user_category_progress';
    $user_cat_level = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT level FROM {$t_cat} WHERE user_id = %d AND category_term_id = %d",
        $user_id,
        $category_id
    )) ?: 1;
    
    if ($user_cat_level < $required_level) {
        wp_send_json_error(sprintf(__('Potreban je nivo %d u ovoj kategoriji.', 'hello-elementor-child'), $required_level));
    }
    
    // Create the post
    $post_data = [
        'post_title' => $title,
        'post_content' => $description,
        'post_status' => 'pending', // Needs approval
        'post_type' => 'voting_list',
        'post_author' => $user_id,
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(__('Greška pri kreiranju liste.', 'hello-elementor-child'));
    }
    
    // Assign category
    wp_set_object_terms($post_id, [$category_id], 'voting_list_category');
    
    // Save voting items (same meta key as admin uses)
    update_post_meta($post_id, '_voting_items', $voting_items);
    update_post_meta($post_id, '_voting_scale', $voting_scale);
    
    // Award XP for creating a list
    $xp_awarded = 0;
    $progress_service = function_exists('ygv_progress') ? ygv_progress() : null;
    if ($progress_service) {
        $xp_result = $progress_service->award_list_creation_xp($user_id, $category_id);
        $xp_awarded = $xp_result['awarded_xp'] ?? 0;
    }
    
    wp_send_json_success([
        'message' => sprintf(__('Lista je kreirana i čeka odobrenje! +%d XP', 'hello-elementor-child'), $xp_awarded),
        'redirect' => ygv_account_page_url(['tab' => 'liste']),
        'post_id' => $post_id,
        'xp_awarded' => $xp_awarded,
    ]);
}
add_action('wp_ajax_ygv_create_list', 'ygv_ajax_create_list');
