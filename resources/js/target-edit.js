function initializeTargetEdit() {
    const openButton = document.getElementById('target-edit-open');
    const form = document.getElementById('target-edit-form');
    const dialog = document.getElementById('kpi-dialog');

    if (!openButton || !form || !dialog) return;
    if (form.dataset.initialized === 'true') return;

    form.dataset.initialized = 'true';

    const fields = document.getElementById('target-edit-fields');
    const monthlyContainer = document.getElementById('target-edit-months');
    const message = document.getElementById('target-edit-message');
    const cancelButton = document.getElementById('target-edit-cancel');

    const months = [
        'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember',
    ];

    let busy = false;
    let editingId = null;
    let generation = 0;

    function setMessage(text, error = false) {
        message.textContent = text;
        message.classList.toggle('is-error', error);
    }

    function setBusy(value) {
        busy = value;
        fields.disabled = value;
        openButton.disabled = value;
        form.setAttribute('aria-busy', String(value));
    }
function showEditError(text) {
    if (typeof window.ipmsNotify === 'function') {
        setMessage('');

        window.ipmsNotify({
            icon: 'error',
            title: 'Perubahan belum berhasil',
            text,
            // Popup harus berada dalam dialog native yang sedang terbuka.
            target: dialog.open ? dialog : document.body,
        });
    } else {
        setMessage(text, true);
    }
}
    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: {
                Accept: 'application/json',
                ...options.headers,
            },
        });

        const payload = await response.json().catch(() => null);

        if (response.status === 401 || response.status === 419) {
            throw new Error(
                'Sesi berakhir. Muat ulang halaman dan login kembali.'
            );
        }

        if (!response.ok || payload?.success !== true) {
            const validation = payload?.errors
                ? Object.values(payload.errors).flat().join(' ')
                : '';

            throw new Error(
                response.status >= 500
                    ? 'Perubahan gagal disimpan. Periksa log Laravel.'
                    : validation || payload?.message || 'Permintaan gagal.'
            );
        }

        return payload;
    }

    openButton.addEventListener('click', async () => {
        if (busy) return;

        const id = openButton.dataset.targetId;
        if (!id) return;

        const currentGeneration = ++generation;

        editingId = null;
        form.hidden = true;
        setBusy(true);
        setMessage('Memuat target terbaru...');

        try {
            const url = openButton.dataset.readTemplate.replace('__ID__', id);
            const payload = await request(url);

            if (
                currentGeneration !== generation ||
                !dialog.open ||
                openButton.dataset.targetId !== id
            ) {
                return;
            }

            const data = payload.data;

            if (
                String(data?.id_target) !== id ||
                !Array.isArray(data?.bulanan) ||
                data.bulanan.length !== 12
            ) {
                throw new Error('Format data target tidak sesuai.');
            }

            editingId = id;
            form.elements.namedItem('version').value = data.version;
            form.elements.namedItem('target_tahunan').value =
                data.target_tahunan ?? '';

            monthlyContainer.replaceChildren();

            data.bulanan.forEach((item) => {
                const month = Number(item.bulan);
                const wrapper = document.createElement('div');
                const label = document.createElement('label');
                const input = document.createElement('input');

                input.id = `target-edit-month-${month}`;
                input.name = `bulan_${month}`;
                input.type = 'number';
                input.step = 'any';
                input.value = item.nilai_target ?? '';

                label.htmlFor = input.id;
                label.textContent = months[month - 1];

                wrapper.append(label, input);
                monthlyContainer.append(wrapper);
            });

            form.hidden = false;
            setMessage('Ubah nilai yang diperlukan, kemudian simpan.');
        } catch (error) {
            if (currentGeneration === generation) {
                showEditError(error.message);
            }
        } finally {
            setBusy(false);

            if (!form.hidden && dialog.open) {
                form.elements.namedItem('target_tahunan').focus();
            }
        }
    });

    cancelButton.addEventListener('click', () => {
        if (busy) return;

        form.hidden = true;
        editingId = null;
        setMessage('Perubahan dibatalkan.');
        openButton.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (busy || !editingId || !form.reportValidity()) return;

        const payload = {
            version: form.elements.namedItem('version').value,
            target_tahunan:
                form.elements.namedItem('target_tahunan').value,
            bulanan: months.map((_, index) => {
                const value = form.elements
                    .namedItem(`bulan_${index + 1}`)
                    .value.trim();

                return value === '' ? null : value;
            }),
        };

        const url = openButton.dataset.saveTemplate.replace(
            '__ID__',
            editingId
        );

        setBusy(true);
        setMessage('Menyimpan target dan menyesuaikan realisasi terkait...');

        try {
            const response = await request(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.elements.namedItem('_token').value,
                },
                body: JSON.stringify(payload),
            });

            // Muat ulang tabel dari backend pada ULP dan tahun yang sama.
            window.location.assign(response.redirect);
        } catch (error) {
    setBusy(false);

    showEditError(
        error.message || 'Perubahan target gagal disimpan.'
    );
}
    });

    // Hindari menutup dialog ketika proses simpan sedang berlangsung.
    dialog.addEventListener('cancel', (event) => {
        if (busy) event.preventDefault();
    });

    dialog.querySelectorAll('form[method="dialog"]').forEach((closeForm) => {
        closeForm.addEventListener('submit', (event) => {
            if (busy) event.preventDefault();
        });
    });

    dialog.addEventListener('close', () => {
        generation++;
        editingId = null;
        form.hidden = true;
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTargetEdit, {
        once: true,
    });
} else {
    initializeTargetEdit();
}