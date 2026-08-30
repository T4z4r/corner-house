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
});
