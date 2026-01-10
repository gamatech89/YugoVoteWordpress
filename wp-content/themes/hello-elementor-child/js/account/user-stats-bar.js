/**
 * User Stats Bar JavaScript
 * Handles live countdown timers and expand/collapse functionality
 */
(function () {
  "use strict";

  // Initialize expand/collapse toggle
  function initExpandToggle() {
    const bars = document.querySelectorAll('.ygv-user-stats-bar');
    
    bars.forEach((bar) => {
      const toggleBtn = bar.querySelector('.ygv-stats-expand');
      if (!toggleBtn) return;
      
      toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        bar.classList.toggle('is-expanded');
        
        // Save preference
        const isExpanded = bar.classList.contains('is-expanded');
        try {
          localStorage.setItem('ygv_stats_expanded', isExpanded ? '1' : '0');
        } catch (e) {}
      });
      
      // Restore saved preference
      try {
        const saved = localStorage.getItem('ygv_stats_expanded');
        if (saved === '1') {
          bar.classList.add('is-expanded');
        }
      } catch (e) {}
    });
  }

  // Find all timer elements
  function initTimers() {
    const timers = document.querySelectorAll(
      ".ygv-stats-timer[data-next-token]"
    );

    timers.forEach((timer) => {
      const seconds = parseInt(timer.dataset.nextToken, 10);
      if (seconds > 0) {
        startCountdown(timer, seconds);
      }
    });
  }

  function startCountdown(timerEl, initialSeconds) {
    let remaining = initialSeconds;
    const valueEl = timerEl.querySelector(".ygv-timer-value");

    if (!valueEl) return;

    const interval = setInterval(() => {
      remaining--;

      if (remaining <= 0) {
        clearInterval(interval);
        // Refresh to get new token count
        location.reload();
        return;
      }

      valueEl.textContent = formatTime(remaining);
    }, 1000);
  }

  function formatTime(seconds) {
    if (seconds < 60) {
      return seconds + "s";
    } else if (seconds < 3600) {
      return Math.floor(seconds / 60) + "m";
    } else {
      const hours = Math.floor(seconds / 3600);
      const mins = Math.floor((seconds % 3600) / 60);
      return hours + "h" + (mins > 0 ? " " + mins + "m" : "");
    }
  }

  // Initialize all
  function init() {
    initExpandToggle();
    initTimers();
  }

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
