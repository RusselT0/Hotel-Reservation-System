document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('accountFeedbackModal');
    if (dialog && !dialog.open) {
        dialog.showModal();
    }
});
