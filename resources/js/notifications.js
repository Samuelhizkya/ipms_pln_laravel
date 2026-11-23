import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const popup = Swal.mixin({
    confirmButtonText: 'Mengerti',
    confirmButtonColor: '#125D72',
    cancelButtonColor: '#64748b',
    background: '#ffffff',
    color: '#17324d',
    width: '32rem',
    heightAuto: false,
    customClass: {
        popup: 'ipms-swal-popup',
        title: 'ipms-swal-title',
        htmlContainer: 'ipms-swal-text',
        confirmButton: 'ipms-swal-button',
    },
});

function showNotification({
    icon = 'info',
    title = 'Informasi',
    text = '',
    target,
}) {
    return popup.fire({
        icon,
        title,
        text,

        ...(target ? { target } : {}),

        toast: false,
        position: 'center',
        width: '25rem',

        backdrop: true,
        heightAuto: false,

        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: '#125D72',

        showCancelButton: false,
        showCloseButton: false,

        timer: undefined,
        timerProgressBar: false,

        allowOutsideClick: false,
        allowEscapeKey: true,

        customClass: {
            popup: 'ipms-swal-popup',
            title: 'ipms-swal-title',
            htmlContainer: 'ipms-swal-text',
            confirmButton: 'ipms-swal-button',
        },
    });
}
async function initializeNotifications() {
    const container = document.getElementById('ipms-notifications');

    if (!container || container.dataset.initialized === 'true') return;

    container.dataset.initialized = 'true';

    let messages;

    try {
        messages = JSON.parse(container.dataset.messages || '[]');
    } catch {
        console.error('Format data notifikasi tidak valid.');
        return;
    }

    if (!Array.isArray(messages)) return;

    for (const notification of messages) {
        await showNotification(notification);
    }
}

// Dapat dipakai oleh fitur AJAX tanpa menambah library lagi.
window.ipmsNotify = showNotification;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeNotifications, {
        once: true,
    });
} else {
    initializeNotifications();
}
function initializeDeleteConfirmations() {
    document.querySelectorAll('form[data-confirm-delete]')
        .forEach((form) => {
            if (form.dataset.confirmInitialized === 'true') return;

            form.dataset.confirmInitialized = 'true';

            let approved = false;
            let asking = false;

            form.addEventListener('submit', async (event) => {
                if (approved) return;

                event.preventDefault();

                if (asking || !form.reportValidity()) return;

                asking = true;

                const submitter = event.submitter;

                try {
                    const result = await popup.fire({
                        icon: 'warning',
                        titleText: 'Hapus akun ini?',
                        text: form.dataset.confirmDelete,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#c64545',
                        focusCancel: true,
                        allowOutsideClick: false,
                    });

                    if (result.isConfirmed) {
                        approved = true;

                        if (submitter) {
                            form.requestSubmit(submitter);
                        } else {
                            form.requestSubmit();
                        }

                        approved = false;
                    }
                } finally {
                    asking = false;
                }
            });
        });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeDeleteConfirmations,
        { once: true }
    );
} else {
    initializeDeleteConfirmations();
}