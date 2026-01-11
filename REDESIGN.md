Task: Account Tabs UI/UX Standardization & Dynamic Color Propagation

1. Objective
   Refactor the User Account area to achieve visual cohesion, premium feel, and logical flow. You will standardize design tokens (typography, cards, buttons) across all tabs and implement a dynamic category color system using existing backend helpers.

2. Core Files to Modify
   Logic: account-router.php, helper-functions.php

Styling: account.css, category-colors.css

Templates: \* account-tab-profil.php

account-tab-kvizovi.php

account-tab-liste.php

account-tab-dostignuca.php

3. Implementation Instructions
   Phase A: Router & Navigation Refactor
   Reorder Tabs: Update ygv_account_nav_items in account-router.php. Set Profile as the primary (first) tab.

Navigation Icons: Use ygv_icon_e() to inject Lucide icons into the nav chips. Match the icon slots defined in account.css (lines 1-55).

Phase B: CSS Tokenization (The "Single Source of Truth")
Normalize the following in account.css (specifically around lines 60-185):

Cards: Establish one universal card style (Subtle shadow, specific border-radius, and neutral background). Eliminate the mix of gradients and flat cards.

Typography: Create a consistent type ramp:

Headings: H3 (18px/700)

Body: 14px/400

Labels/Muted: 12px/500

Buttons: Standardize Primary (Solid) and Secondary (Ghost) variants. Ensure consistent padding, pill-shape/rounded-corners, and hover states.

Progress Bars: Create a single .ygv-progress-component with size variants (S/M/L).

Phase C: Dynamic Category Color System
Inject Variables: In templates (profil and kvizovi), use ygv_get_quiz_category_color() to fetch category colors.

CSS Variables: Apply the color as an inline CSS variable: style="--cat-color: <?php echo $color; ?>;".

UI Mapping: Use --cat-color for:

Progress bar fills.

Left-side card accents.

Badge backgrounds or borders.

Quiz history score rings.

Phase D: Template-Specific Refinements
account-tab-profil.php:

Implement "Ekspertiza po Kategorijama" list using the category color system.

Group content into clean, uniform cards: "Global Level," "Category Levels," "Streak," and "Interests."

Add quick stat chips (XP, Tokens) using a unified chip component.

account-tab-kvizovi.php: Replace the fixed purple/indigo progress bars with the dynamic category color variable.

account-tab-dostignuca.php & account-tab-liste.php: Apply the normalized card and typography tokens to ensure they no longer feel like "visual drift."

4. Technical Constraints
   Maintainability: Keep the isolation of tab templates; do not merge them into one giant file.

Helper Usage: Always use ygv_icon_e() for icons and ygv_get_quiz_category_color() for category logic.

Responsiveness: Ensure the 4-column grids in quiz stats gracefully degrade to 1 or 2 columns on mobile using consistent gutters.

5. Definition of Done
   [ ] Navigation starts with Profile and includes Lucide icons.

[ ] All cards across all 4 tabs have identical shadows, radii, and padding.

[ ] Typography does not "swing" in size between tabs.

[ ] Category-specific items (bars, badges) reflect their specific category color instead of a generic purple.

[ ] Progress bars are visually identical in shape/height regardless of the tab they appear on.
