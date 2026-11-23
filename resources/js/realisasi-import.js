function initializeRealisasiImport() {
    const forms = document.querySelectorAll(
        'form[enctype="multipart/form-data"]'
    );

    forms.forEach((form) => {
        const action = new URL(form.action, window.location.href);
        const path = action.pathname.replace(/\/+$/, '');

        const isTarget = path.endsWith('/target-kpi/import');

if (!isTarget && !path.endsWith('/realisasi-kpi/import')) {
    return;
}

        if (form.dataset.importInitialized === 'true') {
            return;
        }

        const fileInput = form.querySelector(
            'input[type="file"][name="file"]'
        );

        const submitButton = form.querySelector(
            'button[type="submit"], input[type="submit"]'
        );

        if (!fileInput || !submitButton) {
            return;
        }

        form.dataset.importInitialized = 'true';

        const maximumSize = 10 * 1024 * 1024;
        const allowedExtensions = isTarget
    ? ['xlsx', 'xls', 'csv']
    : ['xlsx', 'xls'];

        let submitting = false;
        const initiallyDisabled = submitButton.disabled;

        const buttonIsInput = submitButton.tagName === 'INPUT';

        const originalLabel = buttonIsInput
            ? submitButton.value
            : submitButton.textContent;

        const feedback = document.createElement('p');

        feedback.className = 'realisasi-file-feedback';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        feedback.setAttribute('aria-atomic', 'true');

        fileInput.insertAdjacentElement('afterend', feedback);

        fileInput.accept = allowedExtensions
    .map((extension) => `.${extension}`)
    .join(',');
        fileInput.required = true;

        function setButtonLabel(label) {
            if (buttonIsInput) {
                submitButton.value = label;
            } else {
                submitButton.textContent = label;
            }
        }

        function setFeedback(message, state = 'info') {
            feedback.textContent = message;
            feedback.dataset.state = state;
        }

        function formatSize(bytes) {
            if (bytes < 1024 * 1024) {
                return `${new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 1,
                }).format(bytes / 1024)} KB`;
            }

            return `${new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 2,
            }).format(bytes / (1024 * 1024))} MB`;
        }

        function validateFile(showNativeMessage = false) {
            fileInput.setCustomValidity('');
            fileInput.removeAttribute('aria-invalid');

            const file = fileInput.files?.[0];

            let error = '';

            if (!file) {
                error = 'Pilih file yang akan diimpor terlebih dahulu.';
            } else {
                const extension = file.name
                    .split('.')
                    .pop()
                    .toLowerCase();

                if (!allowedExtensions.includes(extension)) {
                    error = `Gunakan format: ${allowedExtensions.join(', ')}.`;
                } else if (file.size === 0) {
                    error = 'File kosong. Pilih file Excel yang berisi data.';
                } else if (file.size > maximumSize) {
                    error = 'Ukuran file melebihi batas 10 MB.';
                }
            }

            if (error) {
                fileInput.setCustomValidity(error);
                fileInput.setAttribute('aria-invalid', 'true');

                setFeedback(error, 'error');

                if (showNativeMessage) {
                    fileInput.reportValidity();
                }

                return false;
            }

            setFeedback(
                `File dipilih: ${file.name} (${formatSize(file.size)}). ` +
                'Isi Excel akan diperiksa oleh sistem setelah dikirim.',
                'info'
            );

            return true;
        }

        function resetSubmissionState() {
            submitting = false;
            submitButton.disabled = initiallyDisabled;
            form.removeAttribute('aria-busy');
            setButtonLabel(originalLabel);

            if (fileInput.files?.length) {
                validateFile();
            } else {
                fileInput.setCustomValidity('');
                fileInput.removeAttribute('aria-invalid');

                setFeedback(
                    `Format: ${allowedExtensions.join(', ')}. Maksimal 10 MB.`
                );
            }
        }

        fileInput.addEventListener('change', () => {
            validateFile();
        });

        fileInput.addEventListener('invalid', () => {
            validateFile();
        });

        form.addEventListener('submit', (event) => {
            if (submitting || initiallyDisabled) {
                event.preventDefault();
                return;
            }

            if (!validateFile(true)) {
                event.preventDefault();
                return;
            }

            if (!form.reportValidity()) {
                event.preventDefault();
                return;
            }

            submitting = true;
            submitButton.disabled = true;

            form.setAttribute('aria-busy', 'true');

            setButtonLabel('Mengimpor data...');

            setFeedback(
                'File sedang dikirim untuk diperiksa dan diproses. ' +
                'Tunggu sampai halaman menampilkan hasilnya.',
                'loading'
            );

            // Biarkan browser mengirim form POST seperti biasa.
            // Jangan menonaktifkan input file atau pilihan periode:
            // nilainya masih dibutuhkan oleh backend.
        });

        form.addEventListener('reset', () => {
            window.setTimeout(resetSubmissionState, 0);
        });

        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                resetSubmissionState();
            }
        });

        resetSubmissionState();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeRealisasiImport,
        { once: true }
    );
} else {
    initializeRealisasiImport();
}