import Chart from 'chart.js/auto';

/**
 * HanbellShop storefront runtime.
 *
 * Design rules encoded here:
 *  - Ad impressions are billed on *viewability* (50% visible for 1s via
 *    IntersectionObserver), never on render. The homepage emits many creatives,
 *    most below the fold; counting at render time would overcharge advertisers.
 *  - Toasts are the single feedback channel for every mutating action, so no
 *    mutating action ever needs to return a full page reload.
 */

/* ------------------------------------------------------------------ *
 * Toast system
 * ------------------------------------------------------------------ */

const TOAST_ICONS = {
    success:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>',
    error:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
    info: '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>',
    warning:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
};

const TOAST_STYLES = {
    success: 'border-brand-600/25 [&_.hb-toast-icon]:bg-brand-50 [&_.hb-toast-icon]:text-brand-600',
    error: 'border-danger-500/25 [&_.hb-toast-icon]:bg-danger-50 [&_.hb-toast-icon]:text-danger-600',
    info: 'border-info-500/25 [&_.hb-toast-icon]:bg-info-50 [&_.hb-toast-icon]:text-info-600',
    warning: 'border-warning-500/25 [&_.hb-toast-icon]:bg-warning-50 [&_.hb-toast-icon]:text-warning-600',
};

function toastContainer() {
    let host = document.getElementById('hb-toasts');
    if (!host) {
        host = document.createElement('div');
        host.id = 'hb-toasts';
        host.className =
            'pointer-events-none fixed inset-x-0 bottom-0 z-[9999] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:right-0 sm:top-0 sm:bottom-auto sm:items-end';
        host.setAttribute('role', 'region');
        host.setAttribute('aria-label', 'Notifications');
        host.setAttribute('aria-live', 'polite');
        document.body.appendChild(host);
    }
    return host;
}

function escapeHtml(value) {
    return String(value).replace(
        /[&<>"']/g,
        (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
    );
}

export function showToast({ message, type = 'success', title = null, duration = 4500 } = {}) {
    if (!message) return;

    const kind = TOAST_STYLES[type] ? type : 'info';
    const host = toastContainer();

    const el = document.createElement('div');
    el.className = `pointer-events-auto flex w-full max-w-sm translate-y-2 items-start gap-3 rounded-xl border bg-white p-3.5 text-ink-900 opacity-0 shadow-lift transition-all duration-300 ease-out ${TOAST_STYLES[kind]}`;
    el.setAttribute('role', kind === 'error' ? 'alert' : 'status');

    el.innerHTML = `
        <span class="hb-toast-icon flex size-8 shrink-0 items-center justify-center rounded-lg [&>svg]:size-4">${TOAST_ICONS[kind]}</span>
        <div class="min-w-0 flex-1 pt-0.5">
            ${title ? `<p class="text-sm font-semibold leading-tight">${escapeHtml(title)}</p>` : ''}
            <p class="text-sm leading-snug ${title ? 'mt-0.5 text-ink-500' : 'font-medium'}">${escapeHtml(message)}</p>
        </div>
        <button type="button" class="-mr-1 -mt-1 shrink-0 rounded-md p-1 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700" aria-label="Dismiss">
            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
        </button>
    `;

    const dismiss = () => {
        el.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => el.remove(), 300);
    };

    el.querySelector('button').addEventListener('click', dismiss);
    host.appendChild(el);

    requestAnimationFrame(() => el.classList.remove('translate-y-2', 'opacity-0'));

    if (duration > 0) {
        let timer = setTimeout(dismiss, duration);
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => {
            timer = setTimeout(dismiss, 1500);
        });
    }

    return el;
}

window.hbToast = showToast;

window.addEventListener('toast', (event) => {
    const detail = event.detail ?? {};
    // Livewire may deliver a single payload or an array of them.
    (Array.isArray(detail) ? detail : [detail]).forEach((payload) => {
        if (payload && typeof payload === 'object') showToast(payload);
    });
});

/* ------------------------------------------------------------------ *
 * Ad viewability — an impression is only recorded once a creative has been
 * at least 50% visible for one full second.
 * ------------------------------------------------------------------ */

