/**
 * Showdown Admin Meta Box JS
 * Handles item search, add/remove, and media uploader
 */
(function ($) {
    'use strict';

    let itemIndex = 0;

    $(document).ready(function () {
        // Set initial index
        itemIndex = $('.yuv-item-row').length;

        // Search functionality 
        let searchTimer = null;

        $('#yuv-sd-candidate-search').on('input', function () {
            clearTimeout(searchTimer);
            const query = $(this).val().trim();

            if (query.length < 2) {
                $('#yuv-sd-search-results').hide();
                return;
            }

            searchTimer = setTimeout(function () {
                const category = $('#yuv-sd-category-filter').val();

                $.ajax({
                    url: yuvShowdownMeta.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'yuv_showdown_search_items',
                        nonce: yuvShowdownMeta.nonce,
                        query: query,
                        category: category,
                    },
                    success: function (response) {
                        const $results = $('#yuv-sd-search-results');
                        $results.empty();

                        if (response.success && response.data.length > 0) {
                            response.data.forEach(function (item) {
                                const imgHtml = item.image
                                    ? `<img src="${item.image}" class="yuv-search-result-image" alt="${item.name}">`
                                    : '<div class="yuv-search-result-image" style="background:#f0f0f1;display:flex;align-items:center;justify-content:center;font-size:10px;color:#999;">Nema</div>';

                                $results.append(`
                                    <div class="yuv-search-result-item" 
                                         data-name="${$('<div>').text(item.name).html()}" 
                                         data-description="${$('<div>').text(item.description || '').html()}"
                                         data-image-id="${item.image_id || ''}"
                                         data-image="${item.image || ''}">
                                        ${imgHtml}
                                        <div class="yuv-search-result-info">
                                            <h4>${$('<div>').text(item.name).html()}</h4>
                                            <p>${$('<div>').text(item.description || '').html()}</p>
                                        </div>
                                    </div>
                                `);
                            });
                            $results.show();
                        } else {
                            $results.html('<div style="padding:12px;color:#646970;font-size:13px;">Nema rezultata</div>').show();
                        }
                    }
                });
            }, 300);
        });

        // Click search result to add item
        $(document).on('click', '.yuv-search-result-item', function () {
            const name = $(this).data('name');
            const description = $(this).data('description');
            const imageId = $(this).data('image-id');
            const imageUrl = $(this).data('image');

            addItem(name, description, imageId, imageUrl);

            $('#yuv-sd-search-results').hide();
            $('#yuv-sd-candidate-search').val('');
        });

        // Close search results when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.yuv-search-wrapper').length) {
                $('#yuv-sd-search-results').hide();
            }
        });

        // Add item manually
        $('#yuv-sd-add-item').on('click', function () {
            addItem('', '', '', '');
        });

        // Remove item
        $(document).on('click', '.yuv-item-remove', function () {
            $(this).closest('.yuv-item-row').fadeOut(300, function () {
                $(this).remove();
                reindexItems();
                updateCounter();
            });
        });

        // Image upload
        $(document).on('click', '.yuv-select-image-btn', function (e) {
            e.preventDefault();
            const $row = $(this).closest('.yuv-item-row');

            const frame = wp.media({
                title: 'Izaberi sliku',
                button: { text: 'Koristi sliku' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const thumbUrl = attachment.sizes && attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;

                $row.find('input[name*="[image_id]"]').val(attachment.id);
                $row.find('input[name*="[image_url]"]').val(thumbUrl);
                $row.find('.yuv-item-image-preview')
                    .removeClass('empty')
                    .html(`<img src="${thumbUrl}" alt="">`);
                $row.find('.yuv-select-image-btn').text('Promeni');
            });

            frame.open();
        });
    });

    function addItem(name, description, imageId, imageUrl) {
        const idx = itemIndex++;

        const imageHtml = imageUrl
            ? `<img src="${imageUrl}" alt="${name}">`
            : 'Slika';
        const previewClass = imageUrl ? '' : 'empty';

        const html = `
            <li class="yuv-item-row" data-index="${idx}">
                <div class="yuv-item-image-col">
                    <div class="yuv-item-image-preview ${previewClass}">
                        ${imageHtml}
                    </div>
                    <button type="button" class="button yuv-select-image-btn">
                        ${imageUrl ? 'Promeni' : 'Dodaj'}
                    </button>
                    <input type="hidden" name="yuv_sd_items[${idx}][image_id]" value="${imageId || ''}">
                    <input type="hidden" name="yuv_sd_items[${idx}][image_url]" value="${imageUrl || ''}">
                </div>
                <div class="yuv-item-fields">
                    <input type="text" name="yuv_sd_items[${idx}][name]" placeholder="Ime učesnika" value="${escHtml(name)}">
                    <textarea name="yuv_sd_items[${idx}][description]" placeholder="Kratak opis">${escHtml(description)}</textarea>
                </div>
                <button type="button" class="yuv-item-remove" title="Obriši">×</button>
            </li>
        `;

        $('#yuv-sd-items-list').append(html);
        updateCounter();
    }

    function reindexItems() {
        $('.yuv-item-row').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('input, textarea').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/yuv_sd_items\[\d+\]/, `yuv_sd_items[${i}]`));
                }
            });
        });
        itemIndex = $('.yuv-item-row').length;
    }

    function updateCounter() {
        const count = $('.yuv-item-row').length;
        $('#item-counter').text(count);
        $('#items-count').text(count);
    }

    function escHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }

})(jQuery);
