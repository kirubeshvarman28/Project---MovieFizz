// Admin Panel Main JS
document.addEventListener('DOMContentLoaded', () => {
    console.log('MovieFizz Admin Loaded');
    
    // Submenu Toggling (for cases where it's not handled by inline script)
    // Submenu Toggling
    const toggles = document.querySelectorAll('.sidebar-nav .has-submenu .submenu-toggle');
    toggles.forEach(toggle => {
        toggle.onclick = function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const isOpen = parent.classList.contains('open');
            
            // Close other open submenus if they are not active for the current page
            document.querySelectorAll('.sidebar-nav .has-submenu.open').forEach(menu => {
                if (!menu.classList.contains('active')) {
                    menu.classList.remove('open');
                }
            });
            
            if (!isOpen) {
                parent.classList.add('open');
            }
        };
    });
});
