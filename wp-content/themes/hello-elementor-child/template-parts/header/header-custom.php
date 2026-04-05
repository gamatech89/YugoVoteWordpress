<?php
/**
 * Custom Header Template Part
 * Pure PHP header - no Elementor dependency
 * 
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) exit;

$is_logged_in = is_user_logged_in();
$login_url = home_url('/login/');
$account_url = home_url('/moj-nalog/');

// Get user data if logged in
$user_data = [];
if ($is_logged_in) {
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    
    // Get level/XP data
    if (function_exists('ygv_get_user_progress_data')) {
        $progress_data = ygv_get_user_progress_data($user_id);
    } else {
        $progress_data = ['level' => 1, 'xp' => 0, 'next_xp' => 100, 'progress_percent' => 0];
    }
    
    // Get token data
    if (function_exists('ygv_get_user_token_data')) {
        $token_data = ygv_get_user_token_data($user_id);
    } else {
        $token_data = ['tokens' => 0, 'max_tokens' => 48, 'next_token_in' => 0];
    }
    
    // Get YugoCoins
    if (function_exists('ygv_get_user_yugocoins')) {
        $yugocoins = ygv_get_user_yugocoins($user_id);
    } else {
        $yugocoins = 0;
    }
    
    // Get notifications count
    if (function_exists('ygv_get_unread_notifications_count')) {
        $notifications_count = ygv_get_unread_notifications_count($user_id);
    } else {
        $notifications_count = 0;
    }
    
    $user_data = [
        'user' => $user,
        'progress' => $progress_data,
        'tokens' => $token_data,
        'yugocoins' => $yugocoins,
        'notifications' => $notifications_count,
    ];
}

// Get categories for search filter
$categories = get_terms([
    'taxonomy' => 'voting_list_category',
    'parent' => 0,
    'hide_empty' => false,
]);
?>

<header class="ygv-header">
    <!-- Top Bar -->
    <div class="ygv-header__topbar">
        <div class="ygv-header__topbar-inner">
            <nav class="ygv-header__topnav">
                <?php
                // Pull menu from WordPress Menu ID 13
                wp_nav_menu([
                    'menu' => 13,
                    'container' => false,
                    'items_wrap' => '%3$s',
                    'depth' => 1,
                    'fallback_cb' => function() {
                        // Fallback if menu doesn't exist
                        echo '<a href="' . home_url('/sve-kategorije/') . '">sve kategorije</a>';
                        echo '<a href="' . home_url('/o-nama/') . '">o nama</a>';
                        echo '<a href="' . home_url('/podrska/') . '">podrška</a>';
                    },
                ]);
                ?>
            </nav>
            <div class="ygv-header__topactions">
                <!-- Search with Category Filter -->
                <div class="ygv-header__search-wrapper">
                    <button class="ygv-header__search-trigger" aria-label="Pretraga" type="button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <!-- User Dropdown -->
                    <div class="ygv-header__user-dropdown">
                        <button class="ygv-header__user-trigger" type="button" aria-expanded="false">
                            <div class="ygv-header__user-avatar">
                                <?php echo get_avatar($user_id, 32); ?>
                                <span class="ygv-header__user-level"><?php echo esc_html($user_data['progress']['level']); ?></span>
                            </div>
                            <svg class="ygv-header__user-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        
                        <div class="ygv-header__dropdown-menu" aria-hidden="true">
                            <!-- User Info -->
                            <div class="ygv-dropdown__user-info">
                                <div class="ygv-dropdown__avatar">
                                    <?php echo get_avatar($user_id, 48); ?>
                                    <span class="ygv-dropdown__level-badge"><?php echo esc_html($user_data['progress']['level']); ?></span>
                                </div>
                                <div class="ygv-dropdown__user-meta">
                                    <span class="ygv-dropdown__username"><?php echo esc_html($user_data['user']->display_name); ?></span>
                                    <span class="ygv-dropdown__xp">
                                        <?php echo number_format($user_data['progress']['xp']); ?> / <?php echo number_format($user_data['progress']['next_xp']); ?> XP
                                    </span>
                                </div>
                            </div>
                            
                            <!-- XP Progress Bar -->
                            <div class="ygv-dropdown__xp-bar">
                                <div class="ygv-dropdown__xp-fill" style="width: <?php echo esc_attr($user_data['progress']['progress_percent']); ?>%"></div>
                            </div>
                            
                            <!-- Stats Grid -->
                            <div class="ygv-dropdown__stats">
                                <div class="ygv-dropdown__stat">
                                    <span class="ygv-dropdown__stat-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>
                                        </svg>
                                    </span>
                                    <span class="ygv-dropdown__stat-value"><?php echo esc_html($user_data['tokens']['tokens']); ?>/<?php echo esc_html($user_data['tokens']['max_tokens']); ?></span>
                                    <span class="ygv-dropdown__stat-label">Tokeni</span>
                                </div>
                                <div class="ygv-dropdown__stat">
                                    <span class="ygv-dropdown__stat-icon ygv-dropdown__stat-icon--coins">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/>
                                        </svg>
                                    </span>
                                    <span class="ygv-dropdown__stat-value"><?php echo number_format($user_data['yugocoins']); ?></span>
                                    <span class="ygv-dropdown__stat-label">YugoCoins</span>
                                </div>
                            </div>
                            
                            <!-- Token Timer -->
                            <?php if ($user_data['tokens']['tokens'] < $user_data['tokens']['max_tokens'] && $user_data['tokens']['next_token_in'] > 0): ?>
                            <div class="ygv-dropdown__timer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>Sledeći token za: <strong class="ygv-timer-countdown" data-seconds="<?php echo esc_attr($user_data['tokens']['next_token_in']); ?>"><?php 
                                    $s = $user_data['tokens']['next_token_in'];
                                    echo floor($s/60) . ':' . str_pad($s % 60, 2, '0', STR_PAD_LEFT);
                                ?></strong></span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Quick Links -->
                            <div class="ygv-dropdown__links">
                                <a href="<?php echo esc_url($account_url); ?>" class="ygv-dropdown__link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Moj Profil
                                </a>
                                <a href="<?php echo esc_url($account_url . '?tab=dostignuca'); ?>" class="ygv-dropdown__link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                                    </svg>
                                    Dostignuća
                                </a>
                                <a href="<?php echo esc_url($account_url . '?tab=obavestenja'); ?>" class="ygv-dropdown__link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                                    </svg>
                                    Obaveštenja
                                    <?php if ($user_data['notifications'] > 0): ?>
                                        <span class="ygv-dropdown__badge"><?php echo $user_data['notifications'] > 9 ? '9+' : $user_data['notifications']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            
                            <!-- Logout -->
                            <div class="ygv-dropdown__footer">
                                <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="ygv-dropdown__logout">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                                    </svg>
                                    Odjavi se
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo esc_url($login_url); ?>" class="ygv-header__login-btn">prijavi se</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <div class="ygv-header__main">
        <div class="ygv-header__main-inner">
            <div class="ygv-header__frame">
                <!-- Logo -->
                <a href="<?php echo home_url('/'); ?>" class="ygv-header__logo">
                    <div class="ygv-header__mascot">
                        <?php 
                        $svg_path = get_stylesheet_directory() . '/assets/images/king-head.svg';
                        if (file_exists($svg_path)) {
                            echo file_get_contents($svg_path);
                        } else {
                            // Fallback to relative path if stylesheet directory fails
                            $svg_path_alt = dirname(dirname(__DIR__)) . '/assets/images/king-head.svg';
                            if (file_exists($svg_path_alt)) {
                                echo file_get_contents($svg_path_alt);
                            } else {
                                echo '<!-- SVG not found at ' . esc_html($svg_path) . ' or ' . esc_html($svg_path_alt) . ' -->';
                            }
                        }
                        ?>
                    </div>
                    <img width="196" height="28" src="https://yugovote.com/wp-content/uploads/2024/12/Logo.png" class="ygv-header__logo-img attachment-full size-full wp-image-80" alt="YugoVote">
                </a>
                
                <!-- Mega Menu (shortcode) -->
                <div class="ygv-header__menu">
                    <?php echo do_shortcode('[voting_mega_menu]'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Overlay -->
    <div class="ygv-search-overlay" aria-hidden="true">
        <div class="ygv-search-overlay__inner">
            <button class="ygv-search-overlay__close" aria-label="Zatvori pretragu" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <form class="ygv-search-overlay__form" action="<?php echo home_url('/'); ?>" method="get">
                <div class="ygv-search-overlay__category">
                    <select name="voting_list_category" class="ygv-search-overlay__select">
                        <option value="">Sve kategorije</option>
                        <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <input type="search" name="s" placeholder="Pretraži liste, kvizove..." class="ygv-search-overlay__input" autocomplete="off">
                <input type="hidden" name="post_type" value="voting_list">
                <button type="submit" class="ygv-search-overlay__submit">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
            </form>
            <!-- Live Search Results -->
            <div class="ygv-search-results" style="display: none;">
                <div class="ygv-search-results__loader" style="display: none;">
                    <div class="ygv-search-spinner"></div>
                    <span>Tražim...</span>
                </div>
                <ul class="ygv-search-results__list"></ul>
                <div class="ygv-search-results__empty" style="display: none;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p>Nema rezultata za "<span class="ygv-search-term"></span>"</p>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    // Search overlay
    const searchTrigger = document.querySelector('.ygv-header__search-trigger');
    const searchOverlay = document.querySelector('.ygv-search-overlay');
    const searchClose = document.querySelector('.ygv-search-overlay__close');
    const searchInput = document.querySelector('.ygv-search-overlay__input');
    const searchCategory = document.querySelector('.ygv-search-overlay__select');
    const searchResults = document.querySelector('.ygv-search-results');
    const searchResultsList = document.querySelector('.ygv-search-results__list');
    const searchLoader = document.querySelector('.ygv-search-results__loader');
    const searchEmpty = document.querySelector('.ygv-search-results__empty');
    const searchTerm = document.querySelector('.ygv-search-term');
    
    let searchTimeout = null;
    const SEARCH_DELAY = 300; // ms delay before searching
    
    if (searchTrigger && searchOverlay) {
        // Prevent form submission/redirection
        const searchForm = document.querySelector('.ygv-search-overlay__form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
            });
        }

        searchTrigger.addEventListener('click', function() {
            searchOverlay.classList.add('is-active');
            searchOverlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            setTimeout(() => searchInput.focus(), 100);
        });
        
        searchClose.addEventListener('click', closeSearch);
        searchOverlay.addEventListener('click', function(e) {
            if (e.target === searchOverlay) closeSearch();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchOverlay.classList.contains('is-active')) {
                closeSearch();
            }
        });
        
        function closeSearch() {
            searchOverlay.classList.remove('is-active');
            searchOverlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            // Clear results when closing
            searchResults.style.display = 'none';
            searchResultsList.innerHTML = '';
        }
        
        // Live search functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) clearTimeout(searchTimeout);
            
            // Hide results if query is too short
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            // Show loader
            searchResults.style.display = 'block';
            searchLoader.style.display = 'flex';
            searchEmpty.style.display = 'none';
            searchResultsList.style.display = 'none';
            
            // Debounce the search
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, SEARCH_DELAY);
        });
        
        function performSearch(query) {
            const category = searchCategory ? searchCategory.value : '';
            
            const formData = new FormData();
            formData.append('action', 'ygv_live_search');
            formData.append('search', query);
            formData.append('category', category);
            formData.append('nonce', '<?php echo wp_create_nonce('ygv_live_search'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                searchLoader.style.display = 'none';
                
                if (data.success && data.data.length > 0) {
                    renderResults(data.data);
                } else {
                    searchResultsList.style.display = 'none';
                    searchEmpty.style.display = 'flex';
                    searchTerm.textContent = query;
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                searchLoader.style.display = 'none';
                searchEmpty.style.display = 'flex';
                searchTerm.textContent = query;
            });
        }
        
        function renderResults(results) {
            searchResultsList.innerHTML = '';
            searchResultsList.style.display = 'block';
            searchEmpty.style.display = 'none';
            
            results.forEach(item => {
                const li = document.createElement('li');
                li.className = 'ygv-search-result-item';
                li.innerHTML = `
                    <a href="${item.url}" class="ygv-search-result-link">
                        <div class="ygv-search-result-image">
                            ${item.thumbnail ? `<img src="${item.thumbnail}" alt="${item.title}">` : '<div class="ygv-search-result-placeholder"></div>'}
                        </div>
                        <div class="ygv-search-result-info">
                            ${item.category ? `<span class="ygv-search-result-cat" style="--cat-color: ${item.cat_color || '#283363'}">${item.category}</span>` : ''}
                            <h4>${item.title}</h4>
                            ${item.match_info ? `<span class="ygv-search-result-meta">${item.match_info}</span>` : (item.items_count ? `<span class="ygv-search-result-meta">${item.items_count} itema</span>` : '')}
                        </div>
                        <svg class="ygv-search-result-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                `;
                searchResultsList.appendChild(li);
            });
        }
    }
    
    // User dropdown
    const userTrigger = document.querySelector('.ygv-header__user-trigger');
    const userDropdown = document.querySelector('.ygv-header__dropdown-menu');
    
    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userDropdown.classList.toggle('is-open');
            userTrigger.setAttribute('aria-expanded', isOpen);
            userDropdown.setAttribute('aria-hidden', !isOpen);
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.ygv-header__user-dropdown')) {
                userDropdown.classList.remove('is-open');
                userTrigger.setAttribute('aria-expanded', 'false');
                userDropdown.setAttribute('aria-hidden', 'true');
            }
        });
        
        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && userDropdown.classList.contains('is-open')) {
                userDropdown.classList.remove('is-open');
                userTrigger.setAttribute('aria-expanded', 'false');
                userDropdown.setAttribute('aria-hidden', 'true');
            }
        });
    }
    
    // Token countdown timer
    const timerEl = document.querySelector('.ygv-timer-countdown');
    if (timerEl) {
        let seconds = parseInt(timerEl.dataset.seconds) || 0;
        if (seconds > 0) {
            const interval = setInterval(() => {
                seconds--;
                if (seconds <= 0) {
                    clearInterval(interval);
                    timerEl.textContent = '0:00';
                    // Optionally reload to refresh token count
                } else {
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    timerEl.textContent = m + ':' + String(s).padStart(2, '0');
                }
            }, 1000);
        }
    }
})();
</script>
