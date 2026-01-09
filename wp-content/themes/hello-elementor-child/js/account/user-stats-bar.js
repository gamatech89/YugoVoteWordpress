/**
 * User Stats Bar JavaScript
 * Handles live countdown timers for token regeneration
 */
(function () {
  "use strict";

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

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTimers);
  } else {
    initTimers();
  }
})();
