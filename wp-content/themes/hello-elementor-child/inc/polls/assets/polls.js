/**
 * YugoVote Polls Module
 * Handles AJAX voting without page reload
 */
(function () {
  "use strict";

  // Initialize all polls on page
  function initPolls() {
    const polls = document.querySelectorAll(".ygv-poll");
    polls.forEach(initPoll);
  }

  // Initialize single poll
  function initPoll(pollEl) {
    const form = pollEl.querySelector(".ygv-poll__form");
    if (!form) return; // Already voted

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      handleVote(pollEl, form);
    });
  }

  // Handle vote submission
  function handleVote(pollEl, form) {
    const pollId = pollEl.dataset.pollId;
    const nonce = pollEl.dataset.nonce;
    const ajaxUrl = pollEl.dataset.ajaxUrl;

    const selectedOption = form.querySelector('input[type="radio"]:checked');
    if (!selectedOption) {
      showNotification("Izaberite opciju pre glasanja!", "warning");
      return;
    }

    const btn = form.querySelector(".ygv-poll__btn");
    const answerIndex = selectedOption.value;

    // Show loading state
    btn.classList.add("ygv-poll__btn--loading");
    btn.disabled = true;

    // Send AJAX request
    fetch(ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "cs_vote_poll",
        poll_id: pollId,
        answer_index: answerIndex,
        nonce: nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update UI to show results
          showResults(pollEl, data.data);
          showNotification("Hvala na glasanju!", "success");
        } else {
          showNotification(
            data.data.message || "Greška prilikom glasanja.",
            "error"
          );
          btn.classList.remove("ygv-poll__btn--loading");
          btn.disabled = false;
        }
      })
      .catch((error) => {
        console.error("Poll vote error:", error);
        showNotification("Greška u komunikaciji sa serverom.", "error");
        btn.classList.remove("ygv-poll__btn--loading");
        btn.disabled = false;
      });
  }

  // Show results after voting
  function showResults(pollEl, data) {
    const form = pollEl.querySelector(".ygv-poll__form");
    const results = pollEl.querySelector(".ygv-poll__results");

    if (!results) {
      // Fallback: reload page if results div doesn't exist
      location.reload();
      return;
    }

    // Update result bars with new data
    if (data.results) {
      data.results.forEach((result, idx) => {
        const resultEl = results.querySelector(`[data-index="${idx}"]`);
        if (resultEl) {
          const percentEl = resultEl.querySelector(".ygv-poll__result-percent");
          const fillEl = resultEl.querySelector(".ygv-poll__result-fill");
          const votesEl = resultEl.querySelector(".ygv-poll__result-votes");

          if (percentEl) percentEl.textContent = result.percent + "%";
          if (fillEl) fillEl.style.width = result.percent + "%";
          if (votesEl) votesEl.textContent = result.votes + " glasova";
        }
      });

      // Update total
      const totalEl = results.querySelector(".ygv-poll__total-count");
      if (totalEl) totalEl.textContent = data.total;
    }

    // Animate transition
    form.style.opacity = "0";
    form.style.transform = "translateY(-10px)";

    setTimeout(() => {
      form.classList.add("ygv-hidden");
      results.classList.remove("ygv-hidden");
      results.style.opacity = "0";
      results.style.transform = "translateY(10px)";

      // Force reflow
      results.offsetHeight;

      results.style.transition = "opacity 0.3s ease, transform 0.3s ease";
      results.style.opacity = "1";
      results.style.transform = "translateY(0)";
    }, 200);

    pollEl.classList.add("ygv-poll--voted");
  }

  // Show notification toast
  function showNotification(message, type) {
    // Remove existing notification
    const existing = document.querySelector(".ygv-poll-notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `ygv-poll-notification ygv-poll-notification--${type}`;
    notification.innerHTML = `
            <span class="ygv-poll-notification__icon">
                ${type === "success" ? "✓" : type === "warning" ? "!" : "✕"}
            </span>
            <span class="ygv-poll-notification__text">${message}</span>
        `;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(
      () => notification.classList.add("ygv-poll-notification--visible"),
      10
    );

    // Remove after 3 seconds
    setTimeout(() => {
      notification.classList.remove("ygv-poll-notification--visible");
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  // ── Infinite scroll for poll archive ──────────────────────────────────
  function initInfiniteScroll() {
    const grid     = document.getElementById("ygv-poll-grid");
    const sentinel = document.getElementById("ygv-poll-sentinel");
    if (!grid || !sentinel) return;

    let loading = false;

    const observer = new IntersectionObserver(
      (entries) => {
        if (!entries[0].isIntersecting || loading) return;
        loadMorePolls();
      },
      { rootMargin: "200px" }
    );
    observer.observe(sentinel);

    function loadMorePolls() {
      const currentPage = parseInt(grid.dataset.page, 10);
      const maxPages    = parseInt(grid.dataset.maxPages, 10);
      const nextPage    = currentPage + 1;

      if (nextPage > maxPages) {
        observer.disconnect();
        sentinel.style.display = "none";
        return;
      }

      loading = true;
      sentinel.classList.add("ygv-poll-archive__sentinel--loading");

      fetch(grid.dataset.ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "cs_load_more_polls",
          nonce:  grid.dataset.nonce,
          page:   nextPage,
        }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (!data.success) return;

          const { html, has_more } = data.data;

          if (html) {
            const tmp = document.createElement("div");
            tmp.innerHTML = html;
            const newCards = Array.from(tmp.children);
            newCards.forEach((card) => {
              grid.appendChild(card);
              const poll = card.querySelector(".ygv-poll");
              if (poll) initPoll(poll.closest(".ygv-poll") || poll);
            });
            // Re-init polls inside newly appended cards
            grid.querySelectorAll(".ygv-poll:not([data-init])").forEach((p) => {
              p.dataset.init = "1";
              initPoll(p);
            });
          }

          grid.dataset.page = nextPage;

          if (!has_more) {
            observer.disconnect();
            sentinel.style.display = "none";
          }
        })
        .catch((err) => console.error("Load more polls error:", err))
        .finally(() => {
          loading = false;
          sentinel.classList.remove("ygv-poll-archive__sentinel--loading");
        });
    }
  }

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initPolls();
      initInfiniteScroll();
    });
  } else {
    initPolls();
    initInfiniteScroll();
  }

  // Export for external use
  window.YGVPolls = {
    init: initPolls,
    initPoll: initPoll,
  };
})();
