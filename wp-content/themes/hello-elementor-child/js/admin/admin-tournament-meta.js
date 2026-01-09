/**
 * Tournament Meta Admin - Tab-based UI
 */

jQuery(document).ready(function($) {
    'use strict';

    // Cache selectors
    const $contestantList = $('#yuv-contestant-list');
    const $counter = $('#contestant-counter');
    const $searchInput = $('#yuv-candidate-search');
    const $searchResults = $('#yuv-search-results');
    const $categoryFilter = $('#yuv-category-filter');
    
    let searchTimer;

    /**
     * Update filled contestant counter
     */
    function updateCounter() {
        const filledCount = $contestantList.find('.yuv-contestant-item').filter(function() {
            return $(this).find('input[name*="[name]"]').val().trim() !== '';
        }).length;
        
        $counter.text(filledCount);
        
        // Update class for visual feedback
        $contestantList.find('.yuv-contestant-item').each(function() {
            const hasName = $(this).find('input[name*="[name]"]').val().trim() !== '';
            $(this).toggleClass('empty', !hasName);
        });
    }

    /**
     * Media uploader
     */
    $contestantList.on('click', '.yuv-select-image-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $row = $button.closest('.yuv-contestant-item');
        const $preview = $row.find('.yuv-contestant-image-preview');
        const $imageIdInput = $row.find('input[name*="[image_id]"]');
        const $imageUrlInput = $row.find('input[name*="[image_url]"]');

        if (typeof wp === 'undefined' || !wp.media) {
            alert('Media uploader nije dostupan');
            return;
        }

        const frame = wp.media({
            title: 'Izaberite sliku',
            button: { text: 'Koristi ovu sliku' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                                 attachment.sizes.thumbnail.url : attachment.url;
            
            $imageIdInput.val(attachment.id);
            $imageUrlInput.val(attachment.url);
            $preview.removeClass('empty').html(`<img src="${thumbnailUrl}" alt="">`);
            $button.text('Promeni');
        });

        frame.open();
    });

    /**
     * Clear contestant data
     */
    $contestantList.on('click', '.yuv-contestant-clear', function() {
        if (!confirm('Obrisati sve podatke za ovog takmičara?')) {
            return;
        }
        
        const $row = $(this).closest('.yuv-contestant-item');
        $row.find('input[type="text"], textarea').val('');
        $row.find('input[type="hidden"]').val('');
        $row.find('.yuv-contestant-image-preview').addClass('empty').html('Slika');
        $row.find('.yuv-select-image-btn').text('Dodaj');
        $(this).remove();
        
        updateCounter();
    });

    /**
     * Track changes in name fields to update counter
     */
    $contestantList.on('input', 'input[name*="[name]"]', function() {
        updateCounter();
    });

    /**
     * Debounced search
     */
    $searchInput.on('input', function() {
        clearTimeout(searchTimer);
        const query = $(this).val().trim();
        const category = $categoryFilter.val();

        if (query.length < 2) {
            $searchResults.hide().empty();
            return;
        }

        searchTimer = setTimeout(function() {
            $.ajax({
                url: yuvTournamentMeta.ajaxurl,
                type: 'POST',
                data: {
                    action: 'yuv_search_voting_items',
                    query: query,
                    category: category,
                    nonce: yuvTournamentMeta.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        renderSearchResults(response.data);
                    } else {
                        $searchResults.html('<div style="padding: 12px; color: #666;">Nema rezultata</div>').show();
                    }
                },
                error: function(xhr) {
                    console.error('Search error:', xhr);
                    $searchResults.html('<div style="padding: 12px; color: #dc3545;">Greška pri pretrazi</div>').show();
                }
            });
        }, 300);
    });

    /**
     * Category filter change
     */
    $categoryFilter.on('change', function() {
        $searchInput.val('');
        $searchResults.hide().empty();
    });

    /**
     * Render search results
     */
    function renderSearchResults(items) {
        let html = '';
        
        items.forEach(function(item) {
            html += `
                <div class="yuv-search-result-item" data-item='${JSON.stringify(item)}'>
                    ${item.image ? `<img src="${item.image}" class="yuv-search-result-image">` : ''}
                    <div class="yuv-search-result-info">
                        <h4>${item.name}</h4>
                        ${item.description ? `<p>${item.description}</p>` : ''}
                    </div>
                </div>
            `;
        });
        
        $searchResults.html(html).show();
    }

    /**
     * Select item from search results
     */
    $searchResults.on('click', '.yuv-search-result-item', function() {
        const item = $(this).data('item');
        
        // Find first empty slot
        const $emptyRow = $contestantList.find('.yuv-contestant-item').filter(function() {
            return $(this).find('input[name*="[name]"]').val().trim() === '';
        }).first();

        if ($emptyRow.length === 0) {
            alert('Svi slotovi su popunjeni! Obrišite neki postojeći.');
            return;
        }

        // Fill the empty slot
        const thumbnailUrl = item.image || '';
        
        $emptyRow.find('input[name*="[name]"]').val(item.name);
        $emptyRow.find('textarea[name*="[description]"]').val(item.description || '');
        $emptyRow.find('input[name*="[image_id]"]').val(item.image_id || '');
        $emptyRow.find('input[name*="[image_url]"]').val(thumbnailUrl);
        
        if (thumbnailUrl) {
            $emptyRow.find('.yuv-contestant-image-preview')
                .removeClass('empty')
                .html(`<img src="${thumbnailUrl}" alt="${item.name}">`);
            $emptyRow.find('.yuv-select-image-btn').text('Promeni');
        }
        
        // Add clear button if not present
        if (!$emptyRow.find('.yuv-contestant-clear').length) {
            $emptyRow.append('<button type="button" class="yuv-contestant-clear" title="Obriši">×</button>');
        }
        
        // Clear search
        $searchInput.val('');
        $searchResults.hide().empty();
        
        updateCounter();
    });

    /**
     * Close search when clicking outside
     */
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.yuv-search-wrapper').length) {
            $searchResults.hide();
        }
    });

    /**
     * Manual advance button
     */
    $('#yuv-manual-advance-btn').on('click', function() {
        const btn = $(this);
        
        if (!confirm('Pokrenuti ručno napredovanje?')) {
            return;
        }

        btn.prop('disabled', true).text('Procesiranje...');

        $.post(yuvTournamentMeta.ajaxurl, {
            action: 'yuv_manual_advance_tournament',
            tournament_id: yuvTournamentMeta.postId,
            nonce: yuvTournamentMeta.nonce
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ Greška: ' + (response.data.message || 'Nepoznata greška'));
                btn.prop('disabled', false).text('Pokreni Napredovanje Odmah');
            }
        }).fail(function() {
            alert('❌ Greška komunikacije sa serverom');
            btn.prop('disabled', false).text('Pokreni Napredovanje Odmah');
        });
    });

    // Initialize
    updateCounter();
});
