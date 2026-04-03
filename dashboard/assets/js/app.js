// Kalina Engineering Admin Dashboard — app.js

document.addEventListener('DOMContentLoaded', () => {

    // Sidebar toggle
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Auto-dismiss alerts after 4 seconds
    document.querySelectorAll('.alert.alert-success, .alert.alert-warning').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });

    // Confirm delete for any form with data-confirm
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Highlight active table row on click
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a, form')) return;
            this.classList.toggle('table-active');
        });
    });

    // Format currency inputs on blur
    document.querySelectorAll('input[name="monthly_salary"]').forEach(el => {
        el.addEventListener('blur', () => {
            const val = parseFloat(el.value);
            if (!isNaN(val)) el.value = val.toFixed(2);
        });
    });
});
