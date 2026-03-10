/**
 * Centralized Sidebar Toggle Script
 * Handles sidebar collapse/expand functionality across all dashboard pages
 */
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const mainContent = document.getElementById('mainContent') || document.querySelector('.main-content');

    if (toggleBtn && sidebar && mainContent) {
        // Toggle sidebar on button click
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Store preference in localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restore sidebar state from localStorage on desktop
        // Auto-collapse on mobile/tablet
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        const isTablet = window.matchMedia('(max-width: 992px)').matches;
        
        if (isMobile || isTablet) {
            // Always start collapsed on mobile/tablet
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        } else {
            // Restore saved state on desktop
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            const isMobileNow = window.matchMedia('(max-width: 768px)').matches;
            const isTabletNow = window.matchMedia('(max-width: 992px)').matches;

            // Auto-collapse on mobile/tablet if not already collapsed
            if ((isMobileNow || isTabletNow) && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });
    }
});
