import $ from 'jquery';
import 'select2';
import 'select2/dist/css/select2.css';
import * as bootstrap from 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import { initChatWidget } from './chat-widget';
import Swal from 'sweetalert2';

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.Chart = Chart;
window.FullCalendar = { Calendar, dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin };
window.Swal = Swal;

document.addEventListener('DOMContentLoaded', function () {
    initChatWidget();
    initNotificationWidget();
    initDeviceSwitcher();
    initFormLoadingStates();
    try {
        $('select.form-select:not(.no-select2)').each(function () {
            if (!$(this).data('select2')) {
                $(this).select2({ width: '100%' });
            }
        });
    } catch (e) {
        console.warn('Select2 init skipped:', e);
    }

    const STORAGE_KEY = 'cornerhouse.sidebarCollapsed';

    function readCollapsed() {
        try {
            return localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function writeCollapsed(collapsed) {
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (e) {
            /* storage unavailable */
        }
    }

    function applySidebarState(collapsed, updateButton) {
        document.body.classList.toggle('ch-collapsed', collapsed);
        writeCollapsed(collapsed);
        if (updateButton === false) {
            return;
        }
        $('[data-toggle-sidebar] .bi:first').each(function () {
            const $icon = $(this);
            if (collapsed) {
                $icon.removeClass('bi-layout-sidebar').addClass('bi-layout-sidebar-inset');
            } else {
                $icon.removeClass('bi-layout-sidebar-inset').addClass('bi-layout-sidebar');
            }
        });
        $('#sidebarCollapseToggle span').each(function () {
            $(this).text(collapsed ? 'Expand' : 'Collapse');
        });
    }

    applySidebarState(readCollapsed(), false);

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-toggle-sidebar]');
        if (toggle) {
            applySidebarState(!document.body.classList.contains('ch-collapsed'), true);
        }
    });

    (function initPageLoader() {
        const wrap = document.getElementById('pageLoaderWrap');
        if (!wrap || wrap.hasAttribute('data-loader-disabled')) {
            return;
        }
        wrap.classList.remove('is-loaded');
        const layer = document.getElementById('pageLoader');
        const content = wrap.querySelector('.ch-page-content');
        let settled = false;

        const syncHeight = () => {
            if (!layer || !content) {
                return;
            }
            const target = Math.ceil(content.offsetHeight);
            const current = parseInt(layer.style.height, 10) || 0;
            if (Math.abs(target - current) > 1) {
                layer.style.height = target + 'px';
            }
        };
        syncHeight();

        let observer = null;
        if (typeof ResizeObserver !== 'undefined' && content) {
            observer = new ResizeObserver(syncHeight);
            observer.observe(content);
        }

        const finish = () => {
            if (settled) {
                return;
            }
            settled = true;
            if (observer) {
                observer.disconnect();
                observer = null;
            }
            wrap.classList.add('is-loaded');
            setTimeout(() => {
                if (layer) {
                    layer.remove();
                }
            }, 350);
        };

        if (document.readyState === 'complete') {
            setTimeout(finish, 50);
        } else {
            window.addEventListener('load', finish, { once: true });
            // Safety net in case 'load' never fires (e.g. blocked resources).
            setTimeout(finish, 1500);
        }
    })();

    function findSubmitButton(form, submitter) {
        if (submitter && (submitter.tagName === 'BUTTON' || submitter.tagName === 'INPUT') && submitter.type === 'submit') {
            return submitter;
        }
        return Array.from(form.elements).find(function (el) {
            return (el.tagName === 'BUTTON' || el.tagName === 'INPUT') && el.type === 'submit';
        });
    }

    // Disables the triggering submit button and shows a spinner; used directly by
    // the data-confirm handler too, since confirmed submits bypass the submit event.
    function setFormSubmitting(form, submitter) {
        const button = findSubmitButton(form, submitter);
        if (!button || button.disabled) {
            return;
        }

        button.disabled = true;
        button.classList.add('disabled');

        if (button.tagName === 'INPUT') {
            button.dataset.chOriginalValue = button.value;
            button.value = 'Please wait…';
            return;
        }

        button.dataset.chOriginalHtml = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + button.innerHTML;
    }
    window.setFormSubmitting = setFormSubmitting;

    function initFormLoadingStates() {
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            // data-confirm forms submit programmatically after the dialog resolves,
            // which never fires this event, so they set their own loading state.
            if (form.hasAttribute('data-skip-loading-state') || form.hasAttribute('data-confirm')) {
                return;
            }
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            setFormSubmitting(form, event.submitter);
        });

        // Restore buttons left disabled when a page is served from the back/forward cache.
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) {
                return;
            }
            document.querySelectorAll('[data-ch-original-html], [data-ch-original-value]').forEach(function (button) {
                button.disabled = false;
                button.classList.remove('disabled');
                if (button.dataset.chOriginalHtml !== undefined) {
                    button.innerHTML = button.dataset.chOriginalHtml;
                    delete button.dataset.chOriginalHtml;
                }
                if (button.dataset.chOriginalValue !== undefined) {
                    button.value = button.dataset.chOriginalValue;
                    delete button.dataset.chOriginalValue;
                }
            });
        });
    }

    function initNotificationWidget() {
        const widget = document.querySelector('[data-notifications-widget]');
        if (!widget) {
            return;
        }

        const feedUrl = widget.dataset.notificationsFeedUrl;
        const badge = widget.querySelector('[data-notifications-badge]');
        let latestId = widget.dataset.notificationsLatestId || null;
        let loadedOnce = false;
        let requestInFlight = false;
        let audioContext = null;

        function updateBadge(count) {
            if (!badge) {
                return;
            }

            badge.textContent = String(count);
            badge.classList.toggle('d-none', count <= 0);
        }

        function playNotificationSound() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) {
                    return;
                }

                if (!audioContext) {
                    audioContext = new AudioContextClass();
                }

                if (audioContext.state === 'suspended') {
                    audioContext.resume().catch(() => {});
                }

                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.value = 880;
                gainNode.gain.value = 0.0001;

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.start();
                gainNode.gain.exponentialRampToValueAtTime(0.15, audioContext.currentTime + 0.02);
                gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.18);
                oscillator.stop(audioContext.currentTime + 0.2);
            } catch (error) {
                console.warn('Notification sound skipped:', error);
            }
        }

        async function fetchNotifications() {
            if (!feedUrl || requestInFlight) {
                return;
            }

            requestInFlight = true;

            try {
                const response = await fetch(feedUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const incomingLatestId = payload.latest_id || null;
                const unreadCount = Number(payload.unread_count || 0);
                const changed = loadedOnce && incomingLatestId && incomingLatestId !== latestId;

                updateBadge(unreadCount);

                if (changed) {
                    playNotificationSound();
                }

                latestId = incomingLatestId;
                loadedOnce = true;
            } catch (error) {
                console.warn('Notification polling skipped:', error);
            } finally {
                requestInFlight = false;
            }
        }

        window.addEventListener('pointerdown', function primeAudioContext() {
            if (!audioContext && (window.AudioContext || window.webkitAudioContext)) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }

            if (audioContext?.state === 'suspended') {
                audioContext.resume().catch(() => {});
            }
        }, { once: true });

        updateBadge(Number(widget.dataset.notificationsCount || 0));
        fetchNotifications();
        window.setInterval(fetchNotifications, 30000);
    }

    function initDeviceSwitcher() {
        const STORAGE_KEY = 'cornerhouse.deviceView';
        const switcher = document.getElementById('deviceSwitcher');
        if (!switcher) return;

        const indicator = document.createElement('div');
        indicator.className = 'ch-device-indicator';
        document.body.appendChild(indicator);

        function refreshButtons(device) {
            document.querySelectorAll('#deviceSwitcher [data-device]').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.device === device);
            });
        }

        function applyDevice(device) {
            if (!device) {
                return;
            }
            document.body.classList.remove('ch-device-tablet', 'ch-device-mobile');
            if (device === 'tablet') {
                document.body.classList.add('ch-device-tablet');
                indicator.textContent = 'Tablet View (768px)';
            } else if (device === 'mobile') {
                document.body.classList.add('ch-device-mobile');
                indicator.textContent = 'Mobile View (375px)';
            } else {
                indicator.textContent = '';
            }

            refreshButtons(device);

            try {
                localStorage.setItem(STORAGE_KEY, device);
            } catch (error) {
                /* storage unavailable */
            }
        }

        function getStoredDevice() {
            try {
                return localStorage.getItem(STORAGE_KEY) || 'desktop';
            } catch (error) {
                return 'desktop';
            }
        }

        applyDevice(getStoredDevice());

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('#deviceSwitcher [data-device]');
            if (!trigger) {
                return;
            }
            applyDevice(trigger.dataset.device);
        });
    }
});
