<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('backend.appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        $this->authorize('encode appointments');
        $tenant = auth()->user()->tenant;
        $services = Service::orderBy('sort_order')->orderBy('name')->get();
        $residents = $tenant->users()->where('role', 'Resident')->orderBy('name')->get();

        return view('backend.appointments.create', compact('services', 'residents'));
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

        return view('backend.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        $this->authorizeAppointmentEdit();
        $appointment->load(['resident', 'service']);
        $services = Service::orderBy('sort_order')->orderBy('name')->get();

        return view('backend.appointments.edit', compact('appointment', 'services'));
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

        $appointment->update([
            'status' => Appointment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Appointment approved.');
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
