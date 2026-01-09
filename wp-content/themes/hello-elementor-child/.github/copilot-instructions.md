# YugoVote AI Coding Instructions

**WordPress Child Theme for Hello Elementor** | **Last Updated:** December 2025

## 🏗️ Architecture Overview

YugoVote is a modular WordPress voting & quiz platform with three main features:

- **Voting System**: Lists, items, categories, and tournament brackets
- **Quiz System**: Multi-level quizzes with token-based progression
- **Polls**: Standalone voting polls

### Module Structure Pattern

ALL feature modules in `inc/` follow this exact structure (see [MODULE_STRUCTURE_GUIDE.md](../MODULE_STRUCTURE_GUIDE.md)):

```
inc/[module]/
├── [module]-init.php          ← REQUIRED: Module loader (loaded by inc/init.php)
├── [module]-scripts.php       ← Asset enqueuing (if needed)
├── [module]-shortcodes.php    ← Frontend rendering via shortcodes
├── [module]-hooks.php         ← WordPress actions/filters
├── helpers.php                ← Module-specific utilities
├── cpts/                      ← Custom post types AND taxonomies (both live here)
│   ├── cpt-*.php
│   └── taxonomy-*.php         ← ⚠️ Taxonomies go in /cpts/, NOT /taxonomies/
├── meta/                      ← Metaboxes for admin edit screens
├── api/                       ← AJAX endpoints (not REST API)
├── admin/                     ← Admin columns, filters, quick edit
│   └── *-columns.php
└── templates/                 ← HTML template parts
```

**Loading order**: `functions.php` → `inc/init.php` → each module's `*-init.php` → submodules

## � Development Environment

### Theme Setup

This is a **child theme** of Hello Elementor. Required setup:

1. **Parent theme**: Hello Elementor must be installed
2. **Database migrations**: Run automatically on theme activation via `inc/migrations/migrations-init.php`
3. **Dependencies**: jQuery (included by default), no build process required
4. **Custom pages**: Create WordPress pages matching slugs in `inc/config.php`:
   - `/login` - Login page
   - `/registracija` - Registration page
   - `/kompletiranje-naloga` - Complete profile page
   - `/moj-nalog` - Account dashboard

### Debugging & Testing

```php
// Enable WordPress debugging (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check migration status
SELECT * FROM wp_options WHERE option_name = 'voting_db_version';

// Inspect user tokens
SELECT * FROM wp_ygv_user_tokens WHERE user_id = 1;

// Check tournament progress
SELECT * FROM wp_voting_list_votes
WHERE voting_list_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'voting_list'
    AND meta_key = '_is_tournament_match'
);
```

## �🔑 Critical Patterns & Conventions

### 1. Function Naming: Prefix by Module

This codebase uses **four distinct function prefixes**:

**`yuv_`** - Tournament module (YugoVote):

```php
function yuv_cast_tournament_vote_ajax() { ... }     // Tournament AJAX
function yuv_render_arena($match_id) { ... }         // Tournament display
```

**`ygv_`** - Quiz services & account features (YoGuVote):

```php
class YGV_Token_Service { ... }                       // Quiz token system
function ygv_account_panel_shortcode() { ... }        // Account UI
```

**`yugo_`** - User authentication system:

```php
function yugo_login_form_shortcode() { ... }          // Login/register forms
```

**`cs_`** - Legacy prefix (voting, polls, admin, helpers):

```php
function cs_add_voting_list_columns($columns) { ... } // Voting admin
function cs_register_poll_cpt() { ... }               // Polls CPT
function cs_get_svg_icon($name) { ... }               // Global helpers
```

### 2. Data Storage: Custom Tables + Post Meta

- **Votes**: Custom table `wp_voting_list_votes` (see `inc/migrations/001_create_voting_tables.php`)
- **Relations**: Custom table `wp_voting_list_item_relations` (pivot for many-to-many)
- **Configuration**: Post meta fields (`_is_featured`, `_is_tournament_match`, etc.)
- **Migrations**: Run via `inc/migrations/migrations-init.php` on theme activation

### 3. Frontend Delivery: Shortcodes (Not Blocks)

Register shortcodes in `*-shortcodes.php`, render via templates:

```php
function cs_voting_mega_menu_shortcode() {
    ob_start();
    include get_stylesheet_directory() . '/inc/voting/templates/mega-menu.php';
    return ob_get_clean();
}
add_shortcode('voting_mega_menu', 'cs_voting_mega_menu_shortcode');
```

### 4. AJAX Pattern

All AJAX handlers follow WordPress `admin-ajax.php` pattern (NOT REST API):

**Registration** in `api/*-ajax.php`:

```php
add_action('wp_ajax_my_action', 'cs_handle_my_action');          // Logged-in users
add_action('wp_ajax_nopriv_my_action', 'cs_handle_my_action');   // Guest users
```

**JavaScript call** with security:

```javascript
wp_localize_script('my-script', 'myData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('my_nonce_action')
]);

// In JS file:
$.ajax({
    url: myData.ajaxurl,
    action: 'my_action',
    nonce: myData.nonce,
    data: { ... }
});
```

**Handler with nonce verification**:

```php
function cs_handle_my_action() {
    check_ajax_referer('my_nonce_action', 'nonce');  // Security check
    // ... process request
    wp_send_json_success(['data' => $result]);
}
```

