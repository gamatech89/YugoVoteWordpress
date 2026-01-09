<?php
if (!defined('ABSPATH')) exit;

// 1. Dodaj Meta Box
function cs_poll_add_meta_box() {
    add_meta_box(
        'cs_poll_answers_box',
        'Odgovori i Rezultati',
        'cs_poll_render_meta_box',
        'voting_poll',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cs_poll_add_meta_box');

// 2. Renderuj HTML
function cs_poll_render_meta_box($post) {
    $answers = get_post_meta($post->ID, '_cs_poll_answers', true);
    if (!is_array($answers)) {
        $answers = [['text' => '', 'votes' => 0], ['text' => '', 'votes' => 0]];
    }
    wp_nonce_field('cs_save_poll_data', 'cs_poll_nonce');
    ?>
    <div id="cs-poll-wrapper">
        <div id="cs-poll-answers-container">
            <?php foreach ($answers as $index => $answer) : ?>
                <div class="cs-poll-row" style="margin-bottom: 10px; display:flex; gap:10px; align-items:center;">
                    <input type="text" name="poll_answers[<?php echo $index; ?>][text]" value="<?php echo esc_attr($answer['text']); ?>" placeholder="Odgovor..." style="width: 60%;">
                    <input type="hidden" name="poll_answers[<?php echo $index; ?>][votes]" value="<?php echo intval($answer['votes']); ?>">
                    <span style="color:#666;">(Glasova: <strong><?php echo intval($answer['votes']); ?></strong>)</span>
                    <button type="button" class="button cs-remove-answer">X</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="cs-add-answer">+ Dodaj odgovor</button>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#cs-add-answer').click(function() {
            var count = $('.cs-poll-row').length;
            var html = `
                <div class="cs-poll-row" style="margin-bottom: 10px; display:flex; gap:10px; align-items:center;">
                    <input type="text" name="poll_answers[${count}][text]" placeholder="Novi odgovor..." style="width: 60%;">
                    <input type="hidden" name="poll_answers[${count}][votes]" value="0">
                    <span style="color:#666;">(Glasova: <strong>0</strong>)</span>
                    <button type="button" class="button cs-remove-answer">X</button>
                </div>`;
            $('#cs-poll-answers-container').append(html);
        });
        $(document).on('click', '.cs-remove-answer', function() {
            $(this).closest('.cs-poll-row').remove();
        });
    });
    </script>
    <?php
}

// 3. Sačuvaj
function cs_save_poll_meta($post_id) {
    if (!isset($_POST['cs_poll_nonce']) || !wp_verify_nonce($_POST['cs_poll_nonce'], 'cs_save_poll_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    if (isset($_POST['poll_answers'])) {
        $clean = [];
        $total = 0;
        foreach ($_POST['poll_answers'] as $item) {
            if (!empty(trim($item['text']))) {
                $votes = intval($item['votes']);
                $clean[] = ['text' => sanitize_text_field($item['text']), 'votes' => $votes];
                $total += $votes;
            }
        }
        update_post_meta($post_id, '_cs_poll_answers', $clean);
        update_post_meta($post_id, '_cs_poll_total_votes', $total);
    }
}
add_action('save_post', 'cs_save_poll_meta');