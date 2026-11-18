
document.addEventListener("DOMContentLoaded", () => {

    const track = document.getElementById("roomTrack");

    const prev = document.querySelector(".rooms-prev");
    const next = document.querySelector(".rooms-next");

    const modal = document.getElementById("roomDetailsModal");

    if (!track || !modal) return;

    const closeButton =
        modal.querySelector(".room-modal-close");

    const bookButton =
        modal.querySelector(".room-book-btn");

    const note =
        modal.querySelector(".room-modal-note");

    const title = document.getElementById("roomModalTitle");
    const details = document.getElementById("roomModalDetails");

    const capacity = document.getElementById("roomModalCapacity");
    const price = document.getElementById("roomModalPrice");
    const fee = document.getElementById("roomModalFee");

    const beds = document.getElementById("roomModalBeds");
    const features = document.getElementById("roomModalFeatures");

    const bedSection =
        document.getElementById("roomModalBedSection");

    const featureSection =
        document.getElementById("roomModalFeatureSection");

    let selectedRoom = null;

    const currency = new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    // ========================================
    // CAROUSEL NAVIGATION
    // ========================================

    function getScrollDistance() {

        const card = track.querySelector(".room-card");

        if (!card) return 0;

        const gap =
            parseFloat(getComputedStyle(track).columnGap) || 0;

        return card.getBoundingClientRect().width + gap;
    }

    prev?.addEventListener("click", () => {

        track.scrollBy({
            left: -getScrollDistance(),
            behavior: "smooth"
        });

    });

    next?.addEventListener("click", () => {

        track.scrollBy({
            left: getScrollDistance(),
            behavior: "smooth"
        });

    });

    // ========================================
    // DISPLAY ROOM INFORMATION
    // ========================================

    document.querySelectorAll(".room-card-open")
        .forEach(button => {

            button.addEventListener("click", () => {

                selectedRoom =
                    JSON.parse(button.dataset.roomDetails);

                details.hidden = false;
                bookButton.hidden = false;

                title.textContent = selectedRoom.room_Type;

                capacity.textContent =
                    "Included guests: " +
                    selectedRoom.included_Guests +
                    " | Maximum: " +
                    selectedRoom.max_Guests;

                price.textContent =
                    currency.format(selectedRoom.base_Price) +
                    " / night";

                const extraFee =
                    Number(selectedRoom.extra_Person_Fee);

                fee.textContent = extraFee > 0
                    ? currency.format(extraFee) +
                      " per additional guest, per reservation."
                    : "No additional guest fee within maximum capacity.";

                // BED CONFIGURATION

                beds.replaceChildren();

                selectedRoom.beds.forEach(bed => {

                    const item = document.createElement("li");

                    item.textContent =
                        bed.quantity + " × " + bed.type;

                    beds.appendChild(item);

                });

                bedSection.hidden =
                    selectedRoom.beds.length === 0;

                // FEATURES AND AMENITIES

                features.replaceChildren();

                selectedRoom.features.forEach(feature => {

                    const item = document.createElement("li");

                    item.textContent = feature;

                    features.appendChild(item);

                });

                featureSection.hidden =
                    selectedRoom.features.length === 0;

                note.textContent =
                    "Select your preferred dates to check room availability.";

                modal.showModal();

            });

        });

    // ========================================
    // CLOSE MODAL
    // ========================================

    closeButton.addEventListener("click", () => {

        modal.close();

    });

    modal.addEventListener("click", event => {

        if (event.target === modal) {

            const rect = modal.getBoundingClientRect();

            if (
                event.clientX < rect.left ||
                event.clientX > rect.right ||
                event.clientY < rect.top ||
                event.clientY > rect.bottom
            ) {
                modal.close();
            }

        }

    });

    // ========================================
    // CHECK AVAILABILITY
    // ========================================

    bookButton.addEventListener("click", () => {

        if (!selectedRoom) return;

        const facility = document.getElementById("facility");

        const form =
            document.querySelector(".availability-form");

        if (facility) {

            facility.value =
                String(selectedRoom.room_Type_ID);

            facility.dispatchEvent(
                new Event("change", { bubbles: true })
            );

        }

        modal.close();

        form?.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

        document.getElementById("checkin")
            ?.focus({ preventScroll: true });

    });

    // ========================================
    // DATABASE ERROR FEEDBACK
    // ========================================

    if (modal.dataset.catalogError) {

        title.textContent = "Room Information Unavailable";

        details.hidden = true;
        bookButton.hidden = true;

        note.textContent = modal.dataset.catalogError;

        modal.showModal();

    }

});
