/**
 * VotingListV2 Class
 * Handles voting interactions for the V2 templates (Grid, Compact, Classic layouts)
 */

class VotingListV2 {
  constructor($container) {
    this.$container = $container;
    this.listId = $container.data("list-id");
    this.layout = $container.data("layout") || "grid";
    this.ajaxurl = voting_list_vars.ajaxurl;
    this.nonce = voting_list_vars.nonce;

    // Item selector depends on layout
    this.itemSelectors = {
      grid: ".ygv-vote-card",
      compact: ".ygv-vote-item",
      classic: ".ygv-vote-classic-card",
    };

    this.init();
  }

  init() {
    this.bindVoteButtons();
    this.bindScoreDisplay();
  }

  bindVoteButtons() {
    const self = this;

    // Handle vote button clicks
    this.$container.on(
      "click",
      ".ygv-vote-btn:not(.ygv-vote-btn--disabled)",
      function (e) {
        e.preventDefault();
        const $btn = jQuery(this);
        const $item = $btn.closest("[data-item-id]");
        const itemId = $item.data("item-id");
        const score = $btn.data("value");

        if (!itemId || !score) return;

        self.submitVote(itemId, score, $btn, $item);
      }
    );
  }

  bindScoreDisplay() {
    // Update score displays on load with any existing data
    this.$container.find("[data-item-id]").each((_, el) => {
      const $item = jQuery(el);
      const currentScore = $item.find(".ygv-score-value").data("score");
      if (currentScore) {
        this.updateScoreDisplay($item, currentScore);
      }
    });
  }

  submitVote(itemId, score, $btn, $item) {
    const self = this;

    // Add loading state
    $btn.addClass("ygv-vote-btn--loading");
    this.$container
      .find(`[data-item-id="${itemId}"] .ygv-vote-btn`)
      .addClass("ygv-vote-btn--disabled");

    jQuery.ajax({
      url: this.ajaxurl,
      type: "POST",
      data: {
        action: "submit_vote",
        nonce: this.nonce,
        list_id: this.listId,
        item_id: itemId,
        score: score,
      },
      success: function (response) {
        if (response.success) {
          // Update score
          const newScore =
            response.data.new_total_score || response.data.totalScore;
          self.updateItemScore($item, newScore, score);

          // Show success feedback
          self.showVoteFeedback($btn, "success");

          // Update ranking if provided
          if (response.data.rankings) {
            self.updateRankings(response.data.rankings);
          }
        } else {
          self.showVoteFeedback(
            $btn,
            "error",
            response.data?.message || "Vote failed"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Vote error:", error);
        self.showVoteFeedback($btn, "error", "Network error");
      },
      complete: function () {
        // Remove loading state
        $btn.removeClass("ygv-vote-btn--loading");
        self.$container
          .find(`[data-item-id="${itemId}"] .ygv-vote-btn`)
          .removeClass("ygv-vote-btn--disabled");
      },
    });
  }

  updateItemScore($item, newScore, userVote) {
    // Update score display
    const $scoreValue = $item.find(".ygv-score-value");
    $scoreValue.text(parseFloat(newScore).toFixed(1)).data("score", newScore);

    // Mark the voted button as active
    $item.find(".ygv-vote-btn").removeClass("ygv-vote-btn--active");
    $item
      .find(`.ygv-vote-btn[data-value="${userVote}"]`)
      .addClass("ygv-vote-btn--active");

    // Add voted indicator to item
    $item.addClass("ygv-item--voted");
  }

  updateScoreDisplay($item, score) {
    const $scoreValue = $item.find(".ygv-score-value");
    $scoreValue.text(parseFloat(score).toFixed(1));
  }

  updateRankings(rankings) {
    // Re-order items based on new rankings
    const $items = this.$container.find("[data-item-id]");
    const itemSelector = this.itemSelectors[this.layout] || "[data-item-id]";

    rankings.forEach((rank, index) => {
      const $item = this.$container.find(`[data-item-id="${rank.item_id}"]`);
      if ($item.length) {
        // Update rank number
        const newRanking = index + 1;
        $item
          .find(".ygv-vote-card__rank, .ygv-rank-badge, .ygv-classic-rank")
          .text(newRanking);

        // Update top 3 classes
        $item.removeClass("ygv-rank-1 ygv-rank-2 ygv-rank-3 ygv-item--top3");
        if (newRanking <= 3) {
          $item.addClass(`ygv-rank-${newRanking} ygv-item--top3`);
        }
      }
    });
  }

  showVoteFeedback($btn, type, message = "") {
    // Create feedback element
    const $feedback = jQuery('<span class="ygv-vote-feedback"></span>');

    if (type === "success") {
      $feedback
        .addClass("ygv-vote-feedback--success")
        .html(
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>'
        );
    } else {
      $feedback.addClass("ygv-vote-feedback--error").text("✕");
    }

    $btn.parent().append($feedback);

    // Animate and remove
    setTimeout(() => {
      $feedback.addClass("ygv-vote-feedback--show");
    }, 10);

    setTimeout(() => {
      $feedback.removeClass("ygv-vote-feedback--show");
      setTimeout(() => $feedback.remove(), 200);
    }, 1500);
  }
}

// Expose to global scope
window.VotingListV2 = VotingListV2;
