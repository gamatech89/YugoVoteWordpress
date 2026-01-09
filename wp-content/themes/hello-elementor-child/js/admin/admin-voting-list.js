jQuery(document).ready(function ($) {
    const $itemsSelect = $("#voting_item_select");
    const $addButton = $("#add_voting_item");
    const $itemsTable = $("#voting_items_table tbody");
    const $hiddenInput = $("#voting_items");
    const $categoryFilter = $("#voting_item_category");
    const $filterButton = $("#filter_voting_items");

    // Initialize Select2 for better searchability
    if ($.fn.select2) {
        $itemsSelect.select2();
    }

    function fetchVotingItems(category = '') {
        let data = {
            action: "fetch_voting_items",
            category: category
        };

        $.post(ajaxurl, data, function (items) {
            $itemsSelect.empty().append('<option value="">-- Select Voting Item --</option>');
            items.forEach(item => {
                $itemsSelect.append(`<option value="${item.id}">${item.title}</option>`);
            });
        });
    }

    // Load all items on page load
    fetchVotingItems();

    // Filter items by category
    $filterButton.on("click", function () {
        let selectedCategory = $categoryFilter.val();
        fetchVotingItems(selectedCategory);
    });

    $addButton.on("click", function () {
        let selectedId = $itemsSelect.val();
        let selectedText = $itemsSelect.find("option:selected").text();

        if (!selectedId) return;

        if ($itemsTable.find(`tr[data-id='${selectedId}']`).length) return;

        let row = `
            <tr data-id="${selectedId}">
                <td>${selectedText}</td>
                <td><button type="button" class="remove-voting-item button">Remove</button></td>
            </tr>
        `;
        $itemsTable.append(row);
        updateHiddenInput();
    });

    $itemsTable.on("click", ".remove-voting-item", function () {
        $(this).closest("tr").remove();
        updateHiddenInput();
    });

    function updateHiddenInput() {
        let selectedIds = $itemsTable.find("tr").map(function () {
            return $(this).attr("data-id");
        }).get();

        $hiddenInput.val(JSON.stringify(selectedIds));
    }
    
    

    
    // Open modal when clicking "Edit"
    $(document).on("click", ".edit-voting-item", function() {
        let $row = $(this).closest("tr");
        let itemId = $row.data("id");
        let listId = $("#current_voting_list_id").val(); // Ensure you have this hidden input
    
        if (!listId || !itemId) {
            alert("Error: Missing Voting List ID or Item ID!");
            return;
        }
    
        openEditModal(itemId, listId);
    });
    
    function openEditModal(itemId, listId) {
        // Remove existing modal (if any)
        $(".cs-voting-item-edit-modal").remove();
    
        // Get the nonce value (ensure voting_list_vars.nonce is available from wp_localize_script)
        const currentNonce = (typeof admin_voting_vars !== 'undefined' && admin_voting_vars.nonce) 
                             ? admin_voting_vars.nonce 
                             : '';
        if (!currentNonce) {
            console.error("Admin Script: Nonce not found in admin_voting_vars. Action aborted.");
            alert("Security token missing. Cannot fetch item details.");
            return;
        }
    
        // Fetch existing data from pivot table
        $.ajax({
            url: ajaxurl, 
            type: "POST",
            data: {
                action: "get_voting_item_details",
                voting_list_id: listId,
                item_id: itemId,
                nonce: currentNonce // Send nonce
            },
            success: function(response) {
                if (response.success) {
                    let itemData = response.data
                    
                    console.log(itemData);
    
                    // ---- MODIFIED MODAL CONTENT ----
                    let modalContent = `
                        <div class="cs-voting-item-edit-modal">
                            <div class="cs-voting-item-edit-modal--content">
                                <button type="button" id="close-edit-modal" class="cs-voting-item-edit-modal--close button-secondary">&times;</button>
                                <h3>Edit Override Details for: ${itemData.title} (in this list)</h3>
                                
                                <p>
                                    <label for="edit-short-desc">Short Description (Override):</label>
                                    <textarea  id="edit-short-desc" class="widefat"  rows="4">${itemData.short_description || ''}</textarea>
                                    <small class="description">Original item short description: ${itemData.original_short_description || '-'}</small>
                                </p>
                                
                                <p>
                                    <label for="edit-url">URL (e.g., Video - Override):</label>
                                    <input type="text" id="edit-url" class="widefat" value="${itemData.url || ''}">
                                    <small class="description">Original item URL: ${itemData.original_item_url || '-'}</small>
                                </p>
                                
                                <p>
                                    <label for="edit-custom-image-url">Custom Image URL (Override):</label>
                                    <input type="text" id="edit-custom-image-url" class="widefat" value="${itemData.custom_image_url || ''}">
                                    <button type="button" id="upload-override-image-button" class="button">Upload/Select Image</button>
                                    <small class="description">Overrides the item's default featured image for this list only.</small>
                                </p>
    
                                <p>
                                    <label for="edit-custom-image-source">Custom Image Source/Credit (Override):</label>
                                    <input type="text" id="edit-custom-image-source" class="widefat" value="${itemData.custom_image_source || ''}">
                                    <small class="description">Original item image source: ${itemData.original_image_source_text || '-'}</small>
                                </p>
                                
                                <div class="cs-voting-item-edit-modal--footer">
                                    <button type="button" id="save-edit-voting-item" data-item-id="${itemId}" data-list-id="${listId}" class="button-primary">Save Overrides</button>
                                    <button type="button" id="cancel-edit-modal" class="button-secondary">Cancel</button>
                                </div>
                            </div>
                        </div>
                    `;
                    // ---- END OF MODIFIED MODAL CONTENT ----
    
                    $("body").append(modalContent);
                    // Make the close button in the modal content work too
                    $("#close-edit-modal, #cancel-edit-modal").on("click", function() {
                        $(".cs-voting-item-edit-modal").remove();
                    });
               
    
                } else {
                    alert("Error fetching item details: " + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert("AJAX error: Something went wrong while fetching item details, please try again.");
            }
        });
    }
    
   // Handle Save Button Click
    $(document).on("click", "#save-edit-voting-item", function() {
        let $button = $(this);
        let itemId = $button.data("item-id");
        let listId = $button.data("list-id");
    
        // Get values from the modal form
        let shortDesc = $("#edit-short-desc").val();
        // let longDesc = $("#edit-long-desc").val(); // REMOVE THIS LINE
        let customImageUrl = $("#edit-custom-image-url").val(); // Use new ID if you changed it
        let url = $("#edit-url").val();
        let customImageSource = $("#edit-custom-image-source").val(); // NEW: Get custom image source
    
        // Get the nonce value
        const currentNonce = (typeof admin_voting_vars !== 'undefined' && admin_voting_vars.nonce) 
                         ? admin_voting_vars.nonce 
                         : '';
        if (!currentNonce) {
            console.error("Admin Script: Nonce not found in admin_voting_vars for save. Action aborted.");
            alert("Security token missing. Cannot save details.");
            return;
        }

    
        $button.prop("disabled", true).text("Saving...");
    
        $.ajax({
            url: ajaxurl, // WordPress global ajaxurl
            type: "POST",
            data: {
                action: "save_voting_item_details",
                voting_list_id: listId,
                item_id: itemId,
                short_description: shortDesc,
                custom_image_url: customImageUrl, 
                url: url,
                custom_image_source: customImageSource, 
                nonce: currentNonce 
            },
            success: function(response) {
                if (response.success) {
                    alert("Override details saved!"); // Or provide more subtle feedback
                    // You might want to update the main admin table row dynamically here if needed,
                    // but for now, just closing the modal.
                    $(".cs-voting-item-edit-modal").remove();
                    // Potentially trigger a refresh of some part of the page if necessary
                } else {
                    alert("Error saving details: " + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert("AJAX error: Something went wrong while saving details, please try again.");
            },
            complete: function() {
                $button.prop("disabled", false).text("Save Overrides");
            }
        });
    });

    
    // Close Modal when clicking Cancel
    $(document).on("click", "#close-edit-modal", function() {
        $(".cs-voting-item-edit-modal").remove();
    });

    
    // --- Media Uploader Logic ---
    let file_frame_override; 
    $("body").on("click", "#upload-override-image-button", function(event) { // Corrected ID
        event.preventDefault();
        if (file_frame_override) {
            file_frame_override.open();
            return;
        }
        file_frame_override = wp.media({
            title: "Select or Upload Custom Image for Override",
            button: { text: "Use this image" },
            multiple: false 
        });
        file_frame_override.on("select", function() {
            let attachment = file_frame_override.state().get("selection").first().toJSON();
            $("#edit-custom-image-url").val(attachment.url); // Corrected ID
        });
        file_frame_override.open();
    });
    
    $('body').on('click', '.editinline', function() {
        var postId = $(this).closest('tr').attr('id').replace('post-', '');
        var $row = $('#inline-edit');

        var isFeatured = $('#post-' + postId).find('td.column-is_featured').text().trim() === '✅ Yes';
        $row.find('input[name=\"voting_list_is_featured\"]').prop('checked', isFeatured);
    });

    
});


