
document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("availabilityForm");

    const modal = document.getElementById("availabilityErrorModal");

    const errorMessage = document.getElementById("availabilityErrorMessage");

    const closeButton = document.getElementById("closeAvailabilityError");

    const submitButton = form.querySelector('button[type="submit"]');


    // Display validation modal
    function showError(message) {

        errorMessage.textContent = message;

        if (!modal.open) {
            modal.showModal();
        }

    }


    // Close validation modal
    closeButton.addEventListener("click", () => {
        modal.close();
    });


    // Handle Check Availability
    form.addEventListener("submit", async (event) => {

        event.preventDefault();

        const formData = new FormData(form);

        const checkin = formData.get("checkin");
        const checkout = formData.get("checkout");
        const facility = formData.get("facility");
        const guests = formData.get("guests");


        // STEP 1: Check empty fields
        if (!checkin || !checkout || !facility || !guests) {

            showError("Please complete all required fields.");
            return;

        }


        // STEP 2: Check guest count
        if (!Number.isInteger(Number(guests)) || Number(guests) < 1) {

            showError("Please enter a valid number of guests.");
            return;

        }


        // STEP 3: Send data to PHP for validation
        submitButton.disabled = true;

        try {

            const response = await fetch(
                new URL("../actions/check_availability.php", form.action),
                {
                    method: "POST",
                    body: formData
                }
            );

            const data = await response.json();


            // STEP 4: Display backend validation errors
            if (!response.ok || !data.success) {

                showError(
                    data.message || "Unable to check availability."
                );

                return;

            }


            // STEP 5: Check if any rooms matched
            if (data.available_count === 0) {

                showError(
                    "No rooms or facilities are available " +
                    "for your selected dates and guest count. " +
                    "Please try different dates or another room type."
                );

                return;

            }


            // STEP 6: Valid request - redirect to availability.php
            const searchParams = new URLSearchParams();

            searchParams.set("checkin", checkin);
            searchParams.set("checkout", checkout);
            searchParams.set("facility", facility);
            searchParams.set("guests", guests);

            const redirectURL = new URL(form.action);

            redirectURL.search = searchParams.toString();

            window.location.href = redirectURL.href;

        } catch (error) {

            console.error("Availability error:", error);

            showError(
                "Unable to process your request. Please try again."
            );

        } finally {

            submitButton.disabled = false;

        }

    });

});
