/**
 * Polls backend dashboard JSON endpoints to refresh stats/tables without a full page reload.
 */
const POLL_MS = 15000;

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';

    return div.innerHTML;
}

function findLiveRoots() {
    return document.querySelectorAll('[data-dashboard-live][data-poll-url][data-context]');
}

function pollOnce(root) {
    const url = root.getAttribute('data-poll-url');
    const context = root.getAttribute('data-context');
    const csrf = root.getAttribute('data-csrf') ?? '';

    if (!url || !context) {
        return;
    }

    window.axios
        .get(url, {
            headers: { Accept: 'application/json' },
        })
        .then((response) => {
            const data = response.data;
            if (context === 'summary') {
                applySummary(data);
            } else if (context === 'admin') {
                applyAdmin(data, csrf);
            } else if (context === 'nurse') {
                applyNurse(data);
            } else if (context === 'staff') {
                applyStaff(data);
            }
        })
        .catch(() => {
            /* ignore transient errors; next poll will retry */
        });
}

function applySummary(data) {
    const map = {
        todayCount: data.todayCount,
        pendingCount: data.pendingCount,
        approvedToday: data.approvedToday,
    };
    Object.entries(map).forEach(([key, value]) => {
        const el = document.querySelector(`[data-live-stat="${key}"]`);
        if (el && typeof value !== 'undefined') {
            el.textContent = String(value);
        }
    });
}

function applyAdmin(data, csrf) {
    const pendingEl = document.querySelector('[data-live-stat="pendingCount"]');
    if (pendingEl && typeof data.pendingCount !== 'undefined') {
        pendingEl.textContent = String(data.pendingCount);
    }

    const body = document.getElementById('dashboard-live-admin-body');
    if (!body || !Array.isArray(data.appointments)) {
        return;
    }

    if (data.appointments.length === 0) {
        body.innerHTML = '<p class="p-6 text-slate-500">No appointments today.</p>';

        return;
    }

    const rows = data.appointments
        .map((a) => {
            const statusHtml =
                a.is_approved === true
                    ? '<span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>'
                    : '<span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>';

            const approveBlock =
                a.approve_url && csrf
                    ? `<form action="${escapeHtml(a.approve_url)}" method="POST" class="ml-2 inline">
                        <input type="hidden" name="_token" value="${escapeHtml(csrf)}">
                        <button type="submit" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Approve</button>
                       </form>`
                    : '';

            return `<tr class="hover:bg-slate-50/50">
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">${escapeHtml(a.scheduled_time_display)}</td>
                <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.patient_name)}</td>
                <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.service_name)}</td>
                <td class="px-4 py-3">${statusHtml}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <a href="${escapeHtml(a.show_url)}" class="text-sm font-medium text-teal-600 hover:text-teal-700">View</a>
                    ${approveBlock}
                </td>
            </tr>`;
        })
        .join('');

    body.innerHTML = `<div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">${rows}</tbody>
        </table>
    </div>`;
}

function applyNurse(data) {
    const body = document.getElementById('dashboard-live-nurse-body');
    if (!body || !Array.isArray(data.appointments)) {
        return;
    }

    if (data.appointments.length === 0) {
        body.innerHTML = '<p class="p-6 text-slate-500">No approved appointments today.</p>';

        return;
    }

    const rows = data.appointments
        .map(
            (a) => `<tr class="hover:bg-slate-50/50">
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">${escapeHtml(a.scheduled_time_display)}</td>
            <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.patient_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.service_name)}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(a.complaint_excerpt)}</td>
            <td class="whitespace-nowrap px-4 py-3 text-right">
                <a href="${escapeHtml(a.show_url)}" class="text-sm font-medium text-teal-600 hover:text-teal-700">View / Update</a>
            </td>
        </tr>`,
        )
        .join('');

    body.innerHTML = `<div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Complaint</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">${rows}</tbody>
        </table>
    </div>`;
}

function applyStaff(data) {
    const body = document.getElementById('dashboard-live-staff-body');
    if (!body || !Array.isArray(data.appointments)) {
        return;
    }

    if (data.appointments.length === 0) {
        body.innerHTML = '<p class="p-6 text-slate-500">No appointments today.</p>';

        return;
    }

    const rows = data.appointments
        .map((a) => {
            const statusHtml =
                a.is_approved === true
                    ? '<span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">approved</span>'
                    : '<span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">pending</span>';

            return `<tr class="hover:bg-slate-50/50">
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">${escapeHtml(a.scheduled_time_display)}</td>
                <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.patient_name)}</td>
                <td class="px-4 py-3 text-sm text-slate-700">${escapeHtml(a.service_name)}</td>
                <td class="px-4 py-3">${statusHtml}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                    <a href="${escapeHtml(a.show_url)}" class="text-sm font-medium text-teal-600 hover:text-teal-700">View</a>
                </td>
            </tr>`;
        })
        .join('');

    body.innerHTML = `<div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Time</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Service</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">${rows}</tbody>
        </table>
    </div>`;
}

function startPolling() {
    const roots = findLiveRoots();
    if (roots.length === 0) {
        return;
    }

    roots.forEach((root) => {
        pollOnce(root);
        window.setInterval(() => pollOnce(root), POLL_MS);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startPolling);
} else {
    startPolling();
}
