document.addEventListener('DOMContentLoaded', () => {
    const feedback = document.getElementById('adminOverviewFeedback');
    if (feedback && !feedback.open) {
        feedback.showModal();
    }
});
