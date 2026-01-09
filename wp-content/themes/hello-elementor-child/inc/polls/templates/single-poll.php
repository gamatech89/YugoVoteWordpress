<?php
if (!defined('ABSPATH')) exit;
// Promenljive dostupne ovde: $poll_id, $question, $answers, $total, $has_voted
?>
<div class="cs-poll-box <?php echo $has_voted ? 'voted' : ''; ?>" data-id="<?php echo $poll_id; ?>">
    <h4 class="cs-poll-question"><?php echo esc_html($question); ?></h4>
    
    <?php if (!$has_voted) : ?>
        <form class="cs-poll-form">
            <?php foreach ($answers as $idx => $ans) : ?>
                <label class="cs-poll-option">
                    <input type="radio" name="poll_choice" value="<?php echo $idx; ?>">
                    <span class="cs-poll-opt-text"><?php echo esc_html($ans['text']); ?></span>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="cs-btn-vote">Glasaj</button>
        </form>
    <?php else : ?>
        <div class="cs-poll-results">
            <?php foreach ($answers as $ans) : 
                $v = intval($ans['votes']);
                $p = ($total > 0) ? round(($v / $total) * 100) : 0;
            ?>
                <div class="cs-poll-result-item">
                    <div class="cs-poll-res-info">
                        <span class="cs-res-label"><?php echo esc_html($ans['text']); ?></span>
                        <span class="cs-res-val"><?php echo $p; ?>%</span>
                    </div>
                    <div class="cs-poll-bar-bg">
                        <div class="cs-poll-bar-fill" style="width:<?php echo $p; ?>%"></div>
                    </div>
                    <div class="cs-poll-votes-count"><?php echo $v; ?> glasova</div>
                </div>
            <?php endforeach; ?>
            <div class="cs-poll-meta">Ukupno glasova: <?php echo $total; ?></div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $(document).off('submit', '.cs-poll-form').on('submit', '.cs-poll-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var box = form.closest('.cs-poll-box');
        var choice = form.find('input:checked').val();
        var btn = form.find('.cs-btn-vote');
        
        if(!choice) { alert('Izaberite opciju pre glasanja!'); return; }

        btn.prop('disabled', true).text('Glasanje...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'cs_vote_poll',
                poll_id: box.data('id'),
                answer_index: choice,
                nonce: '<?php echo wp_create_nonce('cs_poll_vote_nonce'); ?>'
            },
            success: function(res) {
                if(res.success) {
                    location.reload(); 
                } else {
                    alert(res.data.message);
                    btn.prop('disabled', false).text('Glasaj');
                }
            }
        });
    });
});
</script>