
document.addEventListener("DOMContentLoaded", () => {

    // ========================================
    // MODAL ELEMENTS
    // ========================================

    const modal = document.getElementById(
        "bcFeedbackModal"
    );

    const modalTitle = document.getElementById(
        "bcModalTitle"
    );

    const modalMessage = document.getElementById(
        "bcModalMessage"
    );

    if (!modal || !modalTitle || !modalMessage) {
        return;
    }

    let invalidField = null;

    // ========================================
    // REUSABLE MODAL FUNCTION
    // ========================================

    function showModal(title, message, type = "error") {

        modalTitle.textContent = title;
        modalMessage.textContent = message;

        const icon = modal.querySelector(".bc-modal-icon");

        if (icon) {
            icon.classList.toggle(
                "bc-modal-success",
                type === "success"
            );

            icon.classList.toggle(
                "bc-modal-error",
                type !== "success"
            );

            icon.textContent =
                type === "success" ? "✓" : "!";
        }

        if (!modal.open) {
            modal.showModal();
        }
    }

    // ========================================
    // SHOW SERVER-SIDE FEEDBACK
    // ========================================

    if (modal.dataset.autoOpen === "true") {
        modal.showModal();
    }

    // ========================================
    // PAYMENT FORM VALIDATION
    // ========================================

    const paymentForm = document.getElementById(
        "bcPaymentForm"
    );

    if (paymentForm) {

        paymentForm.noValidate = true;

        paymentForm.addEventListener("submit", (event) => {

            if (paymentForm.checkValidity()) {
                return;
            }

            event.preventDefault();

            const firstInvalid = paymentForm.querySelector(
                ":invalid"
            );

            if (!firstInvalid) {
                return;
            }

            invalidField = firstInvalid;

            let message = firstInvalid.validationMessage;

            if (firstInvalid.name === "payment_method_id") {

                message =
                    "Please select a payment method.";

            } else if (firstInvalid.name === "reference_no") {

                if (firstInvalid.validity.valueMissing) {

                    message =
                        "Please enter a transaction reference number.";

                } else {

                    message =
                        "Please enter a valid transaction reference " +
                        "containing 6 to 120 letters, numbers, " +
                        "spaces, or hyphens.";

                }
            }

            showModal(
                "Validation Error",
                message
            );

        });

    }

    // ========================================
    // RETURN FOCUS TO INVALID FIELD
    // ========================================

    modal.addEventListener("close", () => {

        if (invalidField) {

            invalidField.focus();
            invalidField = null;

        }

    });

});
