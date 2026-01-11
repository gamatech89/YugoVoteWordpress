# YugoVote Development Reference

**WordPress Child Theme for Hello Elementor**  
**Last Updated:** January 9, 2026

---

## 📖 Quick Links

- **AI Instructions**: [.github/copilot-instructions.md](.github/copilot-instructions.md)
- **Module Pattern**: See "Module Structure" section below
- **Shortcodes**: See "Shortcode Reference" section below
- **API Endpoints**: See "API Reference" section below

---

## 🏗️ Architecture Overview

YugoVote is a modular WordPress voting & quiz platform built as a child theme for Hello Elementor.

### Core Features

| Module         | Description                                      |
| -------------- | ------------------------------------------------ |
| **Voting**     | Lists, items, categories, user voting            |
| **Tournament** | Bracket-style competitions with timed matches    |
| **Quizzes**    | Multi-level quizzes with token-based progression |
| **Polls**      | Standalone voting polls                          |
| **Account**    | Custom authentication and user profiles          |

### Technology Stack

- **Backend**: PHP 8.2+, WordPress 6.0+, MySQL custom tables
- **Frontend**: Vanilla JS + jQuery, AJAX, Shortcode-based delivery
- **Styling**: Custom CSS with CSS variables
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

### Voting List V2 Redesign (Jan 2026)

- ✅ New V2 template with 3 layout options: Grid, Compact, Classic
- ✅ Mobile-first responsive design with max-width constraint
- ✅ Larger touch-friendly vote buttons (always visible)
- ✅ Top 3 items highlighted with gold/silver/bronze styling
- ✅ Layout switcher UI for testing different layouts
- ✅ New `[voting_list_v2]` shortcode
- ✅ VotingListV2 JavaScript class for new templates
- ✅ Child categories section with top lists carousel

### Account Tabs Redesign (Jan 2026)

- ✅ Profile page UI with YugoCoins and streak display
- ✅ 30-day streak system with tier-based rewards
- ✅ Admin panel for streak rewards configuration
- ✅ Brand color update (navy blue for buttons/forms)

### Deployment Setup (Jan 9, 2026)

- ✅ Git deployment workflow via rsync
- ✅ Removed backup/old files from repo
- ✅ Consolidated taxonomies to `cpts/` folder
- ✅ Moved SQL files to `inc/migrations/sql/`
- ✅ Updated .gitignore for backup patterns

### Tournament UI Refactor (Dec 27, 2025)

- ✅ Removed all pulsing animations
- ✅ Implemented seamless AJAX navigation (no page reloads)
- ✅ Fixed contender layout (60/40 image/info split)
- ✅ Static VS badge with subtle lightning animation only
- ✅ Database-driven progress tracking

---

## 🎯 Shortcode Reference

### Voting Module

| Shortcode                            | Usage                  | Description                            |
| ------------------------------------ | ---------------------- | -------------------------------------- |
| `[voting_list id="123"]`             | Specific list          | Display voting list by ID              |
| `[voting_list_single]`               | On single post         | Display current post as voting list    |
| `[voting_list_single version="v2"]`  | On single post         | Display with V2 template (new layouts) |
| `[voting_list_v2]`                   | On single post         | V2 template with layout switcher UI    |
| `[voting_list_v2 layout="compact"]`  | On single post         | V2 with specific layout preset         |
| `[voting_list_total_score id="123"]` | Anywhere               | Show total score for a list            |
| `[lists_with_this_item]`             | On voting_items single | Show lists containing current item     |
| `[voting_category_hero]`             | Category archive       | Hero section with featured lists       |
| `[homepage_categories_slider]`       | Homepage               | Category carousel slider               |
| `[voting_top_categories]`            | Homepage               | Top categories with rankings           |
| `[voting_trending]`                  | Anywhere               | Trending/popular lists                 |

**V2 Template Layouts:**

- `grid` - Card grid (2-3 columns, mobile-first vertical cards)
- `compact` - Compact list with always-visible voting buttons
- `classic` - Enhanced horizontal cards (improved original style)

**URL Parameters:** Add `?layout=compact` or `?v2` to any voting list page to test.

### Quiz Module

| Shortcode                   | Usage        | Description                     |
| --------------------------- | ------------ | ------------------------------- |
| `[yuv_quiz_grid]`           | Archive page | Grid of quiz cards with filters |
| `[ygv_levels_per_category]` | Profile      | User levels by category         |

### Tournament Module