Example: Tournament voting in [inc/voting/tournament/api/tournament-ajax.php](../inc/voting/tournament/api/tournament-ajax.php)

### 5. Tournament System Architecture

Tournament matches are special `voting_list` posts with metadata:

- `_is_tournament_match` = '1'
- `_yuv_tournament_id` = parent tournament post ID
- `_yuv_stage` = 'of' | 'qf' | 'sf' | 'final'
- Progress tracking is **database-driven** (NOT localStorage) - see [TOURNAMENT_DATABASE_REFACTOR.md](../TOURNAMENT_DATABASE_REFACTOR.md)

Tournament flow:

1. User votes on a match (AJAX to `yuv_cast_tournament_vote`)
2. Backend queries `voting_list_votes` table to find next unvoted match
3. Returns `next_match` data + `progress` object in JSON response
4. Frontend (`js/tournament-carousel.js`) renders next match without page reload

## 📁 Key Files & Their Roles

| File                                                         | Purpose                                             |
| ------------------------------------------------------------ | --------------------------------------------------- |
| `inc/config.php`                                             | Global constants (page slugs for login/register)    |
| `inc/init.php`                                               | Master module loader (loads all `*-init.php` files) |
| `inc/migrations/run-migrations.php`                          | Creates custom DB tables on activation              |
| `inc/voting/tournament/classes/class-tournament-manager.php` | Tournament generation logic                         |
| `inc/quizzes/services/class-ygv-token-service.php`           | Quiz token system (unlocking levels)                |
| `inc/helpers/icons.php`                                      | SVG icon system via `cs_get_svg_icon()`             |
| `css/tournament.css`                                         | Tournament arena styles (split-screen duel UI)      |
| `js/tournament-carousel.js`                                  | Carousel-style match navigation with auto-advance   |

## 🚀 Common Workflows

### Adding a New Module Feature

1. Create folder: `inc/[module]/`
2. Copy init file template from [MODULE_STRUCTURE_GUIDE.md](../MODULE_STRUCTURE_GUIDE.md)
3. Create `cpts/cpt-[name].php` for custom post type
4. Add meta boxes in `meta/[name]-meta.php`
5. Add to `inc/init.php`: `require_once get_stylesheet_directory() . '/inc/[module]/[module]-init.php';`

### Adding Admin Columns

1. Create `inc/[module]/admin/[module]-columns.php`
2. Hook into `manage_{$post_type}_posts_columns` and `manage_{$post_type}_posts_custom_column`
3. Include in module's `*-init.php` file

### Adding AJAX Endpoint

1. Create handler in `inc/[module]/api/[module]-ajax.php`:
   ```php
   add_action('wp_ajax_my_action', 'cs_handle_my_action');
   add_action('wp_ajax_nopriv_my_action', 'cs_handle_my_action'); // For guests
   ```
2. In JS: `$.ajax({ url: ajaxurl, action: 'my_action', ... })`

### Database Queries

Always use `$wpdb->prepare()` for safety:

```php
global $wpdb;
$votes_table = $wpdb->prefix . 'voting_list_votes';
$count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $votes_table WHERE voting_list_id = %d",
    $list_id
));
```

## 🎨 Styling & Assets

- **Brand Colors**: Primary `#4355A4` (Indigo), Secondary `#FE6555` (Coral)
- **CSS Organization**: Feature-specific CSS in `css/` (e.g., `tournament.css`, `quizzes.css`)
- **Icons**: SVG system via `cs_get_svg_icon()` (see `inc/helpers/icons.php`)
- **Enqueuing**: Use `*-scripts.php` files in each module (e.g., `voting-scripts.php`)

## ⚠️ Common Pitfalls

1. **Don't** put taxonomies in `taxonomies/` folder - they belong in `cpts/`
2. **Don't** forget module-specific prefixes (`yuv_`, `ygv_`, `yugo_`, or `cs_`)
3. **Don't** use localStorage for persistent data (it's not cross-device/browser)
4. **Don't** create REST API endpoints - this theme uses `admin-ajax.php` pattern
5. **Don't** modify `inc/admin/admin-init.php` for module-specific admin features - use module's own `admin/` folder
6. **Don't** skip `check_ajax_referer()` in AJAX handlers - always verify nonces for security

## 📚 Documentation

- [MODULE_STRUCTURE_GUIDE.md](../MODULE_STRUCTURE_GUIDE.md) - Module pattern reference
- [REFACTORING_COMPLETE.md](../REFACTORING_COMPLETE.md) - Recent structural changes summary
- [TOURNAMENT_DATABASE_REFACTOR.md](../TOURNAMENT_DATABASE_REFACTOR.md) - Tournament progress tracking system
- [DOCUMENTATION_INDEX.md](../DOCUMENTATION_INDEX.md) - Full docs navigation

## 🔍 Quick Reference

**Find all shortcodes:**

```bash
grep -r "add_shortcode" inc/
```

**Find AJAX handlers:**

```bash
grep -r "wp_ajax_" inc/
```

**Check custom tables:**

```sql
SHOW TABLES LIKE 'wp_voting_%';
```

**Current modules:** `voting`, `quizzes`, `polls`, `account`, `admin`, `helpers`, `migrations`
