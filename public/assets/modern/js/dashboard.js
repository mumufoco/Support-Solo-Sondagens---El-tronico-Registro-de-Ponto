/**
 * Dashboard - Main JavaScript
 * Handles dropdowns, alerts, and general UI interactions
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initDropdowns();
        initAlerts();
        initGlobalSearch();
    });

    /**
     * Initialize Dropdown Menus
     */
    function initDropdowns() {
        // Get all dropdown toggles
        const dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');

        dropdownToggles.forEach(toggle => {
            const dropdown = toggle.nextElementSibling;
            if (!dropdown || !dropdown.classList.contains('dropdown-menu')) return;

            // Toggle dropdown on click
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Close other dropdowns
                closeAllDropdowns();

                // Toggle this dropdown
                dropdown.classList.toggle('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                closeAllDropdowns();
            }
        });

        // Prevent dropdown from closing when clicking inside
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    }

    /**
     * Close all dropdown menus
     */
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }

    /**
     * Initialize Alert Dismissal
     */
    function initAlerts() {
        const alertCloseButtons = document.querySelectorAll('.alert-close');

        alertCloseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    // Fade out animation
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';

                    // Remove from DOM after animation
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }
            });
        });

        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.alert-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    }

    /**
     * Initialize Global Search
     */
    function initGlobalSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Debounce search
            searchTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    performSearch(query);
                }
            }, 300);
        });

        // Submit on Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            }
        });
    }

    /**
     * Perform global search
     */
    function performSearch(query) {
        console.log('Searching for:', query);
        // TODO: Implement actual search functionality
        // This could be an AJAX call to search endpoint
        // For now, just logging to console
    }

    /**
     * Show notification (programmatically)
     */
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible`;
        alert.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button type="button" class="alert-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Insert at top of page content
        const pageContent = document.querySelector('.page-content');
        if (pageContent) {
            pageContent.insertBefore(alert, pageContent.firstChild);

            // Initialize close button
            const closeButton = alert.querySelector('.alert-close');
            closeButton.addEventListener('click', function() {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });

            // Auto-dismiss
            if (duration > 0) {
                setTimeout(() => {
                    closeButton.click();
                }, duration);
            }
        }
    };

    /**
     * Show loading spinner
     */
    window.showLoading = function(container) {
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinner.id = 'global-spinner';

        if (container) {
            container.innerHTML = '';
            container.appendChild(spinner);
        } else {
            // Add to page content center
            const pageContent = document.querySelector('.page-content');
            if (pageContent) {
                spinner.style.margin = '50px auto';
                spinner.style.display = 'block';
                pageContent.appendChild(spinner);
            }
        }
    };

    /**
     * Hide loading spinner
     */
    window.hideLoading = function() {
        const spinner = document.getElementById('global-spinner');
        if (spinner) {
            spinner.remove();
        }
    };

    /**
     * Confirm dialog (using browser default for now)
     */
    window.confirmAction = function(message) {
        return confirm(message || 'Tem certeza que deseja realizar esta ação?');
    };

})();
