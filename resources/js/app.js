import './bootstrap';
import '../css/admin-dashboard.css';
import '../css/ipms-layout.css';
import '../css/kpi-target.css';
import '../css/realisasi.css';
import '../css/user-management.css';
import '../css/ulp-dashboard.css';
import '../css/ulp-monitoring.css';
import '../css/ulp-statistik.css';
import '../css/ulp-ranking.css';
import '../css/ulp-infografis.css';
import '../css/ulp-laporan.css';
import '../css/admin-monitoring.css';
import '../css/admin-statistik.css';
import '../css/admin-ranking.css';
import '../css/admin-infografis.css';
import '../css/admin-laporan.css';

import './admin-dashboard';
import './ulp-dashboard';
import './ulp-monitoring';
import './ulp-statistik';
import './ulp-ranking';
import './ulp-infografis';
import './ulp-laporan';
import './admin-monitoring';
import './admin-statistik';
import './admin-ranking';
import './admin-infografis';
import './admin-laporan';
import './kpi-target';
import './realisasi';
import './realisasi-import';
import './target-edit';
import './notifications';

function initializePasswordToggle() {
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');

    if (!password || !toggle) {
        return;
    }

    toggle.setAttribute('type', 'button');
    toggle.setAttribute('aria-controls', password.id);

    function updateState() {
        const visible = password.type === 'text';

        toggle.setAttribute(
            'aria-label',
            visible ? 'Sembunyikan password' : 'Tampilkan password'
        );

        toggle.setAttribute('aria-pressed', String(visible));
    }

    updateState();

    toggle.addEventListener('click', () => {
        password.type =
            password.type === 'password' ? 'text' : 'password';

        updateState();
    });
}

