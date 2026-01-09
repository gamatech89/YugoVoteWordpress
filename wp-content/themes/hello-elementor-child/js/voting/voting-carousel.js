document.addEventListener('DOMContentLoaded', function () {
    const heroSwiper = new Swiper('.cs-hero-archive__carousel', {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 20,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.cs-carousel-pagination',
            clickable: true,
        },

        navigation: {
            nextEl: '.cs-carousel-next',
            prevEl: '.cs-carousel-prev',
        },
    });
});

document.addEventListener("DOMContentLoaded", function () {
  new Swiper(".cs-subcategory-carousel", {
    slidesPerView: 2,
    spaceBetween: 20,
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev"
    },
    breakpoints: {
      768: { slidesPerView: 3 },
      1024: { slidesPerView: 5 }
    }
  });
});