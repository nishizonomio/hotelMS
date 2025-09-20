// Add this to your existing JavaScript
function initializeModals() {
    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });

    // Close modals when clicking outside
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // Set up close buttons
    document.querySelectorAll('.modal .close').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) modal.style.display = 'none';
        });
    });

    // Handle form submissions with loading state
    document.querySelectorAll('.modal form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeModals);

// Function to show a modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    }
}

// Function to hide a modal
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        // Reset any forms inside the modal
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// Function to show notifications
function showNotification(message, type = 'info') {
    const container = document.querySelector('.notification-container') || (() => {
        const cont = document.createElement('div');
        cont.className = 'notification-container';
        document.body.appendChild(cont);
        return cont;
    })();

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    container.appendChild(notification);

    // Remove after animation
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}