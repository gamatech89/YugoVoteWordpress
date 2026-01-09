<?php
/**
 * Add custom meta fields to 'voting_list_category' taxonomy.
 * Fields: Category Color, Featured Image, Logo, Hero Slogan.
 */

// 1. Add fields to "Add New Category" form
function voting_list_category_fields_add() {
    ?>
    <div class="form-field">
        <label for="category_color">Category Color</label>
        <input type="color" name="category_color" id="category_color" value="#4457A5">
        <p class="description">Select a brand color for this category.</p>
    </div>

    <div class="form-field">
        <label>Featured Image</label>
        <input type="hidden" id="category_featured_image" name="category_featured_image">
        <input type="button" class="button voting-cat-image-upload" data-target="category_featured_image" value="Upload Featured Image">
        <div class="image-preview"></div>
        <p class="description">Main image used in Hero section (Collage).</p>
    </div>

    <div class="form-field">
        <label>Logo (Mascot)</label>
        <input type="hidden" id="category_logo" name="category_logo">
        <input type="button" class="button voting-cat-image-upload" data-target="category_logo" value="Upload Logo">
        <div class="image-preview"></div>
        <p class="description">Mascot image used in Hero section.</p>
    </div>

    <div class="form-field">
        <label for="category_slogan">Hero Slogan</label>
        <input type="text" name="category_slogan" id="category_slogan" value="">
        <p class="description">Short, punchy title for the Hero slider (e.g., "Legends, victories and glory").</p>
    </div>
    <?php
}
add_action('voting_list_category_add_form_fields', 'voting_list_category_fields_add');


// 2. Add fields to "Edit Category" form
function voting_list_category_fields_edit($term) {
    // Retrieve meta values
    $color = get_term_meta($term->term_id, 'category_color', true);
    $featured_image_id = get_term_meta($term->term_id, 'category_featured_image', true);
    $logo_id = get_term_meta($term->term_id, 'category_logo', true);
    $slogan = get_term_meta($term->term_id, 'category_slogan', true);
    ?>
    
    <tr class="form-field">
        <th><label for="category_color">Category Color</label></th>
        <td>
            <input type="color" name="category_color" id="category_color" value="<?php echo esc_attr($color ?: '#4457A5'); ?>">
            <p class="description">Select a brand color for this category.</p>
        </td>
    </tr>

    <tr class="form-field">
        <th><label>Featured Image</label></th>
        <td>
            <input type="hidden" id="category_featured_image" name="category_featured_image" value="<?php echo esc_attr($featured_image_id); ?>">
            <input type="button" class="button voting-cat-image-upload" data-target="category_featured_image" value="Upload Featured Image">
            <input type="button" class="button voting-cat-image-remove" data-target="category_featured_image" value="Remove" style="<?php echo $featured_image_id ? '' : 'display:none;'; ?>">
            <div class="image-preview">
                <?php 
                if ($featured_image_id) echo wp_get_attachment_image($featured_image_id, 'thumbnail');
                ?>
            </div>
            <p class="description">Main image used in Hero section (Collage).</p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th><label>Logo (Mascot)</label></th>
        <td>
            <input type="hidden" id="category_logo" name="category_logo" value="<?php echo esc_attr($logo_id); ?>">
            <input type="button" class="button voting-cat-image-upload" data-target="category_logo" value="Upload Logo">
            <input type="button" class="button voting-cat-image-remove" data-target="category_logo" value="Remove" style="<?php echo $logo_id ? '' : 'display:none;'; ?>">
            <div class="image-preview">
                <?php 
                if ($logo_id) echo wp_get_attachment_image($logo_id, 'thumbnail');
                ?>
            </div>
            <p class="description">Mascot image used in Hero section.</p>
        </td>
    </tr>

    <tr class="form-field">
        <th><label for="category_slogan">Hero Slogan</label></th>
        <td>
            <input type="text" name="category_slogan" id="category_slogan" value="<?php echo esc_attr($slogan); ?>">
            <p class="description">Short, punchy title for the Hero slider (e.g., "Legends, victories and glory").</p>
        </td>
    </tr>
    <?php
}
add_action('voting_list_category_edit_form_fields', 'voting_list_category_fields_edit');


// 3. Save Meta Fields
function save_voting_list_category_meta($term_id) {
    
    // Save Color
    if (isset($_POST['category_color'])) {
        update_term_meta($term_id, 'category_color', sanitize_hex_color($_POST['category_color']));
    }

    // Save Featured Image
    if (isset($_POST['category_featured_image'])) {
        update_term_meta($term_id, 'category_featured_image', intval($_POST['category_featured_image']));
    }

    // Save Logo
    if (isset($_POST['category_logo'])) {
        update_term_meta($term_id, 'category_logo', intval($_POST['category_logo']));
    }

    // Save Slogan
    if (isset($_POST['category_slogan'])) {
        update_term_meta($term_id, 'category_slogan', sanitize_text_field($_POST['category_slogan']));
    }
}
add_action('created_voting_list_category', 'save_voting_list_category_meta');
add_action('edited_voting_list_category', 'save_voting_list_category_meta');
?>