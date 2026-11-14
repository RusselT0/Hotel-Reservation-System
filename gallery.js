
document.addEventListener("DOMContentLoaded", () => {

    const carousel = document.querySelector(
        ".gallery-carousel"
    );

    if (!carousel) return;

    const track = carousel.querySelector(
        ".gallery-track"
    );

    const slides = carousel.querySelectorAll(
        ".gallery-slide"
    );

    const prevBtn = carousel.querySelector(".prev");
    const nextBtn = carousel.querySelector(".next");

    const dotsContainer = document.querySelector(
        ".gallery-dots"
    );

    let currentSlide = 0;
    const totalSlides = slides.length;

    if (totalSlides === 0 || !dotsContainer) return;

    // Create indicator dots
    slides.forEach((slide, index) => {

        const dot = document.createElement("button");

        dot.type = "button";
        dot.classList.add("gallery-dot");
        dot.setAttribute(
            "aria-label",
            `Show photo ${index + 1}`
        );

        dot.addEventListener("click", () => {
            showSlide(index);
        });

        dotsContainer.appendChild(dot);
    });

    const dots = dotsContainer.querySelectorAll(
        ".gallery-dot"
    );

    // Display selected slide
    function showSlide(index) {

        currentSlide =
            (index + totalSlides) % totalSlides;

        track.style.transform =
            `translateX(-${currentSlide * 100}%)`;

        dots.forEach((dot, dotIndex) => {

            const isActive = dotIndex === currentSlide;

            dot.classList.toggle("active", isActive);

            dot.setAttribute(
                "aria-pressed",
                String(isActive)
            );
        });
    }

    // Next image
    nextBtn.addEventListener("click", () => {
        showSlide(currentSlide + 1);
    });

    // Previous image
    prevBtn.addEventListener("click", () => {
        showSlide(currentSlide - 1);
    });

    // Keyboard navigation
    carousel.setAttribute("tabindex", "0");

    carousel.addEventListener("keydown", (event) => {

        if (event.key === "ArrowRight") {
            event.preventDefault();
            showSlide(currentSlide + 1);
        }

        if (event.key === "ArrowLeft") {
            event.preventDefault();
            showSlide(currentSlide - 1);
        }
    });

    // Show first photo initially
    showSlide(0);

});
