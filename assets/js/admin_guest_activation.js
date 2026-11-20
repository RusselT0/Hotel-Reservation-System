document.addEventListener('DOMContentLoaded', () => {
    const feedback = document.getElementById('activationFeedback');
    if (feedback && !feedback.open) {
        feedback.showModal();
        feedback.addEventListener('close', () => {
            const code = document.getElementById('issued-code');
            if (code) {
                code.value = '';
                code.removeAttribute('value');
            }
        });
    }
});
