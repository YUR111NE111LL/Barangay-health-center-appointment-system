<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectAppointmentRequest;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view appointments');
        $query = Appointment::with(['resident', 'service'])
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        $appointments = $query->paginate(15);

        return view('tenant.appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        $this->authorize('encode appointments');
        $tenant = auth()->user()->tenant;
        $services = Service::orderBy('sort_order')->orderBy('name')->get();
        $residents = $tenant->users()->where('role', 'Resident')->orderBy('name')->get();

        return view('tenant.appointments.create', compact('services', 'residents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('encode appointments');
        $tenant = auth()->user()->tenant;
        if (! $tenant->canExceedAppointmentLimit()) {
            return back()->withInput()->withErrors([
                'scheduled_date' => 'Your health center has reached the monthly appointment limit for your plan. Please contact support to upgrade.',
            ]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['required', 'exists:services,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'complaint' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['status'] = Appointment::STATUS_PENDING;

        Appointment::create($validated);

        return redirect()->route('backend.appointments.index')->with('success', 'Appointment created.');
    }

    public function show(Appointment $appointment): View
    {
        $this->authorize('view appointments');
        $appointment->load(['resident', 'service', 'approvedByUser']);

        return view('tenant.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        $this->authorizeAppointmentEdit();
        $appointment->load(['resident', 'service']);
        $services = Service::orderBy('sort_order')->orderBy('name')->get();

        return view('tenant.appointments.edit', compact('appointment', 'services'));
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeAppointmentEdit();
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'status' => ['required', Rule::in(array_values(Appointment::statuses()))],
            'complaint' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === Appointment::STATUS_APPROVED && ! $appointment->approved_at) {
            $validated['approved_at'] = now();
            $validated['approved_by'] = auth()->id();
        }

        $appointment->update($validated);

        return redirect()->route('backend.appointments.show', $appointment)->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('encode appointments');
        $appointment->delete();

        return redirect()->route('backend.appointments.index')->with('success', 'Appointment cancelled/deleted.');
    }

    /**
     * Approve a pending appointment.
     */
    public function approve(Appointment $appointment): RedirectResponse
    {
        $this->authorize('approve appointments');
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return back()->with('error', 'Only pending appointments can be approved.');
        }

        $payload = [
            'status' => Appointment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ];
        if ($this->appointmentsTableHasRejectionReasonColumn($appointment)) {
            $payload['rejection_reason'] = null;
        }

        $appointment->update($payload);

        return back()->with('success', __('Appointment approved. The resident will be notified by email if their plan includes notifications.'));
    }

    /**
     * Reject a pending appointment (same permission as approve). Sets status to cancelled and notifies the resident like tenant-application rejection.
     */
    public function reject(RejectAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('approve appointments');
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            return back()->with('error', __('Only pending appointments can be rejected.'));
        }

        $validated = $request->validated();

        $payload = [
            'status' => Appointment::STATUS_CANCELLED,
            'approved_at' => null,
            'approved_by' => null,
        ];
        $reason = $validated['rejection_reason'] ?? null;
        if ($this->appointmentsTableHasRejectionReasonColumn($appointment)) {
            $payload['rejection_reason'] = $reason;
        } elseif (filled($reason)) {
            $payload['notes'] = trim(($appointment->notes ?? '')."\n\n[".__('Rejection note')."]\n".$reason);
        }

        $appointment->update($payload);

        return back()->with('success', __('Appointment request rejected. The resident will be notified by email if their plan includes notifications.'));
    }

    /**
     * Tenant DBs created before the rejection_reason migration do not have the column until tenants:migrate is run.
     */
    private function appointmentsTableHasRejectionReasonColumn(Appointment $appointment): bool
    {
        return Schema::connection($appointment->getConnectionName())->hasColumn(
            $appointment->getTable(),
            'rejection_reason',
        );
    }

    /** Allow edit/update if user has any of: encode appointments, update visit status, record notes. */
    private function authorizeAppointmentEdit(): void
    {
        $user = auth()->user();
        if ($user->can('encode appointments') || $user->can('update visit status') || $user->can('record notes')) {
            return;
        }
        abort(403, 'You do not have permission to edit appointments.');
    }
}
