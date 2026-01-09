O# YugoVote Development Status

**Last Updated:** January 9, 2026  
**Environment:** WordPress with Elementor, Hello Elementor Child Theme

---

## 📊 Current State Overview

### ✅ Completed Features

#### 1. Gamification System

- **XP & Leveling:** Users earn XP from voting and quizzes
- **Level Tiers:** Rookie → Amateur → Semi-Pro → Pro → Expert → Master → Legend
- **Category Levels:** Separate progression per category (Sport, Music, Film, etc.)
- **Vote Bonuses:** Higher category levels = more vote weight

#### 2. Achievement System (`class-ygv-achievement-service.php`)

- 20+ achievements across 5 categories: Voting, Quiz, Creation, Level, Streak
- XP rewards for unlocking achievements
- Progress tracking (e.g., "5/10 votes for next achievement")
- Newly unlocked achievement banners

#### 3. Voting Streak System

- Daily voting streak tracking
- Streak bonus XP (up to +10 XP per vote at 10+ day streak)
- Milestone progress visualization (3 → 7 → 14 → 30 → 60 → 100 days)

#### 4. Quiz Token System (`YGV_Token_Service`)

- Token wallet per user (default: 48 max tokens)
- Token regeneration over time (configurable rate)
- Tokens required to play quizzes

#### 5. Icon System (`inc/icons.php`)

- 100+ Lucide SVG icons
- Helper functions: `ygv_icon($name, $size, $class)` and `ygv_icon_e($name, $size, $class)`
- Categories: gaming, stats, achievements, time, money, notifications, media, etc.
- Uses `currentColor` for CSS theming compatibility

#### 6. User Stats Bar (`[ygv_user_stats_bar]` shortcode)

- Displays: Avatar, Level, XP, Quiz Tokens, YugoCoins, Notifications
- Light theme variant active (`.ygv-stats-bar-light`)
- Token regeneration countdown timer
- CSS: `css/user-stats-bar.css`
- Wrapper class: `.cs-player-stats-container`

#### 7. Account Dashboard (`[yugo_account]` shortcode)

Router-based tab system with these tabs:

| Tab         | Status     | File                          |
| ----------- | ---------- | ----------------------------- |
| Kvizovi     | ✅ Working | `account-tab-kvizovi.php`     |
| Profil      | ✅ Working | `account-tab-profil.php`      |
| Moje Liste  | ✅ Working | `account-tab-liste.php`       |
| Dostignuća  | ✅ Working | `account-tab-dostignuca.php`  |
| Podešavanja | ✅ Working | `account-tab-podesavanja.php` |
| Sigurnost   | ✅ Working | `account-tab-sigurnost.php`   |

#### 8. Leaderboard (`[ygv_leaderboard]` shortcode)

- Multiple tabs: Top Voters, Top Streak, Top XP
- User rankings with avatars and stats

---

## 🗂️ Key File Structure

```
wp-content/themes/hello-elementor-child/
├── functions.php                    # Loads inc/init.php
├── inc/
│   ├── init.php                     # Main loader (loads icons.php FIRST)
│   ├── icons.php                    # ⭐ Lucide SVG icon system
│   ├── account/
│   │   ├── account-init.php
│   │   ├── shortcodes/
│   │   │   ├── account-router.php   # [yugo_account] tab router
│   │   │   ├── user-stats-bar-shortcode.php
│   │   │   └── leaderboard-shortcode.php
│   │   └── templates/
│   │       ├── account-tab-kvizovi.php
│   │       ├── account-tab-profil.php
│   │       ├── account-tab-liste.php
│   │       ├── account-tab-dostignuca.php
│   │       ├── account-tab-podesavanja.php
│   │       └── account-tab-sigurnost.php
│   ├── quizzes/
│   │   └── services/
│   │       ├── class-ygv-achievement-service.php
│   │       ├── ProgressService.php
│   │       └── TokenService.php
│   ├── voting/
│   │   ├── frontend-submit.php
│   │   └── api/voting-endpoints.php
│   └── admin/
│       └── level-settings.php       # Admin level/tier configuration
├── css/
│   ├── account.css                  # Account dashboard styles
│   └── user-stats-bar.css           # Stats bar styles
└── js/
    └── account/
        └── user-stats-bar.js        # Token countdown timer
```

---

