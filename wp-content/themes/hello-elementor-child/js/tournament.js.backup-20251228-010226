/**
 * Tournament Arena JavaScript - Seamless AJAX Experience
 * No page reloads, smooth transitions between matches
 * 
 * FIXED: Timer countdown, navigation clicks, result display
 */

jQuery(document).ready(function ($) {
  const $arenaContainer = $("#yuv-arena");
  
  if (!$arenaContainer.length) {
    console.log("YUV Tournament: Arena container not found");
    return;
  }

  // Initialize the arena
  initArena();

  /**
   * Initialize arena with current match data
   */
  function initArena() {
    const $duelArena = $(".yuv-duel-arena");
    
    if (!$duelArena.length) {
      return;
    }

    const matchId = $duelArena.data("match-id");
    const endTime = parseInt($duelArena.data("end-time"));
    const $arena = $(".yuv-arena-wrapper");
    const hasVoted = $arena.hasClass("yuv-show-results");

    console.log("YUV Tournament Init:", {
      matchId: matchId,
      endTime: endTime,
      hasVoted: hasVoted,
    });

    // If already voted, hide buttons and show results immediately
    if (hasVoted) {
      $(".yuv-vote-btn").hide();
      $(".yuv-result-overlay").show();
    } else {
      $(".yuv-vote-btn").show();
      $(".yuv-result-overlay").hide();
    }

    // FIX 3: Start countdown timer if not voted
    if (!hasVoted && endTime) {
      startCountdown(endTime);
    }

    // Bind vote buttons
    bindVoteButtons();

    // Bind navigation links
    bindNavigation();

    // Auto-scroll to current match in nav strip
    scrollToCurrentNav();
  }

  /**
   * FIX 3: Countdown Timer Implementation
   */
  function startCountdown(endTime) {
    const $timerEl = $("#yuv-duel-timer");
    
    if (!$timerEl.length) {
      return;
    }

    function updateTimer() {
      const now = Math.floor(Date.now() / 1000);
      const remaining = endTime - now;

      if (remaining <= 0) {
        $timerEl.text("00:00:00");
        clearInterval(timerInterval);
        return;
      }

      const hours = Math.floor(remaining / 3600);
      const minutes = Math.floor((remaining % 3600) / 60);
      const seconds = remaining % 60;

      $timerEl.text(
        String(hours).padStart(2, "0") + ":" +
        String(minutes).padStart(2, "0") + ":" +
        String(seconds).padStart(2, "0")
      );
    }

    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);
    
    // Store interval ID for cleanup
    $arenaContainer.data('timer-interval', timerInterval);
  }

  /**
   * Bind Vote Button Handlers
   */
  function bindVoteButtons() {
    // Remove old handlers to prevent duplicates
    $(document).off("click", ".yuv-vote-btn");
    
    $(document).on("click", ".yuv-vote-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $btn = $(this);
      const itemId = $btn.data("item-id");
      const $contender = $btn.closest(".yuv-contender");
      const $duelArena = $(".yuv-duel-arena");
      const matchId = $duelArena.data("match-id");

      // Validation
      if (!matchId || !itemId) {
        alert("Greška: Nevažeći podaci.");
        return;
      }

      // Disable all vote buttons
      $(".yuv-vote-btn").prop("disabled", true);
      $btn.html(
        '<span class="yuv-vote-icon">⏳</span><span class="yuv-vote-text">GLASANJE...</span>'
      );

      // Send vote via AJAX
      $.ajax({
        url: yuvTournamentData.ajaxurl,
        type: "POST",
        data: {
          action: "yuv_cast_tournament_vote",
          _ajax_nonce: yuvTournamentData.nonce,
          match_id: matchId,
          item_id: itemId,
        },
        success: function (response) {
          if (response.success) {
            // Show results immediately
            showResults($contender, response.data.results);

            // Show toast
            showToast("Tvoj glas je zabeležen!");

            // Wait 1.5 seconds, then load next match
            setTimeout(function () {
              loadNextMatch();
            }, 1500);
          } else {
            alert(response.data.message || "Greška pri glasanju.");
            $(".yuv-vote-btn").prop("disabled", false);
            $btn.html(
              '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("YUV Tournament: AJAX error:", error);
          alert("Greška pri glasanju. Pokušajte ponovo.");
          $(".yuv-vote-btn").prop("disabled", false);
          $btn.html(
            '<span class="yuv-vote-icon">⚡</span><span class="yuv-vote-text">GLASAJ</span>'
          );
        },
      });
    });
  }

  /**
   * Show Results - Update percentages and vote counts
   */
  function showResults($winningContender, results) {
    const $arena = $(".yuv-arena-wrapper");
    
    // Add results state class
    $arena.addClass("yuv-show-results");
    
    // Hide all buttons and show overlays
    $(".yuv-vote-btn").fadeOut(300);
    $(".yuv-result-overlay").fadeIn(600);
    
    // Mark winner
    if ($winningContender) {
      $winningContender.addClass("is-winner");
    }
    
    // Update each contender with results
    $(".yuv-contender").each(function () {
      const $cont = $(this);
      const contId = $cont.data("contender-id");
      const result = results.find((r) => r.id == contId);

      if (result) {
        $cont.find(".yuv-percent").text(result.percent + "%");
        $cont.find(".yuv-vote-count").text(result.votes.toLocaleString() + " glasova");
        $cont.find(".yuv-result-bar").css("width", result.percent + "%");
      }
    });
  }

  /**
   * Load Next Match via AJAX (no match_id = auto-find next unvoted)
   */
  function loadNextMatch(specificMatchId) {
    $.ajax({
      url: yuvTournamentData.ajaxurl,
      type: "POST",
      data: {
        action: "yuv_load_tournament_match_html",
        match_id: specificMatchId || null, // null = auto-find next
      },
      success: function (response) {
        if (response.success) {
          // Replace arena HTML
          $arenaContainer.html(response.data.html);
          
          // Re-initialize everything
          initArena();
          
          // Smooth scroll to arena (no jump)
          $("html, body").animate({
            scrollTop: $arenaContainer.offset().top - 100
          }, 400);
        } else {
          // Stage complete or no more matches
          if (response.data.stage_complete) {
            const html = '<div class="yuv-stage-complete">' +
              '<div class="yuv-complete-icon">🏆</div>' +
              '<h2>Završili ste sve trenutne duelove!</h2>' +
              '<p>Vratite se kasnije za sledeću rundu.</p>' +
              '<a href="' + window.location.origin + '" class="yuv-btn-primary">Nazad na početnu</a>' +
              '</div>';
            $arenaContainer.html(html);
          } else {
            alert(response.data.message || "Nema više mečeva");
          }
        }
      },
      error: function () {
     FIX 5: Bind Navigation Links (thumbnails at bottom)
   */
  function bindNavigation() {
    // Remove old handlers
    $(document).off("click", ".yuv-nav-item");
    
    console.log("YUV Tournament: Binding navigation to", $(".yuv-nav-item").length, "items");
    
    // Use event delegation on the nav strip container
    // Remove old handlers
    $(document).off("click", ".yuv-nav-item");
    
    console.log("YUV Tournament: Binding navigation to", $(".yuv-nav-item").length, "items");
    
    // Use event delegation on the nav strip container for better performance
    $(".yuv-nav-strip").on("click", ".yuv-nav-item", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const $item = $(this);
      
      // Get match ID from data attribute
      const matchId = $item.data("match-id");

      console.log("YUV Tournament: Nav item clicked, match ID:", matchId);

      if (!matchId) {
        console.error("No match ID found on nav item", $item);
        return;
      }

      // Clean up timer if exists
      const timerId = $arenaContainer.data('timer-interval');
      if (timerId) {
        clearInterval(timerId);
      }
      
      // Load specific match via AJAX (pure carousel behavior)
      loadNextMatch(parseInt(matchId));
    });
  }

  /**
   * Auto-scroll to current match in nav strip
   */
  function scrollToCurrentNav() {
    const $currentNavItem = $(".yuv-nav-item.current");
    if ($currentNavItem.length) {
      const $navStrip = $(".yuv-nav-strip");
      const scrollLeft =
        $currentNavItem.offset().left -
        $navStrip.offset().left -
        $navStrip.width() / 2 +
        $currentNavItem.width() / 2;
      $navStrip.scrollLeft($navStrip.scrollLeft() + scrollLeft);
    }
  }

  /**
   * Toast Notification
   */
  function showToast(message) {
    // Create toast if doesn't exist
    let $toast = $("#yuv-vote-toast");
    if (!$toast.length) {
      $toast = $('<div id="yuv-vote-toast" class="yuv-vote-toast" style="display:none;">' +
        '<div class="yuv-toast-icon">✓</div>' +
        '<div class="yuv-toast-message"></div>' +
        '</div>');
      $("body").append($toast);
    }

    $toast.find(".yuv-toast-message").text(message);
    $toast.show().removeClass("hiding");

    setTimeout(function () {
      $toast.addClass("hiding");
      setTimeout(function () {
        $toast.hide();
      }, 400);
    }, 2500);
  }
});

  // ========================================================================
  // RESULT BARS ANIMATION (if already voted)
  // ========================================================================

  if (hasVoted) {
    setTimeout(function () {
      $(".yuv-result-bar").each(function () {
        const bar = $(this);
        const targetWidth = bar.css("width");

        // Start from 0 and animate to target width
        bar.css("width", "0");
        setTimeout(function () {
          bar.css("width", targetWidth);
        }, 100);
      });
    }, 300);
  }
});
