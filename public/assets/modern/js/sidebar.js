/**
 * Sidebar - Navigation JavaScript
 * Handles sidebar toggle, submenu expansion, and search
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initSidebarToggle();
        initSubmenuToggle();
        initSidebarSearch();
        initMobileSidebar();
        loadSidebarState();
    });

    /**
     * Initialize Sidebar Toggle (Collapse/Expand)
     */
    function initSidebarToggle() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');

        if (!sidebar || !sidebarToggle) return;

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            saveSidebarState();
        });
    }

    /**
     * Initialize Submenu Toggle
     */
    function initSubmenuToggle() {
        const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]');

        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();

                const navItem = this.closest('.nav-item');
                if (!navItem) return;

                // Toggle open class
                navItem.classList.toggle('open');

                // Close other submenus at same level
                const siblings = Array.from(navItem.parentElement.children);
                siblings.forEach(sibling => {
                    if (sibling !== navItem && sibling.classList.contains('has-submenu')) {
                        sibling.classList.remove('open');
                    }
                });

                // Save state
                saveSubmenuState();
            });
        });
    }

    /**
     * Initialize Sidebar Search
     */
    function initSidebarSearch() {
        const searchInput = document.getElementById('sidebarSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const navItems = document.querySelectorAll('.nav-item');

            if (query === '') {
                // Reset - show all items
                navItems.forEach(item => {
                    item.classList.remove('search-match', 'search-hidden');
                });
                return;
            }

            // Search through menu items
            navItems.forEach(item => {
                const navLink = item.querySelector('.nav-link');
                const navText = navLink ? navLink.querySelector('.nav-text') : null;
                const text = navText ? navText.textContent.toLowerCase() : '';

                // Check submenu items too
                const submenuItems = item.querySelectorAll('.submenu-link');
                let submenuMatch = false;

                submenuItems.forEach(subitem => {
                    const subtext = subitem.textContent.toLowerCase();
                    if (subtext.includes(query)) {
                        submenuMatch = true;
                    }
                });

                // Show/hide based on match
                if (text.includes(query) || submenuMatch) {
                    item.classList.add('search-match');
                    item.classList.remove('search-hidden');

                    // Auto-expand if has submenu and submenu matches
                    if (submenuMatch && item.classList.contains('has-submenu')) {
                        item.classList.add('open');
                    }
                } else {
                    item.classList.remove('search-match');
                    item.classList.add('search-hidden');
                }
            });
        });

        // Clear search on Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
                this.blur();
            }
        });
    }

    /**
     * Initialize Mobile Sidebar
     */
    function initMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (!sidebar || !mobileMenuToggle || !backdrop) return;

        // Open sidebar on mobile
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        // Close sidebar on backdrop click
        backdrop.addEventListener('click', function() {
            closeMobileSidebar();
        });

        // Close sidebar on navigation (mobile)
        if (window.innerWidth <= 768) {
            const navLinks = document.querySelectorAll('.nav-link:not([data-toggle="submenu"])');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            });
        }
    }

    /**
     * Close Mobile Sidebar
     */
    function closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    /**
     * Save Sidebar State to LocalStorage
     */
    function saveSidebarState() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    /**
     * Save Submenu State to LocalStorage
     */
    function saveSubmenuState() {
        const openSubmenus = [];
        document.querySelectorAll('.nav-item.has-submenu.open').forEach(item => {
            const link = item.querySelector('.nav-link');
            const text = link ? link.querySelector('.nav-text')?.textContent : null;
            if (text) {
                openSubmenus.push(text);
            }
        });

        localStorage.setItem('openSubmenus', JSON.stringify(openSubmenus));
    }

    /**
     * Load Sidebar State from LocalStorage
     */
    function loadSidebarState() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        // Load collapsed state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }

        // Load submenu states
        try {
            const openSubmenus = JSON.parse(localStorage.getItem('openSubmenus') || '[]');
            openSubmenus.forEach(text => {
                const navItems = document.querySelectorAll('.nav-item.has-submenu');
                navItems.forEach(item => {
                    const link = item.querySelector('.nav-link');
                    const navText = link ? link.querySelector('.nav-text')?.textContent : null;
                    if (navText === text) {
                        item.classList.add('open');
                    }
                });
            });
        } catch (e) {
            console.error('Error loading submenu state:', e);
        }
    }

    /**
     * Update tooltips for collapsed sidebar
     */
    function updateTooltips() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar || !sidebar.classList.contains('collapsed')) return;

        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            const text = link.querySelector('.nav-text')?.textContent;
            if (text) {
                link.setAttribute('data-tooltip', text);
            }
        });
    }

    // Update tooltips on load and collapse
    document.addEventListener('DOMContentLoaded', updateTooltips);
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        const observer = new MutationObserver(updateTooltips);
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }

})();
