
document.addEventListener("DOMContentLoaded", () => {

    const gallery = document.querySelector(".bv-gallery");

    if (!gallery) return;

    const carousel = gallery.querySelector(".bv-carousel");

    const slides = Array.from(
        gallery.querySelectorAll(".bv-carousel__slide")
    );

    const prevBtn = gallery.querySelector(".prev");
    const nextBtn = gallery.querySelector(".next");

    const dotsContainer = gallery.querySelector(
        ".bv-carousel__dots"
    );

    const total = slides.length;
    if (!total) return;

    let current = 0;

    // CREATE DOTS
    dotsContainer.replaceChildren();

    slides.forEach((_, index) => {
        const dot = document.createElement("button");

        dot.type = "button";
        dot.className = "bv-carousel__dot";
        dot.setAttribute(
            "aria-label",
            `Go to photo ${index + 1}`
        );

        dot.addEventListener("click", () => {
            updateCarousel(index);
        });

        dotsContainer.appendChild(dot);
    });

    const dots = Array.from(dotsContainer.children);

    // UPDATE CAROUSEL
    function updateCarousel(index) {

        current = (index + total) % total;

        const previous = (current - 1 + total) % total;
        const next = (current + 1) % total;

        slides.forEach((slide, i) => {

            slide.classList.remove(
                "is-active",
                "is-prev",
                "is-next"
            );

            if (i === current) {
                slide.classList.add("is-active");
            } else if (i === previous) {
                slide.classList.add("is-prev");
            } else if (i === next) {
                slide.classList.add("is-next");
            }

            slide.setAttribute(
                "aria-hidden",
                String(i !== current)
            );
        });

        dots.forEach((dot, i) => {
            const active = i === current;

            dot.classList.toggle("active", active);
            dot.setAttribute(
                "aria-pressed",
                String(active)
            );
        });
    }

    // NAVIGATION
    prevBtn.addEventListener("click", () => {
        updateCarousel(current - 1);
    });

    nextBtn.addEventListener("click", () => {
        updateCarousel(current + 1);
    });

    // KEYBOARD NAVIGATION
    carousel.addEventListener("keydown", (event) => {
        if (event.key === "ArrowLeft") {
            event.preventDefault();
            updateCarousel(current - 1);
        }

        if (event.key === "ArrowRight") {
            event.preventDefault();
            updateCarousel(current + 1);
        }
    });

    updateCarousel(0);

});
