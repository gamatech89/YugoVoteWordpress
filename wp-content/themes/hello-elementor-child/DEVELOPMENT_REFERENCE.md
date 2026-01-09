# YugoVote Development Reference

**WordPress Child Theme for Hello Elementor**  
**Last Updated:** December 27, 2025

---

## 📖 Quick Links

- **AI Instructions**: [.github/copilot-instructions.md](.github/copilot-instructions.md)
- **Module Pattern**: See "Module Structure" section below
- **Tournament System**: See "Tournament Architecture" section below

---

## 🏗️ Architecture Overview

YugoVote is a modular WordPress voting & quiz platform built as a child theme for Hello Elementor.

### Core Features

- **Voting System**: Lists, items, categories, and tournament brackets
- **Quiz System**: Multi-level quizzes with token-based progression
- **Polls**: Standalone voting polls
- **Account System**: Custom authentication and user profiles

### Technology Stack

- **Backend**: PHP 8.0+, WordPress 6.0+, MySQL custom tables
- **Frontend**: jQuery, AJAX (no REST API), Shortcode-based delivery
- **Styling**: Custom CSS with gradient-heavy design system
- **Parent Theme**: Hello Elementor

---

## 📁 Module Structure Pattern

ALL feature modules in `inc/` follow this exact structure:

```
inc/[module]/
├── [module]-init.php          ← Module loader (REQUIRED)
├── [module]-scripts.php       ← Asset enqueuing
├── [module]-shortcodes.php    ← Frontend rendering via shortcodes
├── [module]-hooks.php         ← WordPress actions/filters
├── helpers.php                ← Module-specific utilities
├── cpts/                      ← Custom post types AND taxonomies
│   ├── cpt-*.php
│   └── taxonomy-*.php         ← ⚠️ Taxonomies go in /cpts/, NOT /taxonomies/
├── meta/                      ← Metaboxes for admin edit screens
├── api/                       ← AJAX endpoints (NOT REST API)
├── admin/                     ← Admin columns, filters, quick edit
│   └── *-columns.php
└── templates/                 ← HTML template parts
```

**Loading order**: `functions.php` → `inc/init.php` → each module's `*-init.php`

### Module Init Template

```php
<?php
if (!defined('ABSPATH')) exit;

$module_path = get_stylesheet_directory() . '/inc/[module]/';

// Custom Post Types & Taxonomies
require_once $module_path . 'cpts/cpt-[name].php';
require_once $module_path . 'cpts/taxonomy-[name].php';

// Meta Boxes
require_once $module_path . 'meta/[name]-meta.php';

// API Endpoints
require_once $module_path . 'api/[module]-endpoints.php';

// Admin
if (file_exists($module_path . 'admin/[module]-columns.php')) {
    require_once $module_path . 'admin/[module]-columns.php';
}

// Shortcodes
require_once $module_path . '[module]-shortcodes.php';

// Scripts
if (file_exists($module_path . '[module]-scripts.php')) {
    require_once $module_path . '[module]-scripts.php';
}

// Helpers
if (file_exists($module_path . 'helpers.php')) {
    require_once $module_path . 'helpers.php';
}
```

---

## 🔑 Naming Conventions

### Function Prefixes by Module

**Tournament Module** → `yuv_` prefix:

```php
function yuv_cast_tournament_vote_ajax() { ... }
function yuv_render_arena($match_id) { ... }
function yuv_active_duel_shortcode() { ... }
```

**All Other Modules** → `cs_` prefix (legacy):

```php
function cs_add_voting_list_columns($columns) { ... }
function cs_register_poll_cpt() { ... }
function cs_voting_mega_menu_shortcode() { ... }
```

---

## 🎮 Tournament Architecture

### Database-Driven Progress Tracking

Tournament matches are special `voting_list` posts with metadata:

- `_is_tournament_match` = '1'
- `_yuv_tournament_id` = parent tournament post ID
- `_yuv_stage` = 'of' | 'qf' | 'sf' | 'final'
- `_yuv_match_number` = Match number within stage
- `_yuv_end_time` = Unix timestamp
- `_voting_items` = Array of contestant IDs

### Voting Flow (AJAX-Based, No Reloads)

1. **User votes** → `yuv_cast_tournament_vote` AJAX action
2. **Backend**:
   - Inserts vote into `wp_voting_list_votes` table
   - Calculates updated percentages
   - Finds next unvoted match in same stage
3. **Returns JSON**:
   ```json
   {
     "success": true,
     "results": [{"id": 123, "votes": 45, "percent": 55}],
     "next_match": {...},
     "progress": {"total": 8, "voted": 3, "percent": 37}
   }
   ```
