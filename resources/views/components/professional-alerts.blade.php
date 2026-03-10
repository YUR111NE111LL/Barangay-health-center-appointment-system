{{-- Professional Toast Notifications & Confirm Modal --}}
{{-- Include this partial once in each layout (backend, frontend, superadmin) --}}

{{-- Toast Container --}}
<div id="toast-container" class="pointer-events-none fixed right-4 top-4 z-[9999] flex flex-col gap-2" aria-live="polite"></div>

{{-- Confirm Modal --}}
<div id="confirm-modal-overlay" class="fixed inset-0 z-[9998] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity" style="display:none;">
    <div id="confirm-modal" class="mx-4 w-full max-w-md transform rounded-2xl bg-white p-0 shadow-2xl ring-1 ring-slate-200/60 transition-all">
        <div class="flex items-start gap-4 px-6 pt-6">
            <div id="confirm-modal-icon" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                <svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div class="flex-1">
                <h3 id="confirm-modal-title" class="text-lg font-semibold text-slate-900">Are you sure?</h3>
                <p id="confirm-modal-message" class="mt-1 text-sm text-slate-500"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-6 py-4">
            <button type="button" id="confirm-modal-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">Cancel</button>
            <button type="button" id="confirm-modal-confirm" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">Confirm</button>
        </div>
    </div>
</div>

<script>
(function() {
    // ─── TOAST SYSTEM ───
    var toastContainer = document.getElementById('toast-container');

    var icons = {
        error: '<svg class="h-5 w-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        success: '<svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        info: '<svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    };

    var bgColors = {
        error: 'bg-rose-50 ring-rose-200/60',
        warning: 'bg-amber-50 ring-amber-200/60',
        success: 'bg-emerald-50 ring-emerald-200/60',
        info: 'bg-blue-50 ring-blue-200/60'
    };

    var titles = {
        error: 'Error',
        warning: 'Warning',
        success: 'Success',
        info: 'Notice'
    };

    window.showToast = function(message, type, duration) {
        type = type || 'error';
        duration = duration || 5000;
        if (!toastContainer) return;

        var toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex w-80 max-w-sm items-start gap-3 rounded-xl p-4 shadow-lg ring-1 transform transition-all duration-300 translate-x-full opacity-0 ' + (bgColors[type] || bgColors.info);
        toast.innerHTML =
            '<div class="flex-shrink-0 pt-0.5">' + (icons[type] || icons.info) + '</div>' +
            '<div class="flex-1 min-w-0">' +
                '<p class="text-sm font-semibold text-slate-800">' + (titles[type] || 'Notice') + '</p>' +
                '<p class="mt-0.5 text-sm text-slate-600">' + escapeHtml(message) + '</p>' +
            '</div>' +
            '<button type="button" class="flex-shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-white hover:text-slate-600" aria-label="Close">' +
                '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
            '</button>';

        toastContainer.appendChild(toast);

        // Close button
        toast.querySelector('button').addEventListener('click', function() { removeToast(toast); });

        // Animate in
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            });
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(function() { removeToast(toast); }, duration);
        }

        return toast;
    };

    function removeToast(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ─── CONFIRM MODAL ───
    var overlay = document.getElementById('confirm-modal-overlay');
    var modal = document.getElementById('confirm-modal');
    var modalTitle = document.getElementById('confirm-modal-title');
    var modalMessage = document.getElementById('confirm-modal-message');
    var modalIcon = document.getElementById('confirm-modal-icon');
    var cancelBtn = document.getElementById('confirm-modal-cancel');
    var confirmBtn = document.getElementById('confirm-modal-confirm');
    var pendingCallback = null;

    window.showConfirm = function(options) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        var opts = Object.assign({
            title: 'Are you sure?',
            message: '',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            type: 'danger', // danger, warning, info
            onConfirm: null
        }, options);

        modalTitle.textContent = opts.title;
        modalMessage.textContent = opts.message;
        confirmBtn.textContent = opts.confirmText;
        cancelBtn.textContent = opts.cancelText;

        // Icon + color by type
        if (opts.type === 'danger') {
            modalIcon.className = 'flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-rose-100';
            modalIcon.innerHTML = '<svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            confirmBtn.className = 'rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2';
        } else if (opts.type === 'warning') {
            modalIcon.className = 'flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-100';
            modalIcon.innerHTML = '<svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
            confirmBtn.className = 'rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2';
        } else {
            modalIcon.className = 'flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-blue-100';
            modalIcon.innerHTML = '<svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            confirmBtn.className = 'rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
        }

        pendingCallback = opts.onConfirm;

        // Show
        overlay.style.display = 'flex';
        requestAnimationFrame(function() {
            overlay.classList.remove('hidden');
            overlay.classList.add('opacity-100');
            modal.classList.add('scale-100');
        });
    };

    function closeConfirmModal(confirmed) {
        overlay.classList.add('opacity-0');
        modal.classList.remove('scale-100');
        setTimeout(function() {
            overlay.style.display = 'none';
            overlay.classList.remove('opacity-0');
            if (confirmed && typeof pendingCallback === 'function') {
                pendingCallback();
            }
            pendingCallback = null;
        }, 200);
    }

    if (cancelBtn) cancelBtn.addEventListener('click', function() { closeConfirmModal(false); });
    if (confirmBtn) confirmBtn.addEventListener('click', function() { closeConfirmModal(true); });
    if (overlay) overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeConfirmModal(false);
    });

    // ESC to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay && overlay.style.display === 'flex') {
            closeConfirmModal(false);
        }
    });

    // ─── FORM CONFIRM HELPER (replaces inline onsubmit="return confirm(...)") ───
    window.confirmFormSubmit = function(form, options) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        showConfirm(Object.assign({}, options, {
            onConfirm: function() {
                form.submit();
            }
        }));
    };
})();
</script>
