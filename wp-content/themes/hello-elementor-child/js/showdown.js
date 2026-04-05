/**
 * Showdown — Emergency Restoral JS (V5)
 * Simplified logic to match the stable HTML structure
 */

jQuery(document).ready(function($) {
    var $arena = $('#yuv-showdown-arena');
    if (!$arena.length) return;

    var items = $arena.data('items') || [];
    var showdownId = $arena.data('showdown-id');
    var status = $arena.data('status');
    var hasPlayed = $arena.data('has-played') === 1;

    if (hasPlayed || status === 'completed') return;

    var queue = [...items];
    var currentA, currentB;

    function nextMatchup() {
        if (queue.length < 2) {
            finishShowdown();
            return;
        }

        currentA = queue.shift();
        currentB = queue.shift();

        updateCards();
    }

    function updateCards() {
        // Simple update without complex animations first to ensure stability
        $('#sd-fighter-a-img').attr('src', currentA.image);
        $('#sd-fighter-a-name').text(currentA.name);
        
        $('#sd-fighter-b-img').attr('src', currentB.image);
        $('#sd-fighter-b-name').text(currentB.name);

        // Update progress
        var total = items.length;
        var remaining = queue.length + 2;
        var progress = ((total - remaining) / (total - 1)) * 100;
        $('#sd-progress-fill').css('width', progress + '%');
    }

    function handleVote(side) {
        var winner = (side === 'left') ? currentA : currentB;
        var loser = (side === 'left') ? currentB : currentA;

        // Simple winner stays in queue pattern
        queue.push(winner);
        nextMatchup();
    }

    function finishShowdown() {
        var winner = queue[0];
        $arena.fadeOut(400, function() {
            // Send to server
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'yuv_submit_showdown_winner',
                    showdown_id: showdownId,
                    winner_id: winner.id
                },
                success: function() {
                    location.reload(); // Simplest way to show results for now
                }
            });
        });
    }

    $('.sd-card').on('click', function() {
        var side = $(this).data('side');
        handleVote(side);
    });

    $('.sd-card__btn').on('click', function(e) {
        e.stopPropagation();
        var side = $(this).data('side');
        handleVote(side);
    });

    // Start
    nextMatchup();
});
