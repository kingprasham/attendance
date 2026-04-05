// Kalina Engineering Admin Dashboard — app.js

document.addEventListener('DOMContentLoaded', () => {

    const toggle   = document.getElementById('sidebarToggle');
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    const isMobile = () => window.innerWidth < 992;

    // ── Sidebar toggle ────────────────────────────────────
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            if (isMobile()) {
                const isOpen = sidebar.classList.toggle('open');
                backdrop && backdrop.classList.toggle('show', isOpen);
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }

    // Close sidebar when backdrop is clicked
    if (backdrop) {
        backdrop.addEventListener('click', () => {
            sidebar && sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        });
    }

    // Close sidebar on resize to desktop
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            sidebar && sidebar.classList.remove('open');
            backdrop && backdrop.classList.remove('show');
        }
    });

    // ── Auto-dismiss alerts ───────────────────────────────
    document.querySelectorAll('.alert.alert-success, .alert.alert-warning').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });

    // ── Confirm delete ────────────────────────────────────
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // ── Highlight table row on click ──────────────────────
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a, form')) return;
            this.classList.toggle('table-active');
        });
    });

    // ── Format currency inputs ────────────────────────────
    document.querySelectorAll('input[name="monthly_salary"]').forEach(el => {
        el.addEventListener('blur', () => {
            const val = parseFloat(el.value);
            if (!isNaN(val)) el.value = val.toFixed(2);
        });
    });
});
