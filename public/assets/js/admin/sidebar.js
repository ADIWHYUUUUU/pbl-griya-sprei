document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const closeButton = document.getElementById('sidebarClose');
    const toggleButton = document.getElementById('burgerBtn');
    const logoutDialog = document.querySelector('.logout-dialog');
    const logoutTrigger = document.querySelector('[data-open-logout-dialog]');

    if (logoutDialog && logoutTrigger && typeof logoutDialog.showModal === 'function') {
        logoutTrigger.addEventListener('click', function () {
            logoutDialog.showModal();
        });

        logoutDialog.querySelector('[data-close-logout-dialog]')?.addEventListener('click', function () {
            logoutDialog.close();
        });
    } else if (logoutTrigger) {
        logoutTrigger.addEventListener('click', function () {
            if (window.confirm('Apakah Anda yakin ingin keluar dari sistem admin?')) {
                document.getElementById('adminLogoutForm').submit();
            }
        });
    }

    if (!sidebar || !backdrop || !toggleButton) return;

    function setSidebarOpen(open) {
        document.body.classList.toggle('sidebar-open', open);
        document.body.style.overflow = open ? 'hidden' : '';
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

    sidebar.querySelectorAll('.sidebar-nav a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.toggleAdminSidebar = toggleSidebar;
    window.closeAdminSidebar = closeSidebar;
});