4. **Frontend** (`tournament.js`):
   - Shows results immediately (CSS class toggle)
   - Waits 1.5 seconds
   - Calls `yuv_load_tournament_match_html` AJAX endpoint
   - Replaces arena HTML seamlessly (no page reload)
   - Reinitializes event handlers and timer

### Key Endpoints

- `yuv_cast_tournament_vote` - Submit vote, get results
- `yuv_load_tournament_match_html` - Load next/specific match HTML

---

## 💾 Database Schema

### Custom Tables

**`wp_voting_list_votes`** - All votes (tournament & regular)

```sql
id, voting_list_id, voting_item_id, user_id, ip_address, vote_value, created_at
```

**`wp_voting_list_item_relations`** - Many-to-many pivot

```sql
id, voting_list_id, voting_item_id, short_description, long_description,
custom_image_url, url, created_at, updated_at
```

**`wp_ygv_user_overall_progress`** - Quiz progress

```sql
user_id, overall_level, updated_at
```

### Migrations

- Located in: `inc/migrations/`
- Run automatically on theme activation via `migrations-init.php`

---

## 🎨 Design System

### Brand Colors

- Primary: `#4355A4` (Indigo)
- Secondary: `#FE6555` (Coral)
- Gold: `#FFD700` (Success/Winner)
- Dark: `#16213e` → `#0f172a` (Gradients)

### UI Patterns

- **Split-Screen Duel**: 60% image / 40% info area
- **Gradients**: Heavy use throughout (135deg angles)
- **Animations**: Subtle only (no pulsing buttons)
- **Responsive**: Mobile-first, stacks at 768px

---

## 🚀 Common Workflows

### Add New Module

1. Create folder: `inc/[module]/`
2. Copy init template (see above)
3. Create `cpts/cpt-[name].php`
4. Add to `inc/init.php`: `require_once ... '[module]-init.php';`

### Add AJAX Endpoint

1. Create handler in `inc/[module]/api/[module]-ajax.php`:
   ```php
   add_action('wp_ajax_my_action', 'cs_my_action_handler');
   add_action('wp_ajax_nopriv_my_action', 'cs_my_action_handler');
   ```
2. In JS: `$.ajax({ url: ajaxurl, action: 'my_action', ... })`

### Add Admin Columns

1. Create `inc/[module]/admin/[module]-columns.php`
2. Hook into:
   - `manage_{$post_type}_posts_columns`
   - `manage_{$post_type}_posts_custom_column`
3. Include in module's `*-init.php`

---

## ⚠️ Common Pitfalls

1. **Don't** put taxonomies in `taxonomies/` - they go in `cpts/`
2. **Don't** use localStorage for persistent data (not cross-device)
3. **Don't** create REST API endpoints - use AJAX pattern
4. **Don't** forget function prefix (`cs_` or `yuv_`)
5. **Don't** modify global `admin-init.php` for module features

---

## 🔧 Development Commands

```bash
# Check git status
git status

# View custom tables
mysql -u root -p wp_database
SHOW TABLES LIKE 'wp_voting_%';

# Find shortcodes
grep -r "add_shortcode" inc/

# Find AJAX handlers
grep -r "wp_ajax_" inc/

# Check errors
tail -f wp-content/debug.log
```

---

## 📝 Recent Major Changes

### Tournament UI Refactor (Dec 27, 2025)

- ✅ Removed all pulsing animations
- ✅ Implemented seamless AJAX navigation (no page reloads)
- ✅ Fixed contender layout (60/40 image/info split)
- ✅ Static VS badge with subtle lightning animation only
- ✅ Database-driven progress tracking

**Files Modified**:

- `css/tournament.css` - Complete UI overhaul
- `js/tournament.js` - AJAX navigation rewrite
- `inc/voting/tournament/api/tournament-ajax.php` - New endpoint
- `inc/voting/tournament/shortcodes/bracket-shortcode.php` - Extracted rendering

### Module Structure Refactor (Dec 26, 2025)

- ✅ Moved taxonomies to `cpts/` folders
- ✅ Created module-specific `admin/` folders
- ✅ Extracted admin filters from global admin file
- ✅ Added proper init loaders for helpers & migrations

---

## 📚 Resources

- **AI Coding Guide**: `.github/copilot-instructions.md`
- **WordPress Codex**: https://codex.wordpress.org/
- **Hello Elementor**: https://github.com/elementor/hello-theme

---

**For detailed API documentation and implementation examples, see the inline comments in source files.**
