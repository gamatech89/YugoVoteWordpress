/**
 * Top Categories Carousel
 * Horizontal slider for category cards
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    const $carousel = $(".yuv-categories-carousel");
    if (!$carousel.length) return;

    const $track = $carousel.find(".yuv-carousel-track");
    const $prevBtn = $carousel.find(".yuv-carousel-prev");
    const $nextBtn = $carousel.find(".yuv-carousel-next");
    const $cards = $track.find(".yuv-cat-card");

    if ($cards.length === 0) return;

    let currentIndex = 0;
    const cardWidth = $cards.first().outerWidth(true);
    const visibleCards = Math.floor($carousel.width() / cardWidth);
    const maxIndex = Math.max(0, $cards.length - visibleCards);

    // Update button states
    function updateButtons() {
      $prevBtn.prop("disabled", currentIndex === 0);
      $nextBtn.prop("disabled", currentIndex >= maxIndex);
    }

    // Slide to index
    function slideTo(index) {
      currentIndex = Math.max(0, Math.min(index, maxIndex));
      const offset = -currentIndex * cardWidth;
      $track.css("transform", `translateX(${offset}px)`);
      updateButtons();
    }

    // Navigation
    $prevBtn.on("click", function () {
      slideTo(currentIndex - 1);
    });

    $nextBtn.on("click", function () {
      slideTo(currentIndex + 1);
    });

    // Touch/swipe support
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    $track.on("touchstart mousedown", function (e) {
      isDragging = true;
      startX = e.type === "touchstart" ? e.touches[0].clientX : e.clientX;
      $track.css("transition", "none");
    });

    $(document).on("touchmove mousemove", function (e) {
      if (!isDragging) return;
      currentX = e.type === "touchmove" ? e.touches[0].clientX : e.clientX;
      const diff = currentX - startX;
      const offset = -currentIndex * cardWidth + diff;
      $track.css("transform", `translateX(${offset}px)`);
    });

    $(document).on("touchend mouseup", function () {
      if (!isDragging) return;
      isDragging = false;
      $track.css("transition", "");

      const diff = currentX - startX;
      if (Math.abs(diff) > cardWidth / 3) {
        if (diff > 0) {
          slideTo(currentIndex - 1);
        } else {
          slideTo(currentIndex + 1);
        }
      } else {
        slideTo(currentIndex);
      }
    });

    // Initial state
    updateButtons();

    // Responsive: recalculate on resize
    let resizeTimer;
    $(window).on("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        const newCardWidth = $cards.first().outerWidth(true);
        const newVisibleCards = Math.floor($carousel.width() / newCardWidth);
        const newMaxIndex = Math.max(0, $cards.length - newVisibleCards);

        if (currentIndex > newMaxIndex) {
          currentIndex = newMaxIndex;
        }
        slideTo(currentIndex);
      }, 250);
    });
  });
})(jQuery);
