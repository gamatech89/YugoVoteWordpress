/**
 * Quiz Grid Filter Logic
 * Filters quiz cards by category using data attributes
 */

document.addEventListener("DOMContentLoaded", function () {
  const filterButtons = document.querySelectorAll(".yuv-filter-btn");
  const quizCards = document.querySelectorAll(".yuv-quiz-card-entry");
  const visibleCountEl = document.getElementById("yuv-visible-count");

  if (filterButtons.length === 0 || quizCards.length === 0) return;

  // Filter function
  function filterQuizzes(category) {
    let visibleCount = 0;

    quizCards.forEach((card) => {
      const cardCategory = card.getAttribute("data-category");

      if (category === "all" || cardCategory === category) {
        card.style.display = "block";
        // Add fade-in animation
        card.classList.add("yuv-fade-in");
        setTimeout(() => card.classList.remove("yuv-fade-in"), 400);
        visibleCount++;
      } else {
        card.style.display = "none";
      }
    });

    // Update visible count
    if (visibleCountEl) {
      visibleCountEl.textContent = visibleCount;
    }

    return visibleCount;
  }

  // Attach click events to filter buttons
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const filterValue = this.getAttribute("data-filter");

      // Remove active class from all buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"));

      // Add active class to clicked button
      this.classList.add("active");

      // Filter cards
      const count = filterQuizzes(filterValue);

      // Show "no results" message if needed
      const grid = document.getElementById("yuv-quiz-grid");
      let emptyMsg = grid.querySelector(".yuv-quiz-empty");

      if (count === 0 && !emptyMsg) {
        grid.innerHTML = `
                    <div class="yuv-quiz-empty">
                        <i class="ri-survey-line" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                        <p>Nema dostupnih kvizova u ovoj kategoriji.</p>
                    </div>
                `;
      } else if (count > 0 && emptyMsg) {
        emptyMsg.remove();
      }
    });
  });

  // Handle quiz card clicks (launch quiz modal)
  function attachQuizCardListeners() {
    document.querySelectorAll(".yuv-quiz-card-link").forEach((link) => {
      // Skip if already has listener
      if (link.dataset.listenerAttached) return;
      link.dataset.listenerAttached = "true";

      link.addEventListener("click", function (e) {
        e.preventDefault();
        const quizId = this.getAttribute("data-quiz-id");

        if (!quizId) {
          console.error("Quiz ID missing from card");
          return;
        }

        if (!window.Quiz) {
          console.error("Quiz class not loaded yet");
          return;
        }

        if (!window.quizSettings || !window.quizSettings.apiUrl) {
          console.error("quizSettings not available");
          return;
        }

        // Close any existing quiz
        if (window.currentQuiz?.closeQuiz) {
          window.currentQuiz.closeQuiz();
        }

        // Launch new quiz
        try {
          window.currentQuiz = new window.Quiz(
            window.quizSettings.apiUrl,
            quizId
          );
        } catch (error) {
          console.error("Failed to launch quiz:", error);
        }
      });
    });
  }

  // Initial attachment
  attachQuizCardListeners();

  // Re-attach after filtering (in case new cards are shown)
  filterButtons.forEach((button) => {
    const originalClickHandler = button.onclick;
    button.onclick = function () {
      if (originalClickHandler) originalClickHandler.call(this);
      setTimeout(attachQuizCardListeners, 100);
    };
  });
});
