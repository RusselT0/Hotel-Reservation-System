document.addEventListener('DOMContentLoaded', () => {
    const feedback = document.getElementById('reservationFeedback');
    const details = document.getElementById('reservationDetails');
    const dialog = feedback || details;
    if (dialog && !dialog.open) dialog.showModal();
    if (feedback && details) {
        feedback.addEventListener('close', () => {
            if (!details.open) details.showModal();
        });
    }

    if (details) {
        details.addEventListener('close', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('detail');
            window.history.replaceState(null, '', url);
        });
    }
});