## 🗄️ Database Tables

| Table                        | Purpose                                |
| ---------------------------- | -------------------------------------- |
| `ygv_user_overall_progress`  | Global XP and level per user           |
| `ygv_user_category_progress` | XP and level per category per user     |
| `ygv_user_quiz_progress`     | Quiz attempts, scores, XP earned       |
| `ygv_user_achievements`      | Unlocked achievements per user         |
| `ygv_token_wallets`          | Quiz token balances and regen settings |
| `ygv_votes`                  | Individual vote records                |
| `ygv_voting_lists`           | User-created voting lists              |

---

## 🐛 Recently Fixed Issues

1. **Profil tab blank page** - `get_user_stats()` was `protected`, changed to `public`
2. **Function redeclaration error** - Added `function_exists()` checks for helper functions
3. **Stats bar CSS broke Elementor** - Removed global `.elementor-shortcode` selectors, now targets only `.cs-player-stats-container`
4. **Stats bar CSS not loading** - Changed from `has_shortcode()` check to `is_user_logged_in()` (Elementor bypasses shortcode detection)
5. **Icons not displaying** - Added fallback `require_once` for `icons.php` in all tab templates

---

## 🔮 Future Work Plan

### Phase 1: Polish & Bug Fixes (Priority: High)

- [ ] Add error logging/debugging helper for development
- [ ] Review all templates for potential PHP errors
- [ ] Add try-catch blocks around database queries
- [ ] Create admin tool to view user stats/debug info

### Phase 2: YugoCoins System (Priority: High)

- [ ] Design YugoCoin earning mechanics (voting, achievements, daily login)
- [ ] Create YugoCoin shop/rewards system
- [ ] Add YugoCoin transaction history
- [ ] Implement YugoCoin spending (boost votes, unlock features)

### Phase 3: Notifications System (Priority: Medium)

- [ ] Create notifications database table
- [ ] Implement notification types (achievement unlocked, streak milestone, etc.)
- [ ] Add notification bell functionality in stats bar
- [ ] Create notifications tab in account dashboard
- [ ] Add email notification preferences

### Phase 4: Social Features (Priority: Medium)

- [ ] User profiles (public view)
- [ ] Follow/friend system
- [ ] Activity feed
- [ ] Share achievements on social media

### Phase 5: Advanced Gamification (Priority: Low)

- [ ] Daily/weekly challenges
- [ ] Seasonal events with limited achievements
- [ ] Guild/team system
- [ ] Referral program with rewards

### Phase 6: Analytics & Admin (Priority: Low)

- [ ] Admin dashboard with user statistics
- [ ] Engagement metrics (daily active users, retention)
- [ ] Achievement unlock rates
- [ ] Popular categories/quizzes analytics

---

## 🛠️ Development Notes

### CSS Best Practices

- **NEVER** target global Elementor classes like `.elementor-widget-shortcode`
- Always use specific wrapper classes (e.g., `.cs-player-stats-container`)
- Use `.ygv-` prefix for all custom classes

### PHP Best Practices

- Always wrap function definitions in `function_exists()` in templates
- Use `public` visibility for methods called outside the class
- Add fallback `require_once` for dependencies in templates
- Enable `WP_DEBUG` and `WP_DEBUG_LOG` for development

### Icon Usage

```php
// Return SVG string
$icon = ygv_icon('star', 24, 'custom-class');

// Echo directly
<?php ygv_icon_e('trophy', 20); ?>

// In JavaScript strings
'<span><?php echo esc_js(ygv_icon('check', 16)); ?></span>'
```

### Shortcodes

- `[ygv_user_stats_bar]` - User stats header bar
- `[yugo_account]` - Account dashboard with tabs
- `[ygv_leaderboard]` - Leaderboard widget

---

## 📝 Configuration

### Level Tiers (Admin → Level Settings)

Configurable via `ygv_get_level_config()`:

- XP thresholds per level
- Tier titles and level ranges
- Vote bonuses per tier

### Token Settings

- Max tokens per user (default: 48)
- Regeneration rate (tokens per interval)
- Regeneration interval (minutes)

---

## 🔗 URLs

- Account page: `/moj-nalog/`
- Profile tab: `/moj-nalog/?tab=profil`
- Quizzes: `/kvizovi/`
- Complete profile: `/kompletiranje-naloga/`
