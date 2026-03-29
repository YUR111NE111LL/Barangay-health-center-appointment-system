<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(): View
    {
        if (! auth()->user()->can('book appointments')) {
            throw new AuthorizationException(__('Your health center admin has disabled booking for your account. Permissions are set per tenant plan. Contact your health center if you need this access.'));
        }
        $tenant = auth()->user()->tenant;
        $tenant?->loadMissing('domains');
        $services = Service::orderBy('sort_order')->orderBy('name')->get();

        return view('frontend.resident.book', compact('services', 'tenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->user()->can('book appointments')) {
            throw new AuthorizationException(__('Your health center admin has disabled booking for your account. Permissions are set per tenant plan. Contact your health center if you need this access.'));
        }
        $tenant = auth()->user()->tenant;

        if (! $tenant->canExceedAppointmentLimit()) {
            return back()->withInput()->withErrors([
                'scheduled_date' => 'Your health center has reached its monthly appointment limit. Please try again next month.',
            ]);
        }

        $validated = $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'complaint' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = $validated['scheduled_date'];
        $dailyLimit = config('bhcas.daily_appointment_limit');
        if ($dailyLimit !== null) {
            $countOnDay = $tenant->appointments()
                ->whereDate('scheduled_date', $date)
                ->count();
            if ($countOnDay >= $dailyLimit) {
                return back()->withInput()->withErrors([
                    'scheduled_date' => 'This date is fully booked. Please choose another date.',
                ]);
            }
        }

        $validated['tenant_id'] = $tenant->id;
        $validated['user_id'] = auth()->id();
        $validated['status'] = Appointment::STATUS_PENDING;

        Appointment::create($validated);

        return redirect()->route('resident.dashboard')->with('success', 'Appointment requested. You will be notified once approved.');
    }
}
