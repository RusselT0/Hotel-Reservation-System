document.addEventListener("DOMContentLoaded", async () => {

    const resultsContainer = document.getElementById(
        "availabilityResults"
    );

    const availabilityCount = document.getElementById(
        "availabilityCount"
    );

    // Get search values from the URL
    const params = new URLSearchParams(window.location.search);

    const formData = new URLSearchParams();

    const fields = ["checkin", "checkout", "facility", "guests"];

    fields.forEach(field => {
        formData.set(field, params.get(field) ?? "");
    });

    // Existing images from your project
    const roomImages = {
        "Standard Room": "../assets/images/standard room.jfif",
        "Family Room": "../assets/images/family room.webp",
        "Event Hall": "../assets/images/event hall.jfif"
    };

    // Format prices into Philippine pesos
    function formatPrice(amount) {
        return new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP",
            minimumFractionDigits: 2
        }).format(Number(amount));
    }

    function showResultsError(message) {
        const modal = document.getElementById("availabilityErrorModal");
        document.getElementById("availabilityErrorMessage").textContent = message;

        if (!modal.open) {
            modal.showModal();
        }
    }

    // Create an individual room card
    function createRoomCard(room, data) {

        const card = document.createElement("article");
        card.className = "avp-room-card";

        // ROOM IMAGE
        const image = document.createElement("img");
        image.className = "avp-room-image";
        image.src = roomImages[data.room_type] || "../assets/images/hero.webp";
        image.alt = `${data.room_type} - ${room.room_Code}`;
        image.loading = "lazy";

        // ROOM DETAILS
        const details = document.createElement("div");
        details.className = "avp-room-details";

        const title = document.createElement("h2");
        title.textContent = room.room_Code;

        const roomType = document.createElement("p");
        roomType.textContent = data.room_type;

        if (room.floor_Number != null) {
            roomType.textContent += ` • Floor ${room.floor_Number}`;
        }

        const status = document.createElement("p");
        status.className = "avp-available-text";
        status.textContent = "✓ Available for selected dates";

        const capacity = document.createElement("p");
        capacity.textContent =
            `${data.included_guests} included guests • ` +
            `Maximum ${data.max_guests} guests`;

        details.append(title, roomType, status, capacity);

        // Bed configuration and amenities come from the selected room type.
        if (data.beds?.length) {
            const beds = document.createElement("p");
            beds.textContent = "Beds: " + data.beds.map(bed =>
                `${bed.quantity} × ${bed.type}`
            ).join(", ");
            details.append(beds);
        }

        if (data.features?.length) {
            const featuresLabel = document.createElement("h3");
            featuresLabel.className = "avp-features-label";
            featuresLabel.textContent = "Amenities";

            const features = document.createElement("ul");
            features.className = "avp-room-features";

            data.features.forEach(feature => {
                const item = document.createElement("li");
                item.textContent = feature;
                features.append(item);
            });

            details.append(featuresLabel, features);
        }

        if (data.max_guests > data.included_guests) {
            const fee = document.createElement("p");
            fee.className = "avp-extra-fee";
            fee.textContent = Number(data.extra_person_fee) > 0
                ? `${formatPrice(data.extra_person_fee)} per additional guest (one-time per reservation).`
                : "No extra fee for additional guests within capacity.";
            details.append(fee);
        }

        // PRICE SECTION
        const priceSection = document.createElement("div");
        priceSection.className = "avp-room-price";

        const priceLabel = document.createElement("span");
        priceLabel.className = "avp-price-label";
        priceLabel.textContent = "Base rate per night";

        const price = document.createElement("strong");
        price.className = "avp-price";
        price.textContent = formatPrice(data.nightly_rate);

        // BOOK NOW BUTTON
        
        const bookButton = document.createElement("button");

        bookButton.type = "button";
        bookButton.className = "avp-book-btn";
        bookButton.textContent = "Book Now";

        // Redirect to the booking page
        bookButton.addEventListener("click", () => {

            const params = new URLSearchParams({
                room_id: room.room_ID,
                checkin: data.checkin,
                checkout: data.checkout,
                guests: data.guests
            });

            window.location.href =
                "booking.php?" + params.toString();

        });

        priceSection.append(priceLabel, price, bookButton);

        // COMPLETE CARD
        card.append(image, details, priceSection);

        return card;
    }

    try {

        // Send search values to the existing PHP backend
        const response = await fetch("../actions/check_availability.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        resultsContainer.replaceChildren();

        // Display backend errors
        if (!response.ok || !data.success) {
            availabilityCount.textContent =
                "Unable to load results";

            showResultsError(data.message || "Please try another search.");

            return;
        }

        // Display result count
        const count = Number(data.available_count);

        availabilityCount.textContent =
            `${count} ${count === 1 ? "room" : "rooms"} ` +
            `available for your selected dates`;

        // Handle no available rooms
        if (count === 0) {

            const message = document.createElement("p");
            message.textContent =
                "No rooms are available for the selected dates. " +
                "Please try another room type or different dates.";

            resultsContainer.appendChild(message);

            return;
        }

        // Create room cards dynamically
        data.rooms.forEach(room => {

            const card = createRoomCard(room, data);

            resultsContainer.appendChild(card);

        });

    } catch (error) {

        console.error("Availability results error:", error);

        availabilityCount.textContent =
            "Unable to load availability";

        resultsContainer.replaceChildren();
        showResultsError("Something went wrong while loading the rooms.");

    }

});
