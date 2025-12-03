/**
 * Theme Switcher - Light/Dark Mode
 * Handles theme switching and persistence
 */

(function() {
    'use strict';

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initThemeSwitcher();
        loadTheme();
    });

    /**
     * Initialize Theme Switcher
     */
    function initThemeSwitcher() {
        const themeToggle = document.getElementById('themeToggle');
        if (!themeToggle) return;

        themeToggle.addEventListener('click', function() {
            toggleTheme();
        });

        // Keyboard shortcut: Ctrl+Shift+L
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                toggleTheme();
            }
        });
    }

    /**
     * Toggle Theme
     */
    function toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';

        setTheme(newTheme);
    }

    /**
     * Set Theme
     */
    function setTheme(theme) {
        const html = document.documentElement;

        // Set theme attribute
        html.setAttribute('data-theme', theme);

        // Save to localStorage
        localStorage.setItem('theme', theme);

        // Save to backend (optional - for cross-device sync)
        saveThemeToBackend(theme);

        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    /**
     * Load Theme from localStorage or system preference
     */
    function loadTheme() {
        // Try localStorage first
        let theme = localStorage.getItem('theme');

        // If no saved theme, check system preference
        if (!theme) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                theme = 'dark';
            } else {
                theme = 'light';
            }
        }

        setTheme(theme);
    }

    /**
     * Save theme to backend (optional)
     */
    function saveThemeToBackend(theme) {
        // Only save if user is logged in
        const userId = document.body.getAttribute('data-user-id');
        if (!userId) return;

        // Send AJAX request to save preference
        fetch('/api/user/preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                theme: theme
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Theme preference saved:', data);
        })
        .catch(error => {
            console.error('Error saving theme preference:', error);
        });
    }

    /**
     * Listen for system theme changes
     */
    if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');

        darkModeQuery.addEventListener('change', function(e) {
            // Only auto-switch if user hasn't manually set a preference
            if (!localStorage.getItem('theme')) {
                const newTheme = e.matches ? 'dark' : 'light';
                setTheme(newTheme);
            }
        });
    }

    /**
     * Get current theme
     */
    window.getCurrentTheme = function() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    };

    /**
     * Export setTheme for external use
     */
    window.setTheme = setTheme;

})();