| Shortcode           | Usage           | Description               |
| ------------------- | --------------- | ------------------------- |
| `[yuv_active_duel]` | Tournament page | Active bracket/arena view |

### Polls Module

| Shortcode             | Usage    | Description        |
| --------------------- | -------- | ------------------ |
| `[yuv_poll id="123"]` | Anywhere | Display poll by ID |

### Account Module

| Shortcode             | Usage          | Description            |
| --------------------- | -------------- | ---------------------- |
| `[ygv_account_panel]` | Profile page   | User account dashboard |
| `[ygv_token_display]` | Header/sidebar | Token balance display  |

---

## 🔌 API Reference

### REST Endpoints (Quiz Module)

| Endpoint                                | Method | Description                         |
| --------------------------------------- | ------ | ----------------------------------- |
| `/wp-json/yugovote/v1/quiz/{id}`        | GET    | Get quiz data with questions        |
| `/wp-json/yugovote/v1/quiz/{id}/start`  | POST   | Start quiz attempt (charges tokens) |
| `/wp-json/yugovote/v1/quiz/{id}/submit` | POST   | Submit quiz results                 |

### AJAX Endpoints

**Voting Module:**
| Action | Description |
|--------|-------------|
| `cs_cast_vote` | Cast vote on voting list item |
| `cs_get_votes` | Get current vote counts |

**Tournament Module:**
| Action | Description |
|--------|-------------|
| `yuv_cast_tournament_vote` | Vote in tournament match |
| `yuv_load_tournament_match_html` | Load next match HTML |

**Quiz Module:**
| Action | Description |
|--------|-------------|
| `yuv_quiz_grid_filter` | Filter quiz grid by category |

**Account Module:**
| Action | Description |
|--------|-------------|
| `ygv_update_profile` | Update user profile |

---

## 📁 Folder Structure

```
hello-elementor-child/
├── functions.php              # Entry point
├── style.css                  # Theme header + global styles
├── inc/
│   ├── init.php               # Bootstraps all modules
│   ├── config.php             # Configuration constants
│   ├── quizzes/
│   │   ├── quizzes-init.php
│   │   ├── quizzes-scripts.php
│   │   ├── cpts/              # quiz, question, quiz-level CPTs
│   │   ├── meta/              # Meta boxes
│   │   ├── api/               # REST + AJAX endpoints
│   │   ├── services/          # Token & Progress services
│   │   ├── shortcodes/
│   │   ├── helpers/
│   │   └── templates/
│   ├── voting/
│   │   ├── voting-init.php
│   │   ├── voting-scripts.php
│   │   ├── voting-shortcodes.php
│   │   ├── voting-hooks.php
│   │   ├── cpts/              # voting_list, voting_items, taxonomies
│   │   ├── meta/
│   │   ├── api/
│   │   ├── admin/
│   │   ├── templates/
│   │   └── tournament/        # Tournament sub-module
│   │       ├── tournament-init.php
│   │       ├── cpts/
│   │       ├── meta/
│   │       ├── api/
│   │       ├── shortcodes/
│   │       └── classes/
│   ├── account/
│   │   ├── account-init.php
│   │   ├── api/
│   │   ├── shortcodes/
│   │   └── templates/
│   ├── polls/
│   │   ├── polls-init.php
│   │   ├── cpts/
│   │   ├── meta/
│   │   └── templates/
│   ├── admin/                 # Global admin customizations
│   ├── helpers/               # Global utility functions
│   └── migrations/            # Database migrations
│       ├── sql/               # Raw SQL scripts
│       └── *.php              # Migration files
├── css/                       # Feature-specific CSS
├── js/                        # Feature-specific JS
├── assets/                    # Images, sounds, etc.
└── template-parts/            # Reusable template partials
```

---

## 🚀 Deployment Workflow

### Local Development

1. Edit files in `wp-content/themes/hello-elementor-child/`
2. Test locally via Docker (`docker-compose up`)
3. Commit and push:
   ```bash
   git add -f wp-content/themes/hello-elementor-child/
   git commit -m "Your message"
   git push
   ```

### Server Deployment

SSH into server and run:

```bash
~/yugovote-theme/deploy-theme.sh
```

This will:

1. `git pull` latest changes
2. `rsync` child theme to production
3. Display "Deploy complete."

---

## 📚 Resources

- **AI Coding Guide**: `.github/copilot-instructions.md`
- **WordPress Codex**: https://codex.wordpress.org/
- **Hello Elementor**: https://github.com/elementor/hello-theme

---

**For detailed implementation examples, see inline comments in source files.**
