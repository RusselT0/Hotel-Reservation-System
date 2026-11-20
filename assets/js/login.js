
document.addEventListener("DOMContentLoaded", () => {

    const form =
        document.getElementById("loginForm") ||
        document.getElementById("registerForm") ||
        document.getElementById("activateForm");

    const password = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    const modal = document.getElementById("loginFeedbackModal");
    const modalTitle = document.getElementById("authModalTitle");
    const modalMessage = document.getElementById("authModalMessage");

    const modalIcon = modal.querySelector(".auth-modal-icon");

    let invalidField = null;

    // ========================================
    // FEEDBACK MODAL
    // ========================================

    function showFeedback(title, message) {

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modalIcon.textContent = "!";
        modalIcon.classList.remove("is-success");

        if (!modal.open) {
            modal.showModal();
        }
    }

    // Display server-side messages
    if (modal.dataset.autoOpen === "true") {
        modal.showModal();
    }

    // ========================================
    // PASSWORD VISIBILITY
    // ========================================

    togglePassword?.addEventListener("click", () => {

        const isHidden = password.type === "password";

        password.type = isHidden ? "text" : "password";

        togglePassword.textContent =
            isHidden ? "Hide" : "Show";

        togglePassword.setAttribute(
            "aria-pressed",
            String(isHidden)
        );

        togglePassword.setAttribute(
            "aria-label",
            isHidden ? "Hide password" : "Show password"
        );

    });

    // ========================================
    // FORM VALIDATION
    // ========================================

    form?.addEventListener("submit", (event) => {

        if (!form.checkValidity()) {

            event.preventDefault();

            invalidField = form.querySelector(":invalid");

            showFeedback(
                "Validation Error",
                invalidField.validationMessage
            );

            return;
        }

        const confirmPassword =
            document.getElementById("confirm_password");

        if (
            confirmPassword &&
            password.value !== confirmPassword.value
        ) {

            event.preventDefault();
            invalidField = confirmPassword;

            showFeedback(
                "Validation Error",
                "Passwords do not match."
            );

            return;
        }

        // Valid forms continue to PHP normally.

    });

    // Return focus to the invalid field
    modal.addEventListener("close", () => {

        if (invalidField) {

            invalidField.focus();
            invalidField = null;

        }

    });

});