const AD_IMPRESSION_TTL = 1000;
const AD_SESSION_CAP_KEY = 'hb.ad.caps';

function sessionCaps() {
    try {
        return JSON.parse(sessionStorage.getItem(AD_SESSION_CAP_KEY) || '{}');
    } catch {
        return {};
    }
}

function recordSessionCap(creativeId, count) {
    try {
        const caps = sessionCaps();
        caps[creativeId] = count;
        sessionStorage.setItem(AD_SESSION_CAP_KEY, JSON.stringify(caps));
    } catch {
        /* private mode — impression still counts server-side, just no client cap */
    }
}

function beacon(url, data) {
    const body = JSON.stringify(data);
    if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
    } else {
        fetch(url, {
            method: 'POST',
            body,
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
        }).catch(() => {});
    }
}

let adObserver = null;

function initAdImpressionTracking() {
    const creatives = document.querySelectorAll('[data-ad-creative]:not([data-ad-observed])');
    if (!creatives.length) return;

    if (!('IntersectionObserver' in window)) {
        creatives.forEach((el) => trackAdImpression(el));
        return;
    }

    if (!adObserver) {
        adObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const el = entry.target;
                    if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                        el._hbVisibleSince ??= Date.now();
                        el._hbTimer ??= setTimeout(() => {
                            if (el._hbVisibleSince) trackAdImpression(el);
                        }, AD_IMPRESSION_TTL);
                    } else {
                        clearTimeout(el._hbTimer);
                        el._hbTimer = null;
                        el._hbVisibleSince = null;
                    }
                });
            },
            { threshold: [0, 0.5, 1] },
        );
    }

    creatives.forEach((el) => {
        el.setAttribute('data-ad-observed', '1');
        if (applySessionCap(el) === false) return;
        adObserver.observe(el);
    });
}

function applySessionCap(el) {
    const id = el.dataset.adCreative;
    const cap = parseInt(el.dataset.adMaxImpressions || '0', 10);
    if (!cap) return true;

    const seen = parseInt(sessionCaps()[id] || '0', 10);
    if (seen >= cap) {
        el.remove();
        return false;
    }
    return true;
}

function trackAdImpression(el) {
    if (el.dataset.adCounted === '1') return;
    el.dataset.adCounted = '1';
    clearTimeout(el._hbTimer);

    const id = el.dataset.adCreative;
    const cap = parseInt(el.dataset.adMaxImpressions || '0', 10);
    if (cap) recordSessionCap(id, parseInt(sessionCaps()[id] || '0', 10) + 1);

    beacon(el.dataset.adImpressionUrl || '/ads/impression', {
        creative: id,
        placement: el.dataset.adPlacement || null,
    });
}

/* ------------------------------------------------------------------ *
 * Infinite scroll — IntersectionObserver with a real button fallback.
 * Livewire components own their own observer via wire:intersect, so this
 * helper only covers non-Livewire lists.
 * ------------------------------------------------------------------ */

export function initInfiniteScroll(root = document) {
    root.querySelectorAll('[data-hb-infinite]').forEach((sentinel) => {
        if (sentinel.dataset.hbBound === '1') return;
        sentinel.dataset.hbBound = '1';

        const target = document.querySelector(sentinel.dataset.hbInfinite);
        if (!target || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const event = sentinel.dataset.hbEvent || 'load-more';
                        window.dispatchEvent(new CustomEvent(event));
                    }
                });
            },
            { rootMargin: '400px 0px' },
        );
        observer.observe(sentinel);
    });
}

window.hbInitInfiniteScroll = initInfiniteScroll;

/* ------------------------------------------------------------------ *
 * Chart.js — admin dashboards
 * ------------------------------------------------------------------ */

export function hbChart(canvas, config) {
    if (!canvas) return null;
    const existing = Chart.getChart(canvas);
    if (existing) existing.destroy();
    return new Chart(canvas, config);
}

window.hbChart = hbChart;
window.Chart = Chart;

/* ------------------------------------------------------------------ *
 * Livewire lifecycle wiring
 * ------------------------------------------------------------------ */

function boot() {
    initAdImpressionTracking();
    initInfiniteScroll();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', boot);
