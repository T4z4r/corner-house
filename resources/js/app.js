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

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.Chart = Chart;
window.FullCalendar = { Calendar, dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin };

document.addEventListener('DOMContentLoaded', function () {
    initChatWidget();
    initNotificationWidget();
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

    function initNotificationWidget() {
        const widget = document.querySelector('[data-notifications-widget]');
        if (!widget) {
            return;
        }

        const feedUrl = widget.dataset.notificationsFeedUrl;
        const readAllUrl = widget.dataset.notificationsReadAllUrl;
        const badge = widget.querySelector('[data-notifications-badge]');
        const summary = widget.querySelector('[data-notifications-summary]');
        const list = widget.querySelector('[data-notifications-list]');
        const markAllReadButton = widget.querySelector('[data-notifications-mark-all-read]');
        let latestId = widget.dataset.notificationsLatestId || null;
        let loadedOnce = false;
        let requestInFlight = false;

        const levelClassMap = {
            success: 'text-bg-success',
            warning: 'text-bg-warning',
            danger: 'text-bg-danger',
            info: 'text-bg-info',
        };

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function updateBadge(count) {
            if (!badge) {
                return;
            }

            badge.textContent = String(count);
            badge.classList.toggle('d-none', count <= 0);
        }

        function updateSummary(count) {
            if (!summary) {
                return;
            }

            summary.textContent = count === 1 ? '1 unread' : `${count} unread`;
        }

        function playNotificationSound() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) {
                    return;
                }

                const audioContext = new AudioContextClass();
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
                oscillator.onended = () => audioContext.close().catch(() => {});
            } catch (error) {
                console.warn('Notification sound skipped:', error);
            }
        }

        function renderNotifications(notifications) {
            if (!list) {
                return;
            }

            if (!notifications.length) {
                list.innerHTML = `
                    <div class="px-3 py-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                        No notifications yet.
                    </div>
                `;
                return;
            }

            list.innerHTML = notifications.map((notification) => {
                const badgeClass = levelClassMap[notification.level] || levelClassMap.info;
                const unreadClass = notification.read_at ? '' : ' ch-notification-unread';
                const newBadge = notification.read_at
                    ? ''
                    : '<span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">New</span>';

                return `
                    <a class="dropdown-item py-3 border-bottom${unreadClass}" href="${notification.url}" data-notification-link data-notification-id="${notification.id}">
                        <div class="d-flex gap-3">
                            <div class="ch-notification-icon ${badgeClass}">
                                <i class="bi ${notification.icon}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="fw-semibold text-dark">${escapeHtml(notification.title)}</div>
                                    ${newBadge}
                                </div>
                                <div class="small text-muted">${escapeHtml(notification.message || '')}</div>
                                <div class="small text-muted mt-1">${escapeHtml(notification.diff_for_humans || '')}</div>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');
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

                renderNotifications(payload.notifications || []);
                updateBadge(unreadCount);
                updateSummary(unreadCount);

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

        async function markAllRead() {
            if (!readAllUrl) {
                return;
            }

            try {
                await fetch(readAllUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                });

                await fetchNotifications();
            } catch (error) {
                console.warn('Unable to mark notifications as read:', error);
            }
        }

        widget.addEventListener('click', async function (event) {
            const link = event.target.closest('[data-notification-link]');
            if (!link) {
                return;
            }

            event.preventDefault();

            try {
                await fetch(`${feedUrl.replace(/\/feed$/, '')}/${link.dataset.notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                });
            } catch (error) {
                console.warn('Unable to mark notification as read:', error);
            }

            window.location.href = link.href;
        });

        if (markAllReadButton) {
            markAllReadButton.addEventListener('click', function (event) {
                event.preventDefault();
                markAllRead();
            });
        }

        updateBadge(Number(widget.dataset.notificationsCount || 0));
        updateSummary(Number(widget.dataset.notificationsCount || 0));
        fetchNotifications();
        window.setInterval(fetchNotifications, 30000);
    }
});
