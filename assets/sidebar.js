/**
 * sidebar.js — shared sidebar toggle behavior for all admin/cashier pages.
 *
 * Desktop/tablet-landscape (>1024px): toggle button collapses sidebar to
 * icon-only width and expands main content (existing "collapsed" behavior).
 *
 * Mobile/tablet-portrait (<=1024px): sidebar is off-canvas. Toggle button
 * slides it in/out over the content, with a dark backdrop. Tapping the
 * backdrop, tapping a nav link, or resizing past the breakpoint closes it.
 */
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.flex-grow-1');

    if (!sidebarToggle || !sidebar) return;

    // Create a backdrop element once, shared across pages.
    let backdrop = document.getElementById('sidebarBackdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'sidebarBackdrop';
        document.body.appendChild(backdrop);
    }

    function isMobile() {
        return window.innerWidth <= 1024;
    }

    function updateIcon() {
        const icon = sidebarToggle.querySelector('i');
        if (!icon) return;
        const isOpen = isMobile()
            ? sidebar.classList.contains('sidebar-open')
            : !sidebar.classList.contains('sidebar-collapsed');
        icon.className = isOpen ? 'fas fa-times' : 'fas fa-bars';
    }

    function closeMobileSidebar() {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('show');
        updateIcon();
    }

    sidebarToggle.addEventListener('click', function () {
        if (isMobile()) {
            const opening = !sidebar.classList.contains('sidebar-open');
            sidebar.classList.toggle('sidebar-open');
            backdrop.classList.toggle('show', opening);
        } else {
            sidebar.classList.toggle('sidebar-collapsed');
            if (mainContent) mainContent.classList.toggle('main-expanded');
        }
        updateIcon();
    });

    // Tap outside (the dark backdrop) closes the sidebar on mobile.
    backdrop.addEventListener('click', closeMobileSidebar);

    // Tapping a nav link inside the sidebar closes it on mobile so the
    // sidebar doesn't stay open over the new page content.
    sidebar.querySelectorAll('.nav-link, a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) closeMobileSidebar();
        });
    });

    // If the viewport is resized past the breakpoint (e.g. rotating a
    // tablet to landscape, or a desktop window resize), make sure we
    // don't get stuck in an inconsistent open/backdrop state.
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            backdrop.classList.remove('show');
        }
        updateIcon();
    });

    updateIcon();
});
