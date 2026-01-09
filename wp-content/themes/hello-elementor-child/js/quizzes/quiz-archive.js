/**
 * Quiz Archive - AJAX Filtering, Sorting & Load More
 */

document.addEventListener("DOMContentLoaded", function () {
  const archive = document.querySelector(".yuv-quiz-archive");
  if (!archive) return;

  const grid = document.getElementById("yuv-quiz-grid");
  const loader = document.querySelector(".yuv-quiz-loader");
  const loadMoreBtn = document.getElementById("yuv-load-more-btn");
  const sortSelect = document.getElementById("yuv-sort-select");
  const filterButtons = document.querySelectorAll(".yuv-filter-btn");
  const visibleCount = document.getElementById("yuv-visible-count");
  const totalCount = document.getElementById("yuv-total-count");

  let currentPage = 1;
  let currentCategory = "";
  let currentSort = "latest";
  let maxPages = parseInt(archive.dataset.maxPages) || 1;
  let perPage = parseInt(archive.dataset.perPage) || 9;
  let isLoading = false;

  // Helper: Show/Hide Loader
  function toggleLoader(show) {
    if (loader) {
      loader.style.display = show ? "flex" : "none";
    }
  }

  // Helper: Show/Hide Load More Button
  function updateLoadMoreButton() {
    if (loadMoreBtn) {
      loadMoreBtn.style.display = currentPage >= maxPages ? "none" : "block";
    }
  }

  // Helper: Update Result Count
  function updateResultCount(visible, total) {
    if (visibleCount) visibleCount.textContent = visible;
    if (totalCount) totalCount.textContent = total;
  }

  // Load Quizzes via AJAX
  async function loadQuizzes(append = false) {
    if (isLoading) return;
    isLoading = true;

    toggleLoader(true);
    if (loadMoreBtn) loadMoreBtn.disabled = true;

    try {
      const formData = new FormData();
      formData.append("action", "yuv_load_quizzes");
      formData.append("page", currentPage);
      formData.append("per_page", perPage);
      formData.append("category", currentCategory);
      formData.append("sort_by", currentSort);

      const response = await fetch(yuvAjax.ajaxurl, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        if (append) {
          // Append to existing grid
          grid.insertAdjacentHTML("beforeend", result.data.html);
        } else {
          // Replace grid content
          grid.innerHTML = result.data.html;
        }

        // Update pagination data
        maxPages = result.data.max_pages;
        const totalVisible = append
          ? grid.querySelectorAll(".yuv-quiz-card-entry").length
          : result.data.posts_count;

        updateResultCount(totalVisible, result.data.total_posts);
        updateLoadMoreButton();

        // Re-attach quiz card listeners
        attachQuizCardListeners();
      } else {
        if (!append) {
          const message = result.data?.message || "Nema dostupnih kvizova.";
          grid.innerHTML = `
                        <div class="yuv-quiz-empty">
                            <i class="ri-survey-line" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                            <p>${message}</p>
                        </div>
                    `;
        }
        if (loadMoreBtn) loadMoreBtn.style.display = "none";
      }
    } catch (error) {
      console.error("AJAX Load Error:", error);
      grid.innerHTML = `
                <div class="yuv-quiz-empty">
                    <i class="ri-error-warning-line" style="font-size: 4rem; color: #dc3545; margin-bottom: 20px;"></i>
                    <p>Došlo je do greške. Molimo pokušajte ponovo.</p>
                </div>
            `;
    } finally {
      toggleLoader(false);
      if (loadMoreBtn) loadMoreBtn.disabled = false;
      isLoading = false;
    }
  }

  // Attach listeners to quiz cards
  function attachQuizCardListeners() {
    document.querySelectorAll(".yuv-quiz-card-link").forEach((link) => {
      if (link.dataset.listenerAttached) return;
      link.dataset.listenerAttached = "true";

      link.addEventListener("click", function (e) {
        e.preventDefault();
        const quizId = this.getAttribute("data-quiz-id");

        if (!quizId || !window.Quiz || !window.quizSettings) {
          console.error("Quiz launch error: Missing dependencies");
          return;
        }

        // Close any existing quiz
        if (window.currentQuiz?.closeQuiz) {
          window.currentQuiz.closeQuiz();
        }

        // Launch new quiz
        window.currentQuiz = new window.Quiz(
          window.quizSettings.apiUrl,
          quizId
        );
      });
    });
  }

  // Event: Category Filter Click
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const filterValue = this.getAttribute("data-filter");

      // Update active state
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");

      // Reset and reload
      currentCategory = filterValue === "all" ? "" : filterValue;
      currentPage = 1;
      loadQuizzes(false);
    });
  });

  // Event: Sort Change
  if (sortSelect) {
    sortSelect.addEventListener("change", function () {
      currentSort = this.value;
      currentPage = 1;
      loadQuizzes(false);
    });
  }

  // Event: Load More Click
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", function () {
      currentPage++;
      loadQuizzes(true); // Append mode
    });
  }

  // Initial attachment
  attachQuizCardListeners();
});
