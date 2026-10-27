document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const closeButton = document.getElementById('sidebarClose');
    const toggleButton = document.getElementById('burgerBtn');

    if (!sidebar || !backdrop || !toggleButton) return;

    function setSidebarOpen(open) {
        document.body.classList.toggle('sidebar-open', open);
        toggleButton.setAttribute('aria-expanded', String(open));
        toggleButton.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        toggleButton.innerHTML = open ? '&times;' : '&#9776;';
    }

    function closeSidebar() {
        setSidebarOpen(false);
    }

    function toggleSidebar() {
        setSidebarOpen(!document.body.classList.contains('sidebar-open'));
    }

    toggleButton.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', closeSidebar);

    if (closeButton) {
        closeButton.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.toggleAdminSidebar = toggleSidebar;
    window.closeAdminSidebar = closeSidebar;
});
