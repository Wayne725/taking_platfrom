/**
 * utils.js - Shared UI utilities
 */

/**
 * Displays an error/success message in an element with id `elId`.
 */
function showMessage(elId, message, type = 'error') {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = message;
    el.className = `message message-${type}`;
    el.style.display = 'block';
}

function hideMessage(elId) {
    const el = document.getElementById(elId);
    if (el) el.style.display = 'none';
}

/**
 * Translates status enum to a human-readable Chinese label.
 */
function statusLabel(status) {
    const map = {
        open:                           '開放申請',
        assigned:                       '已指派',
        in_progress:                    '進行中',
        completed_pending_confirmation: '待確認完成',
        completed:                      '已完成',
    };
    return map[status] || status;
}

/**
 * Returns a CSS class name for the status badge.
 */
function statusClass(status) {
    const map = {
        open:                           'badge-open',
        assigned:                       'badge-assigned',
        in_progress:                    'badge-progress',
        completed_pending_confirmation: 'badge-pending',
        completed:                      'badge-completed',
    };
    return 'badge ' + (map[status] || '');
}

/**
 * Formats a number as TWD currency string.
 */
function formatBudget(amount) {
    return 'NT$ ' + Number(amount).toLocaleString('zh-TW');
}

/**
 * Formats an ISO date string as YYYY-MM-DD.
 */
function formatDate(dateStr) {
    if (!dateStr) return '—';
    return dateStr.substring(0, 10);
}

/**
 * Escapes HTML to prevent XSS when using innerHTML.
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str ?? '')));
    return div.innerHTML;
}

/**
 * Creates a DOM element with optional className and textContent.
 */
function el(tag, opts = {}) {
    const node = document.createElement(tag);
    if (opts.className) node.className = opts.className;
    if (opts.text !== undefined) node.textContent = opts.text;
    if (opts.html !== undefined) node.innerHTML = opts.html;
    if (opts.attrs) {
        for (const [k, v] of Object.entries(opts.attrs)) {
            node.setAttribute(k, v);
        }
    }
    return node;
}
