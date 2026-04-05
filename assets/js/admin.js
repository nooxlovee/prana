// Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu functionality
    const mobileMenuBtn = document.querySelector('.mobile_menu_btn');
    const burgerMenu = document.querySelector('.burger-menu');
    const burgerMenuOverlay = document.querySelector('.burger-menu-overlay');

    if (mobileMenuBtn && burgerMenu && burgerMenuOverlay) {
        mobileMenuBtn.addEventListener('click', function() {
            burgerMenu.classList.toggle('active');
            burgerMenuOverlay.classList.toggle('active');
        });

        burgerMenuOverlay.addEventListener('click', function() {
            burgerMenu.classList.remove('active');
            burgerMenuOverlay.classList.remove('active');
        });
    }

    // Search functionality
    const searchBtn = document.querySelector('.search_btn');
    const searchInput = document.querySelector('.search_box input');

    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                // Здесь будет логика поиска
                console.log('Searching for:', searchTerm);
            }
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    // Здесь будет логика поиска
                    console.log('Searching for:', searchTerm);
                }
            }
        });
    }

    // Add product button functionality
    const addProductBtn = document.querySelector('.add_product_btn');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            // Здесь будет логика добавления товара
            console.log('Add product clicked');
        });
    }

    // Action buttons functionality
    const actionButtons = document.querySelectorAll('.action_btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.classList.contains('view') ? 'view' :
                          this.classList.contains('edit') ? 'edit' :
                          this.classList.contains('delete') ? 'delete' : null;
            
            if (action) {
                // Здесь будет логика для каждого действия
                console.log(`${action} button clicked`);
            }
        });
    });

    // Table row hover effect
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#F5F5F5';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
}); 