function initializeLayout() {
    if (!document.body.classList.contains('ipms-app')) {
        return;
    }

    const sidebar = document.getElementById('ipms-sidebar');
    const toggle = document.getElementById('ipms-menu-toggle');
    const backdrop = document.getElementById('ipms-backdrop');
    const profile = document.getElementById('ipms-profile-menu');
    const mobile = window.matchMedia('(max-width: 900px)');

    function setSidebarOpen(open, restoreFocus = false) {
        const isOpen = Boolean(open && mobile.matches);

        document.body.classList.toggle('sidebar-open', isOpen);

        toggle?.setAttribute('aria-expanded', String(isOpen));
        toggle?.setAttribute(
            'aria-label',
            isOpen ? 'Tutup navigasi' : 'Buka navigasi'
        );

        if (backdrop) {
            backdrop.hidden = !isOpen;
        }

        if (sidebar) {
            sidebar.inert = mobile.matches && !isOpen;
        }

        if (isOpen) {
            sidebar?.querySelector('a')?.focus();
        } else if (restoreFocus) {
            toggle?.focus();
        }
    }

    toggle?.addEventListener('click', () => {
        const open = document.body.classList.contains('sidebar-open');
        setSidebarOpen(!open, open);
    });

    backdrop?.addEventListener('click', () => {
        setSidebarOpen(false, true);
    });

    mobile.addEventListener('change', () => {
        setSidebarOpen(false);
    });

    document.addEventListener('click', (event) => {
        if (profile?.open && !profile.contains(event.target)) {
            profile.open = false;
        }
    });

    document.addEventListener('focusin', (event) => {
        if (profile?.open && !profile.contains(event.target)) {
            profile.open = false;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (profile?.open) {
            profile.open = false;
            profile.querySelector('summary')?.focus();
        }

        if (document.body.classList.contains('sidebar-open')) {
            setSidebarOpen(false, true);
        }
    });

    setSidebarOpen(false);
}

function initializeApp() {
    initializePasswordToggle();
    initializeLayout();

    if (typeof window.lucide?.createIcons === 'function') {
        window.lucide.createIcons();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp, {
        once: true,
    });
} else {
    initializeApp();
}

// Dropdown filter dengan jarak pilihan yang lebih lega.
function initializeSpaciousFilters() {
    const selector = [
        '[data-ipms-filter]',
        '#kpi-workspace select',
        '#realisasi-workspace select',
        '.dashboard-page .filter-group select',
        '.rk-field select',
        '.ig-field select',
        '.lp-field select',
        '#realisasi-ulp',
        '#realisasi-year',
        '#realisasi-month',
        '#realisasi-status',
    ].join(',');

    let activePopup = null;
    let activeTrigger = null;

    function closePopup(restoreFocus = false) {
        const trigger = activeTrigger;

        activePopup?.remove();
        trigger?.setAttribute('aria-expanded', 'false');

        activePopup = null;
        activeTrigger = null;

        if (restoreFocus) trigger?.focus();
    }

    function enhance(select) {
        if (
            select.multiple ||
            select.size > 1 ||
            select.dataset.spaciousFilter
        ) {
            return;
        }

        select.dataset.spaciousFilter = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'ipms-select-wrap';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'ipms-select-trigger';
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-haspopup', 'dialog');

        const caption = document.createElement('span');
        trigger.append(caption);

        select.before(wrapper);
        wrapper.append(select, trigger);

        select.classList.add('ipms-select-native');
        select.tabIndex = -1;

        function sync() {
            const selected = select.options[select.selectedIndex];
            const label = select.labels?.[0]?.textContent.trim() || '';

            caption.textContent = selected?.textContent || 'Pilih...';
            trigger.disabled = select.disabled;

            trigger.setAttribute(
                'aria-label',
                (label ? label + ': ' : '') + caption.textContent
            );

            trigger.title = caption.textContent;
        }

        // Ikuti perubahan nilai yang dilakukan kode filter lama.
        ['value', 'selectedIndex'].forEach(property => {
            const descriptor = Object.getOwnPropertyDescriptor(
                HTMLSelectElement.prototype,
                property
            );

            if (!descriptor?.get || !descriptor?.set) return;

            Object.defineProperty(select, property, {
                configurable: true,
                get() {
                    return descriptor.get.call(this);
                },
                set(value) {
                    descriptor.set.call(this, value);
                    sync();
                },
            });
        });

        function openPopup() {
            const wasOpen = activeTrigger === trigger;
            closePopup();

            if (wasOpen || select.disabled) return;

            sync();

            const popup = document.createElement('div');
            popup.className = 'ipms-select-popup';
            popup.setAttribute('role', 'dialog');
            popup.setAttribute(
                'aria-label',
                select.labels?.[0]?.textContent.trim() || 'Pilihan filter'
            );

            const search = document.createElement('input');
            search.type = 'search';
            search.className = 'ipms-select-search';
            search.placeholder = 'Cari pilihan...';
            search.setAttribute('aria-label', 'Cari pilihan');

            const list = document.createElement('div');
            list.className = 'ipms-select-options';

            popup.append(search, list);
            document.body.append(popup);

            activePopup = popup;
            activeTrigger = trigger;
            trigger.setAttribute('aria-expanded', 'true');

            function renderOptions() {
                const keyword = search.value.trim().toLowerCase();
                list.replaceChildren();

                Array.from(select.options).forEach(option => {
                    if (
                        option.hidden ||
                        !option.textContent.toLowerCase().includes(keyword)
                    ) {
                        return;
                    }

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'ipms-select-option';
                    button.textContent = option.textContent;

                    button.disabled =
                        option.disabled ||
                        (
                            option.parentElement instanceof HTMLOptGroupElement &&
                            option.parentElement.disabled
                        );

                    button.classList.toggle(
                        'is-selected',
                        option.selected
                    );

                    button.setAttribute(
                        'aria-pressed',
                        String(option.selected)
                    );

                    button.onclick = () => {
                        select.selectedIndex = option.index;
                        sync();
                        closePopup(true);

                        select.dispatchEvent(
                            new Event('input', { bubbles: true })
                        );

                        select.dispatchEvent(
                            new Event('change', { bubbles: true })
                        );
                    };

                    list.append(button);
                });

                if (!list.children.length) {
                    const empty = document.createElement('div');
                    empty.className = 'ipms-select-empty';
                    empty.textContent = 'Pilihan tidak ditemukan.';
                    list.append(empty);
                }
            }

            const rect = trigger.getBoundingClientRect();

            const width = Math.min(
                Math.max(rect.width, 230),
                window.innerWidth - 24
            );

            const below = window.innerHeight - rect.bottom - 16;
            const above = rect.top - 16;
            const openBelow = below >= 220 || below >= above;

            const available = Math.max(
                80,
                Math.min(340, openBelow ? below : above)
            );

            popup.style.width = width + 'px';
            popup.style.left = Math.max(
                12,
                Math.min(rect.left, window.innerWidth - width - 12)
            ) + 'px';

            list.style.maxHeight = Math.max(40, available - 65) + 'px';

            renderOptions();

            if (openBelow) {
                popup.style.top = rect.bottom + 6 + 'px';
            } else {
                popup.style.top = Math.max(
                    8,
                    rect.top - popup.offsetHeight - 6
                ) + 'px';
            }

            search.addEventListener('input', renderOptions);

            popup.addEventListener('keydown', event => {
                const buttons = Array.from(
                    list.querySelectorAll('button:not(:disabled)')
                );

                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    closePopup(true);
                    return;
                }

                if (
                    event.key !== 'ArrowDown' &&
                    event.key !== 'ArrowUp'
                ) {
                    return;
                }

                event.preventDefault();

                const index = buttons.indexOf(document.activeElement);
                const next = event.key === 'ArrowDown'
                    ? Math.min(index + 1, buttons.length - 1)
                    : Math.max(index - 1, 0);

                buttons[next]?.focus();
            });

            search.focus();
        }

        trigger.addEventListener('click', openPopup);

        trigger.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                openPopup();
            }
        });

        select.addEventListener('change', sync);

        select.addEventListener('focus', () => {
            trigger.focus();
        });

        select.form?.addEventListener('reset', () => {
            setTimeout(sync, 0);
        });

        new MutationObserver(() => {
            sync();

            if (activeTrigger === trigger) {
                closePopup();
            }
        }).observe(select, {
            childList: true,
            subtree: true,
            attributes: true,
            characterData: true,
        });

        sync();
    }

    function scan() {
        document.querySelectorAll(selector).forEach(enhance);
    }

    document.addEventListener('pointerdown', event => {
        if (
            activePopup &&
            !activePopup.contains(event.target) &&
            !activeTrigger?.contains(event.target)
        ) {
            closePopup();
        }
    });

    document.addEventListener('focusin', event => {
        if (
            activePopup &&
            !activePopup.contains(event.target) &&
            event.target !== activeTrigger
        ) {
            closePopup();
        }
    });

    window.addEventListener('resize', () => closePopup());

    window.addEventListener('scroll', event => {
        if (activePopup && !activePopup.contains(event.target)) {
            closePopup();
        }
    }, true);

    scan();

    new MutationObserver(scan).observe(document.body, {
        childList: true,
        subtree: true,
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeSpaciousFilters,
        { once: true }
    );
} else {
    initializeSpaciousFilters();
}