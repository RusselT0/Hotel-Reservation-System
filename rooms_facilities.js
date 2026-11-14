
document.addEventListener("DOMContentLoaded", () => {

    const track = document.querySelector("#roomTrack");

    const prevBtn = document.querySelector(".rooms-prev");
    const nextBtn = document.querySelector(".rooms-next");

    if (!track || !prevBtn || !nextBtn) return;

    // CAROUSEL NAVIGATION

    function scrollRooms(direction) {

        const card = track.querySelector(".room-card");

        if (!card) return;

        const gap = parseFloat(
            getComputedStyle(track).columnGap
        ) || 0;

        const distance = card.getBoundingClientRect().width + gap;

        const reducedMotion = window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        ).matches;

        track.scrollBy({
            left: distance * direction,
            behavior: reducedMotion ? "instant" : "smooth"
        });
    }

    prevBtn.addEventListener("click", () => {
        scrollRooms(-1);
    });

    nextBtn.addEventListener("click", () => {
        scrollRooms(1);
    });

    // ENABLE OR DISABLE ARROWS

    function updateArrows() {

        const maxScroll =
            track.scrollWidth - track.clientWidth;

        prevBtn.disabled = track.scrollLeft <= 1;
        nextBtn.disabled =
            track.scrollLeft >= maxScroll - 1;
    }

    track.addEventListener("scroll", updateArrows);
    window.addEventListener("resize", updateArrows);

    updateArrows();

    // ROOM DETAILS

    const roomData = {
        standard: {
            name: "Standard Room",
            capacity: "2 guests included, maximum 3 guests",
            price: "₱3,500 per night",
            description:
                "A comfortable room for guests looking for a relaxing stay.",
            fee: "An additional ₱1,000 applies for the third guest."
        },

        family: {
            name: "Family Room",
            capacity: "Maximum of 5 guests",
            price: "₱6,000 per night",
            description:
                "A spacious accommodation suitable for families and groups.",
            fee: "The rate covers up to 5 guests."
        },

        event: {
            name: "Event Hall",
            capacity: "30 guests included, maximum 40 guests",
            price: "₱15,000 per night",
            description:
                "An event space for celebrations and gatherings.",
            fee: "An additional ₱500 per guest applies above 30 guests."
        }
    };

    const modal = document.querySelector(
        "#roomDetailsModal"
    );

    if (!modal) return;

    const closeBtn = modal.querySelector(
        ".room-modal-close"
    );

    // OPEN ROOM DETAILS

    document.querySelectorAll(".room-card-open")
        .forEach(button => {

            button.addEventListener("click", () => {

                const type = button.dataset.room;
                const room = roomData[type];

                if (!room) return;

                document.querySelector(
                    "#roomModalTitle"
                ).textContent = room.name;

                document.querySelector(
                    "#roomModalCapacity"
                ).textContent = room.capacity;

                document.querySelector(
                    "#roomModalPrice"
                ).textContent = room.price;

                document.querySelector(
                    "#roomModalDescription"
                ).textContent = room.description;

                document.querySelector(
                    "#roomModalFee"
                ).textContent = room.fee;

                modal.showModal();
            });
        });

    // CLOSE ROOM DETAILS

    closeBtn.addEventListener("click", () => {
        modal.close();
    });

});
