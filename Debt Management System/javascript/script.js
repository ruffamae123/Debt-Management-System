
function toggleSubMenu(event) {
    event.preventDefault();
    const parent = event.currentTarget.parentElement;
    parent.classList.toggle('open');
    }
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const arrow = document.querySelector(`#${id}`).previousElementSibling.querySelector('.submenu-arrow');
    
    submenu.classList.toggle('active');
    arrow.classList.toggle('rotated');
    
    // Prevent the link from navigating
    return false;

}

function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
    }
}

function togglePassword() {
    ['current_password', 'new_password', 'confirm_password'].forEach(id => {
        const field = document.getElementById(id);
        field.type = (field.type === 'password') ? 'text' : 'password';
    });
}

// Auto-hide alert after 3 seconds for adding new debt
window.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.querySelector('.alert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                alertBox.style.opacity = '0';
                alertBox.style.transform = 'translateY(-10px)';
                setTimeout(() => alertBox.remove(), 600); // Remove after animation
            }, 3000);
        }
});
// Highlight active nav link based on current URL
const links = document.querySelectorAll('.nav-link');
links.forEach(link => {
    if (link.href === window.location.href) {
        link.classList.add('active');
    }
});
async function confirmDeletion(button) {
    const form = button.closest('form');
    
    try {
        const confirmed = await showConfirmModal(
            'Delete Record', 
            'Are you sure you want to delete this record? This action cannot be undone.',
            'Delete',
            'Cancel'
        );
        
        if (confirmed) {
            // Show loading state
            button.classList.add('loading');
            
            // Submit the form
            form.submit();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function showConfirmModal(title, message, confirmText, cancelText) {
    return new Promise((resolve) => {
        // Create modal elements
        const modal = document.createElement('div');
        modal.className = 'confirm-modal';
        
        modal.innerHTML = `
            <div class="confirm-modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirm-modal-buttons">
                    <button class="btn btn-cancel">${cancelText}</button>
                    <button class="btn btn-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Handle confirm button
        modal.querySelector('.btn-confirm').addEventListener('click', () => {
            document.body.removeChild(modal);
            resolve(true);
        });
        
        // Handle cancel button
        modal.querySelector('.btn-cancel').addEventListener('click', () => {
            document.body.removeChild(modal);
            resolve(false);
        });
        
        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                resolve(false);
            }
        });
    });
}