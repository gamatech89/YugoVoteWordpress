jQuery(document).ready(function($) {
    $('body').on('click', '.voting-cat-image-upload', function(e) {
        e.preventDefault();

        let button = $(this);
        let inputField = $('#' + button.data('target'));
        let previewContainer = button.closest('td, .form-field').find('.image-preview');

        // Always create a new instance for each click to avoid conflicts
        let mediaFrame = wp.media({
            title: 'Select or Upload Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        mediaFrame.on('select', function() {
            let attachment = mediaFrame.state().get('selection').first().toJSON();
            inputField.val(attachment.id);
            previewContainer.html(`<img src="${attachment.url}" style="max-width:100px;height:auto;">`);
        });

        mediaFrame.open();
    });
});
