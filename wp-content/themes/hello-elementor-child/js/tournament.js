/**
 * Tournament Duel Arena - Clean & Functional JavaScript
 * Created: 2025-12-28
 */

(function ($) {
  "use strict";

  // Tournament Duel functionality
  const TournamentDuel = {
    init: function () {
      this.bindVoting();
      this.bindNavigation();
      this.startTimer();
    },

    /**
     * Handle vote button clicks
     */
    bindVoting: function () {
      $(document).on("click", ".yuv-vote-btn", function (e) {
        e.preventDefault();

        const $btn = $(this);
        const $arena = $(".yuv-duel-arena");
        const itemId = $btn.data("item-id");
        const matchId = $arena.data("match-id");
        const tournamentId = $arena.data("tournament-id");

        if (!itemId || !matchId) {
          alert("Greška: nedostaju podaci");
          return;
        }

        // Disable all buttons
        $(".yuv-vote-btn").prop("disabled", true).css("opacity", "0.5");

        // Cast vote via AJAX
        $.ajax({
          url: yuvTournament.ajaxurl,
          method: "POST",
          data: {
            action: "yuv_cast_tournament_vote",
            match_id: matchId,
            item_id: itemId,
            tournament_id: tournamentId,
            nonce: yuvTournament.nonce,
          },
          success: function (response) {
            if (response.success) {
              TournamentDuel.showResults(response.data);
            } else {
              alert(response.data.message || "Greška pri glasanju");
              $(".yuv-vote-btn").prop("disabled", false).css("opacity", "1");
            }
          },
          error: function () {
            alert("Greška u komunikaciji sa serverom");
            $(".yuv-vote-btn").prop("disabled", false).css("opacity", "1");
          },
        });
      });
    },

    /**
     * Display voting results
     */
    showResults: function (data) {
      const $arena = $(".yuv-arena-wrapper");

      // Add results class
      $arena.addClass("yuv-show-results");

      // Update percentages and vote counts
      if (data.results) {
        data.results.forEach(function (result) {
          const $contender = $(
            '.yuv-contender[data-contender-id="' + result.item_id + '"]'
          );

          if ($contender.length) {
            // Update result bar
            $contender
              .find(".yuv-result-bar")
              .css("width", result.percent + "%");

            // Update text
            $contender.find(".yuv-result-percent").text(result.percent + "%");
            $contender
              .find(".yuv-result-votes")
              .text(result.votes + " glasova");

            // Mark winner
            if (result.is_winner) {
              $contender.addClass("is-winner");
            }
          }
        });
      }

      // Auto-advance to next match after 600ms
      if (data.next_match) {
        setTimeout(function () {
          TournamentDuel.loadMatch(data.next_match.match_id);
        }, 600);
      }
    },

    /**
     * Handle navigation thumbnail clicks
     */
    bindNavigation: function () {
      $(document).on("click", ".yuv-nav-item", function () {
        const matchId = $(this).data("match-id");

        if (matchId && !$(this).hasClass("current")) {
          TournamentDuel.loadMatch(matchId);
        }
      });
    },

    /**
     * Load a specific match via AJAX
     */
    loadMatch: function (matchId) {
      const $arena = $(".yuv-arena-wrapper");

      // Show loading state
      $arena.css("opacity", "0.5");

      $.ajax({
        url: yuvTournament.ajaxurl,
        method: "POST",
        data: {
          action: "yuv_load_match",
          match_id: matchId,
          nonce: yuvTournament.nonce,
        },
        success: function (response) {
          if (response.success && response.data.html) {
            // Replace arena content
            $arena.replaceWith(response.data.html);

            // Restart timer
            TournamentDuel.startTimer();

            // Scroll to top
            $("html, body").animate(
              {
                scrollTop: $("#yuv-arena").offset().top - 100,
              },
              600
            );
          } else {
            alert("Greška pri učitavanju meča");
            $arena.css("opacity", "1");
          }
        },
        error: function () {
          alert("Greška u komunikaciji sa serverom");
          $arena.css("opacity", "1");
        },
      });
    },

    /**
     * Countdown timer
     */
    startTimer: function () {
      const $timer = $(".yuv-timer-value");

      if (!$timer.length) return;

      const endTime = parseInt($timer.data("end"));

      if (!endTime || endTime <= 0) return;

      // Clear any existing interval
      if (window.yuvTimerInterval) {
        clearInterval(window.yuvTimerInterval);
      }

      // Update timer every second
      window.yuvTimerInterval = setInterval(function () {
        const now = Math.floor(Date.now() / 1000);
        const remaining = endTime - now;

        if (remaining <= 0) {
          $timer.text("00:00:00");
          clearInterval(window.yuvTimerInterval);
          return;
        }

        // Calculate hours, minutes, seconds
        const hours = Math.floor(remaining / 3600);
        const minutes = Math.floor((remaining % 3600) / 60);
        const seconds = remaining % 60;

        // Format with leading zeros
        const formatted =
          String(hours).padStart(2, "0") +
          ":" +
          String(minutes).padStart(2, "0") +
          ":" +
          String(seconds).padStart(2, "0");

        $timer.text(formatted);
      }, 1000);
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    TournamentDuel.init();
  });
})(jQuery);
