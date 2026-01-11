# YugoVote Pure PHP Templates

## Overview

This document describes the new pure PHP template system that bypasses Elementor for better performance.

## Template Files Created

### CSS

- **[css/templates.css](css/templates.css)** - Unified styles for all pure PHP templates
  - CSS Variables for consistent theming
  - Category page styles
  - List card component
  - Item page styles
  - Quiz archive styles
  - Auth page styles (login, register, complete profile)
  - Account page header styles
  - Responsive breakpoints

### Category Templates

- **[taxonomy-voting_list_category.php](taxonomy-voting_list_category.php)** - Router (loads fullpage template)
- **[taxonomy-voting_list_category-fullpage.php](taxonomy-voting_list_category-fullpage.php)** - Complete category template
  - Hero section with category logo, stats
  - Subcategory grid (parent categories only)
  - Popular lists by category cards (parent only)
  - All lists grid with pagination
  - Handles both parent and child categories

### Voting List Templates

- **[single-voting_list.php](single-voting_list.php)** - Single voting list (loads fullpage template)
- **[inc/voting/templates/voting-list/voting-list-fullpage.php](inc/voting/templates/voting-list/voting-list-fullpage.php)** - Complete voting list page
- **[css/voting-fullpage.css](css/voting-fullpage.css)** - Dedicated voting list styles

### Single Item Template

- **[single-voting_items.php](single-voting_items.php)** - Single voting item page
  - Hero with item image, title, stats
  - External links (YouTube, Spotify, web)
  - Grid of all lists containing this item
  - Shows item's rank and score per list

### Quiz Templates

- **[archive-quiz.php](archive-quiz.php)** - Quiz archive/listing page
  - Hero section with user stats
  - Category filter buttons
  - Quiz cards with progress indicators
  - Pagination

### Account & Auth Templates

- **[page-moj-nalog.php](page-moj-nalog.php)** - Account dashboard page
  - User header with avatar, level badge
  - Logout button
  - Renders `[yugo_account]` shortcode
- **[page-login.php](page-login.php)** - Login page (updated with ygv styles)
- **[page-register.php](page-register.php)** - Registration page (updated with ygv styles)
- **[page-complete-profile.php](page-complete-profile.php)** - Profile completion page (updated with ygv styles)

## URL Structure

| URL Pattern              | Template                          | Description        |
| ------------------------ | --------------------------------- | ------------------ |
| `/kategorija/{slug}/`    | taxonomy-voting_list_category.php | Category pages     |
| `/lista/{slug}/`         | single-voting_list.php            | Single voting list |
| `/item/{slug}/`          | single-voting_items.php           | Single voting item |
| `/kvizovi/`              | archive-quiz.php                  | Quiz archive       |
| `/quiz/{slug}/`          | (Elementor)                       | Single quiz        |
| `/moj-nalog/`            | page-moj-nalog.php                | Account dashboard  |
| `/prijava/`              | page-login.php                    | Login page         |
| `/registracija/`         | page-register.php                 | Registration page  |
| `/kompletiranje-naloga/` | page-complete-profile.php         | Profile completion |

## Key Features

### Performance

- Pure PHP renders ~200-300ms
- Elementor typically 500-800ms
- No Elementor builder overhead
- Minimal database queries

### Design System

- Consistent CSS variables throughout
- Reusable component classes (.ygv-\*)
- Category colors from term meta
- Responsive mobile-first design

### Components

- `.ygv-cat-hero` - Category hero section
- `.ygv-subcat-card` - Subcategory card
- `.ygv-list-card` - List card (used everywhere)
- `.ygv-item-hero` - Item hero section
- `.ygv-quiz-card` - Quiz card
- `.ygv-filter-btn` - Filter button

## What Still Uses Elementor

1. **Homepage** - Complex layout with multiple sections
2. **Single Quiz** - Modal/overlay quiz interface

## Development Notes

### Adding New Templates

1. Create template file in theme root or `template-parts/`
2. Enqueue `templates.css` at minimum
3. Use `.ygv-*` class prefix for new components
4. Use `--ygv-*` CSS variables for theming

### Category Colors

```php
$category_color = get_term_meta($term_id, 'category_color', true) ?: '#2D3A8C';
```

### Getting Stats

```php
// Total votes for list
$total_score = get_post_meta($list_id, '_vote_cache_total_score', true) ?: 0;

// Items count
$items_count = get_post_meta($list_id, '_vote_cache_items_count', true) ?: 0;

// Category votes
$total_votes = get_term_meta($term_id, '_total_category_votes', true) ?: 0;
```

## Testing URLs

After permalinks flush:

- Category: `http://yugovote.local:8080/kategorija/muzika/`
- List: `http://yugovote.local:8080/lista/najbolje-jugoslavenske-pjesme/`
- Item: `http://yugovote.local:8080/item/{item-slug}/`
- Quizzes: `http://yugovote.local:8080/kvizovi/`
