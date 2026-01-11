<?php
if (!defined('ABSPATH')) exit;
// Promenljive dostupne ovde: $poll_id, $question, $answers, $total, $has_voted

// Pripremi JSON podatke za rezultate (koristi se za AJAX update bez reload-a)
$results_data = [];
foreach ($answers as $idx => $ans) {
    $v = intval($ans['votes']);
    $p = ($total > 0) ? round(($v / $total) * 100) : 0;
    $results_data[$idx] = [
        'text' => esc_html($ans['text']),
        'votes' => $v,
        'percent' => $p
    ];
}
?>
<div class="ygv-poll <?php echo $has_voted ? 'ygv-poll--voted' : ''; ?>" 
     data-poll-id="<?php echo esc_attr($poll_id); ?>"
     data-nonce="<?php echo esc_attr(wp_create_nonce('cs_poll_vote_nonce')); ?>"
     data-ajax-url="<?php echo esc_attr(admin_url('admin-ajax.php')); ?>">
    
    <h4 class="ygv-poll__question"><?php echo esc_html($question); ?></h4>
    
    <!-- Form View -->
    <form class="ygv-poll__form <?php echo $has_voted ? 'ygv-hidden' : ''; ?>">
        <div class="ygv-poll__options">
            <?php foreach ($answers as $idx => $ans) : ?>
                <label class="ygv-poll__option" data-index="<?php echo $idx; ?>">
                    <input type="radio" name="poll_choice_<?php echo $poll_id; ?>" value="<?php echo $idx; ?>">
                    <span class="ygv-poll__option-radio"></span>
                    <span class="ygv-poll__option-text"><?php echo esc_html($ans['text']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="ygv-poll__btn">
            <span class="ygv-poll__btn-text">Glasaj</span>
            <span class="ygv-poll__btn-loading">
                <svg class="ygv-spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4 31.4" stroke-linecap="round"/></svg>
            </span>
        </button>
    </form>
    
    <!-- Results View -->
    <div class="ygv-poll__results <?php echo !$has_voted ? 'ygv-hidden' : ''; ?>">
        <?php foreach ($answers as $idx => $ans) : 
            $v = intval($ans['votes']);
            $p = ($total > 0) ? round(($v / $total) * 100) : 0;
        ?>
            <div class="ygv-poll__result" data-index="<?php echo $idx; ?>">
                <div class="ygv-poll__result-header">
                    <span class="ygv-poll__result-text"><?php echo esc_html($ans['text']); ?></span>
                    <span class="ygv-poll__result-percent"><?php echo $p; ?>%</span>
                </div>
                <div class="ygv-poll__result-bar">
                    <div class="ygv-poll__result-fill" style="width: <?php echo $p; ?>%"></div>
                </div>
                <span class="ygv-poll__result-votes"><?php echo $v; ?> glasova</span>
            </div>
        <?php endforeach; ?>
        
        <div class="ygv-poll__total">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Ukupno: <strong class="ygv-poll__total-count"><?php echo $total; ?></strong> glasova</span>
        </div>
    </div>
</div>