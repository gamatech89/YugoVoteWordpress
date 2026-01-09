/**
 * Tournament Tinder-Style Voting
 * No redirects - smooth animations between matches
 */

jQuery(document).ready(function ($) {
  const arena = $(".yuv-duel-arena");
  if (!arena.length) return;

  let currentMatchId = arena.data("match-id");
  let tournamentId = arena.data("tournament-id");
  let stage = arena.data("stage");
  let endTime = parseInt(arena.data("end-time"));
  const hasVoted = arena.data("user-voted") === "true";

  // Progress tracking - from server (database)
  let totalMatches = parseInt(arena.data("total-matches") || 0);
  let votedMatches = parseInt(arena.data("voted-matches") || 0);

  updateProgressBar();

  // ========================================================================
  // COUNTDOWN TIMER
  // ========================================================================

  function updateTimer() {
    const now = Math.floor(Date.now() / 1000);
    const remaining = endTime - now;

    if (remaining <= 0) {
      $("#timer-hours").text("00");
      $("#timer-minutes").text("00");
      $("#timer-seconds").text("00");
      $(".yuv-vote-btn").prop("disabled", true).text("VREME ISTEKLO");
      return;
    }

    const hours = Math.floor(remaining / 3600);
    const minutes = Math.floor((remaining % 3600) / 60);
    const seconds = remaining % 60;

    $("#timer-hours").text(String(hours).padStart(2, "0"));
    $("#timer-minutes").text(String(minutes).padStart(2, "0"));
    $("#timer-seconds").text(String(seconds).padStart(2, "0"));
  }

  if (!hasVoted) {
    updateTimer();
    setInterval(updateTimer, 1000);
  }

  // ========================================================================
  // PROGRESS BAR
  // ========================================================================

  function updateProgressBar() {
    const $progressBar = $("#yuv-progress-bar");
    const $progressText = $("#yuv-progress-text");

    if ($progressBar.length) {
      const percentage =
        totalMatches > 0 ? (votedMatches / totalMatches) * 100 : 0;
      $progressBar.find(".yuv-progress-fill").css("width", percentage + "%");
      $progressText.text(`${votedMatches}/${totalMatches} duels completed`);
    }
  }

  // ========================================================================
  // VOTE BUTTON HANDLER - CAROUSEL STYLE (delegated)
  // ========================================================================

  $(document).on("click", ".yuv-vote-btn", function (e) {
    e.preventDefault();

    const btn = $(this);
    const itemId = btn.data("item-id");
    const contender = btn.closest(".yuv-contender");

    // Prevent double-clicks
    if (btn.prop("disabled")) return;

    // Disable all vote buttons
    $(".yuv-vote-btn").prop("disabled", true);
    btn.html(
      '<span class="yuv-vote-icon">⏳</span><span class="yuv-vote-text">GLASANJE...</span>'
    );

    // Send AJAX vote
    $.ajax({
      url: yuvTournamentData.ajaxurl,
      type: "POST",
      data: {
        action: "yuv_cast_tournament_vote",
        _ajax_nonce: yuvTournamentData.nonce,
        match_id: currentMatchId,
        item_id: itemId,
      },
      success: function (response) {
        if (response.success) {
          // Animate winner (scale up) and loser (fade out)
          const $winner = contender;
          const $loser = contender.siblings(".yuv-contender");

          $winner.addClass("yuv-winner-animation");
          $loser.addClass("yuv-loser-animation");

          // Auto-reload after animation to show next unvoted match (swipe effect)
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          alert(response.data.message || "Greška pri glasanju.");
          $(".yuv-vote-btn").prop("disabled", false);
          btn.html(
            '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
          );
        }
      },
      error: function () {
        alert("Greška pri glasanju. Pokušajte ponovo.");
        $(".yuv-vote-btn").prop("disabled", false);
        btn.html(
          '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
        );
      },
    });
  });

  // ========================================================================
  // LOAD NEXT MATCH
  // ========================================================================

  function loadNextMatch(matchData) {
    if (!matchData) return;

    // Update current match data
    currentMatchId = matchData.match_id;
    endTime = matchData.end_time;

    // Slide out current content
    arena.addClass("yuv-slide-out");

    setTimeout(function () {
      // Update content with new match data
      updateArenaContent(matchData);

      // Slide in new content
      arena.removeClass("yuv-slide-out").addClass("yuv-slide-in");

      setTimeout(function () {
        arena.removeClass("yuv-slide-in");
      }, 600);
    }, 600);

    // Restart timer
    updateTimer();
  }

  // ========================================================================
  // UPDATE ARENA CONTENT
  // ========================================================================

  function updateArenaContent(data) {
    const contenders = data.contenders;
    if (!contenders || contenders.length < 2) return;

    const item1 = contenders[0];
    const item2 = contenders[1];

    // Update match number with stage name
    const stageNames = {
      of: "OSMINA FINALA",
      qf: "ČETVRTFINALE",
      sf: "POLUFINALE",
      final: "FINALE",
    };
    const stageName = stageNames[data.stage] || "DUEL";
    $(".yuv-arena-header h2").text(`${stageName} ${data.match_number || ""}`);

    // Update arena data attributes
    arena.attr({
      "data-match-id": currentMatchId,
      "data-end-time": endTime,
      "data-user-voted": "false",
    });

    // Update contenders
    const $contenders = $(".yuv-contender");

    // Update first contender
    const $contender1 = $contenders.eq(0);
    $contender1.removeClass("yuv-winner-animation yuv-loser-animation");
    const $img1 = $contender1.find(".yuv-contender-img");
    $img1.attr("src", item1.image + "?t=" + Date.now());
    $contender1.find(".yuv-contender-name").text(item1.name);
    $contender1.find(".yuv-contender-desc").text(item1.description);
    $contender1
      .find(".yuv-vote-btn")
      .attr("data-item-id", item1.id)
      .prop("disabled", false)
      .html(
        '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
      );

    // Update second contender
    const $contender2 = $contenders.eq(1);
    $contender2.removeClass("yuv-winner-animation yuv-loser-animation");
    const $img2 = $contender2.find(".yuv-contender-img");
    $img2.attr("src", item2.image + "?t=" + Date.now());
    $contender2.find(".yuv-contender-name").text(item2.name);
    $contender2.find(".yuv-contender-desc").text(item2.description);
    $contender2
      .find(".yuv-vote-btn")
      .attr("data-item-id", item2.id)
      .prop("disabled", false)
      .html(
        '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
      );
  }

  // ========================================================================
  // SHOW STAGE COMPLETE
  // ========================================================================

  function showStageComplete() {
    arena.html(`
      <div class="yuv-stage-complete">
        <div class="yuv-complete-icon">✓</div>
        <h2>Završili ste sve mečeve u ovoj fazi!</h2>
        <p>Vratite se kasnije za sledeću rundu.</p>
        <a href="${window.location.origin}" class="yuv-btn-primary">Nazad na početnu</a>
      </div>
    `);
  }

  // ========================================================================
  // SHOW FINAL BRACKET (deprecated)
  // ========================================================================

  function showFinalBracket() {
    arena.addClass("yuv-slide-out");

    setTimeout(function () {
      // Hide arena
      arena.hide();

      // Show bracket
      const $bracket = $("#bracket-results");
      if ($bracket.length) {
        $bracket.show();
        $("html, body").animate(
          {
            scrollTop: $bracket.offset().top - 100,
          },
          500
        );
      } else {
        // Fallback: reload to show results
        location.reload();
      }
    }, 600);
  }

  // ========================================================================
  // NAVIGATION DOTS
  // ========================================================================

  function createNavigationDots() {
    if (totalMatches <= 1) return;

    const $nav = $("<div>", { class: "yuv-match-navigation" });

    for (let i = 0; i < totalMatches; i++) {
      const $dot = $("<span>", {
        class:
          "yuv-nav-dot" +
          (i < votedMatches ? " completed" : "") +
          (i === votedMatches ? " active" : ""),
      });
      $nav.append($dot);
    }

    arena.after($nav);
  }

  createNavigationDots();
});
