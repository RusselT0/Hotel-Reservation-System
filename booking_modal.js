
document.addEventListener("DOMContentLoaded", () => {

    // ========================================
    // MODAL ELEMENTS
    // ========================================

    const modal = document.getElementById(
        "bookingFeedbackModal"
    );

    const modalTitle = document.getElementById(
        "bkModalTitle"
    );

    const modalMessage = document.getElementById(
        "bkModalMessage"
    );

    if (!modal || !modalTitle || !modalMessage) {
        return;
    }

    let invalidField = null;

    // ========================================
    // REUSABLE FUNCTION TO SHOW MESSAGES
    // ========================================

    function showBookingModal(title, message) {

        modalTitle.textContent = title;
        modalMessage.textContent = message;

        if (!modal.open) {
            modal.showModal();
        }
    }

    // ========================================
    // SHOW SERVER-SIDE PHP ERRORS
    // ========================================

    if (modal.dataset.autoOpen === "true") {
        modal.showModal();
    }

    // ========================================
    // VALIDATE GUEST INFORMATION FORM
    // ========================================

    const bookingForm = document.querySelector(
        ".bk-form"
    );

    if (bookingForm) {

        // Let our modal handle browser validation
        bookingForm.noValidate = true;

        bookingForm.addEventListener("submit", (event) => {

            if (bookingForm.checkValidity()) {
                return;
            }

            // Stop submission if invalid
            event.preventDefault();

            const firstInvalid = bookingForm.querySelector(
                ":invalid"
            );

            if (!firstInvalid) {
                return;
            }

            invalidField = firstInvalid;

            let errorMessage = firstInvalid.validationMessage;

            // Required checkbox
            if (firstInvalid.name === "accept_terms") {

                errorMessage =
                    "Please accept the booking terms and conditions.";

            }

            // Required text fields
            else if (firstInvalid.validity.valueMissing) {

                const label = firstInvalid.labels?.[0];

                const fieldName = label
                    ? label.textContent.trim()
                    : "required field";

                errorMessage =
                    "Please complete the " + fieldName + ".";

            }

            // Invalid email format
            else if (firstInvalid.validity.typeMismatch) {

                errorMessage =
                    "Please enter a valid email address.";

            }

            // Invalid add-on quantity
            else if (
                firstInvalid.validity.rangeOverflow ||
                firstInvalid.validity.rangeUnderflow
            ) {

                errorMessage =
                    "Please enter an add-on quantity between 0 and 10.";

            }

            showBookingModal(
                "Validation Error",
                errorMessage
            );

        });

    }

    // ========================================
    // RETURN FOCUS TO INVALID INPUT
    // ========================================

    modal.addEventListener("close", () => {

        if (invalidField) {

            invalidField.focus();
            invalidField = null;

        }

    });

});
