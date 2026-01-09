/**
 * Tournament Meta Admin JavaScript
 * Handles all tournament configuration UI interactions
 */

jQuery(document).ready(function($) {
    'use strict';

    // Cache jQuery selectors
    const $contestantList = $('#yuv-contestant-list');
    const $counter = $('#contestant-counter');
    const $maxCount = $('#contestant-max');
    const $addBtn = $('#yuv-add-contestant-btn');
    const $sizeSelect = $('#yuv_tournament_size');
    const $categoryFilter = $('#yuv-category-filter');
    const $searchInput = $('#yuv-candidate-search');
    const $searchResults = $('#yuv-search-results');
    const $ofDurationField = $('#of-duration-field');
    const $qfRoundLabel = $('#qf-round-label');
    const $sfRoundLabel = $('#sf-round-label');
    const $finalRoundLabel = $('#final-round-label');
    const $tournamentSizeInfo = $('#tournament-size-info');
    
    let searchTimer;

    /**
     * Update contestant counter display
     */
    function updateCounter() {
        const currentCount = $contestantList.find('.yuv-contestant-item').length;
        const maxCount = parseInt($maxCount.text()) || 16;
        
        $counter.text(currentCount);
        
        // Disable/enable add button based on count
        if ($addBtn.length) {
            if (currentCount >= maxCount) {
                $addBtn.prop('disabled', true).css('opacity', '0.6');
            } else {
                $addBtn.prop('disabled', false).css('opacity', '1');
            }
        }
    }

    /**
     * Tournament size change handler
     * Updates UI to reflect 8 vs 16 player configuration
     */
    $sizeSelect.on('change', function() {
        const newSize = parseInt($(this).val());
        $maxCount.text(newSize);
        
        if (newSize === 16) {
            // Show octofinals, update round labels
            if ($ofDurationField.length) $ofDurationField.show();
            if ($qfRoundLabel.length) $qfRoundLabel.text('Krug 2: 4 meča istovremeno');
            if ($sfRoundLabel.length) $sfRoundLabel.text('Krug 3: 2 meča istovremeno');
            if ($finalRoundLabel.length) $finalRoundLabel.text('Krug 4: Finalni meč');
            if ($tournamentSizeInfo.length) {
                $tournamentSizeInfo.text('4 kruga: Osmina finala → Četvrtfinale → Polufinale → Finale');
            }
        } else {
            // Hide octofinals, adjust round labels for 8 players
            if ($ofDurationField.length) $ofDurationField.hide();
            if ($qfRoundLabel.length) $qfRoundLabel.text('Krug 1: 4 meča istovremeno');
            if ($sfRoundLabel.length) $sfRoundLabel.text('Krug 2: 2 meča istovremeno');
            if ($finalRoundLabel.length) $finalRoundLabel.text('Krug 3: Finalni meč');
            if ($tournamentSizeInfo.length) {
                $tournamentSizeInfo.text('3 kruga: Četvrtfinale → Polufinale → Finale');
            }
        }
        
        updateCounter();
    });

    /**
     * Add new empty contestant row
     */
    $addBtn.on('click', function() {
        const currentCount = $contestantList.find('.yuv-contestant-item').length;
        const maxCount = parseInt($maxCount.text()) || 16;
        
        if (currentCount >= maxCount) {
            alert('Dostignut je maksimalan broj takmičara (' + maxCount + ')');
            return;
        }
        
        const newIndex = currentCount;
        const $newRow = $(`
            <li class="yuv-contestant-item" data-index="${newIndex}">
                <div class="yuv-contestant-image-col">
                    <div class="yuv-contestant-image-preview empty">Nema slike</div>
                    <button type="button" class="button yuv-select-image-btn">Izbor slike</button>
                    <input type="hidden" name="yuv_contestants[${newIndex}][image_id]" value="">
                    <input type="hidden" name="yuv_contestants[${newIndex}][image_url]" value="">
                </div>
                <div class="yuv-contestant-fields">
                    <input type="text" name="yuv_contestants[${newIndex}][name]" placeholder="Ime takmičara" value="">
                    <textarea name="yuv_contestants[${newIndex}][description]" placeholder="Kratak opis / biografija"></textarea>
                </div>
                <button type="button" class="yuv-contestant-remove" title="Obriši kandidata">×</button>
            </li>
        `);
        
        $contestantList.append($newRow);
        updateCounter();
    });

    /**
     * Remove contestant and reindex
     */
    $contestantList.on('click', '.yuv-contestant-remove', function() {
        if (!confirm('Obrisati takmičara?')) {
            return;
        }
        
        $(this).closest('.yuv-contestant-item').remove();
        
        // Reindex all remaining contestants
        $contestantList.find('.yuv-contestant-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input, textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
        
        updateCounter();
    });

    /**
     * Media uploader for contestant images
     */
    $contestantList.on('click', '.yuv-select-image-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $row = $button.closest('.yuv-contestant-item');
        const $preview = $row.find('.yuv-contestant-image-preview');
        const $imageIdInput = $row.find('input[name*="[image_id]"]');
        const $imageUrlInput = $row.find('input[name*="[image_url]"]');

        // Check if wp.media is available
        if (typeof wp === 'undefined' || !wp.media) {
            alert('Media uploader nije dostupan');
            return;
        }

        const frame = wp.media({
            title: 'Izaberite sliku kandidata',
            button: { text: 'Koristi ovu sliku' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                                 attachment.sizes.thumbnail.url : attachment.url;
            
            $imageIdInput.val(attachment.id);
            $imageUrlInput.val(attachment.url);
            $preview.removeClass('empty').html(
                `<img src="${thumbnailUrl}" alt="Candidate">`
            );
            $button.text('Promeni sliku');
        });

        frame.open();
    });

    /**
     * Debounced search with category filter
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
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'yuv_search_voting_items',
                    query: query,
                    category: category,
                    nonce: $('input[name="yuv_tournament_meta_nonce"]').val()
                },
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        renderSearchResults(response.data);
                    } else {
                        $searchResults.html('<div style="padding: 12px; color: #666;">Nema rezultata</div>').show();
                    }
                },
                error: function() {
                    $searchResults.html('<div style="padding: 12px; color: #dc3545;">Greška pri pretrazi</div>').show();
                }
            });
        }, 300);
    });

    /**
     * Category filter change handler
     */
    $categoryFilter.on('change', function() {
        // Clear search and results when category changes
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
        const currentCount = $contestantList.find('.yuv-contestant-item').length;
        const maxCount = parseInt($maxCount.text()) || 16;
        
        if (currentCount >= maxCount) {
            alert('Dostignut je maksimalan broj takmičara (' + maxCount + ')');
            return;
        }
        
        // Create new contestant row with data from search
        const newIndex = currentCount;
        const thumbnailUrl = item.image || '';
        const hasImage = thumbnailUrl !== '';
        
        const $newRow = $(`
            <li class="yuv-contestant-item" data-index="${newIndex}">
                <div class="yuv-contestant-image-col">
                    <div class="yuv-contestant-image-preview ${hasImage ? '' : 'empty'}">
                        ${hasImage ? `<img src="${thumbnailUrl}" alt="${item.name}">` : 'Nema slike'}
                    </div>
                    <button type="button" class="button yuv-select-image-btn">
                        ${hasImage ? 'Promeni sliku' : 'Izbor slike'}
                    </button>
                    <input type="hidden" name="yuv_contestants[${newIndex}][image_id]" value="${item.image_id || ''}">
                    <input type="hidden" name="yuv_contestants[${newIndex}][image_url]" value="${thumbnailUrl}">
                </div>
                <div class="yuv-contestant-fields">
                    <input type="text" 
                           name="yuv_contestants[${newIndex}][name]" 
                           placeholder="Ime takmičara" 
                           value="${item.name}">
                    <textarea name="yuv_contestants[${newIndex}][description]" 
                              placeholder="Kratak opis / biografija">${item.description || ''}</textarea>
                </div>
                <button type="button" class="yuv-contestant-remove" title="Obriši kandidata">×</button>
            </li>
        `);
        
        $contestantList.append($newRow);
        
        // Clear search
        $searchInput.val('');
        $searchResults.hide().empty();
        
        updateCounter();
    });

    /**
     * Close search results when clicking outside
     */
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.yuv-search-wrapper').length) {
            $searchResults.hide();
        }
    });

    /**
     * Manual advance button handler
     */
    $('#yuv-manual-advance-btn').on('click', function() {
        const btn = $(this);
        const tournamentId = $('#post_ID').val();
        
        if (!confirm('Da li ste sigurni da želite ručno pokrenuti napredovanje?')) {
            return;
        }

        btn.prop('disabled', true).text('Procesiranje...');

        $.post(ajaxurl, {
            action: 'yuv_manual_advance_tournament',
            tournament_id: tournamentId,
            nonce: $('input[name="yuv_tournament_meta_nonce"]').val()
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                location.reload();
            } else {
                alert('❌ Greška: ' + (response.data.message || 'Nepoznata greška'));
                btn.prop('disabled', false).text('Pokreni Napredovanje Odmah');
            }
        }).fail(function() {
            alert('❌ Greška: Problem sa serverom');
            btn.prop('disabled', false).text('Pokreni Napredovanje Odmah');
        });
    });

    /**
     * Initialize counter on page load
     */
    updateCounter();

    /**
     * MutationObserver to detect dynamically added contestants
     */
    if ($contestantList.length > 0) {
        const observer = new MutationObserver(function(mutations) {
            updateCounter();
        });
        
        observer.observe($contestantList[0], {
            childList: true,
            subtree: false
        });
    }
});
