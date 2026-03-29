<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantApplicationRequest;
use App\Models\Plan;
use App\Models\TenantApplication;
use App\Support\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantApplicationController extends Controller
{
    public function create(): View
    {
        $plans = Plan::query()->orderBy('name')->get();

        return view('tenant-applications.create', compact('plans'));
    }

    public function store(StoreTenantApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (Recaptcha::shouldProcess()) {
            $result = Recaptcha::verifyV3($request, $validated['recaptcha_token'], 'tenant_application');
            if (! $result['ok']) {
                return back()
                    ->withInput($request->except('recaptcha_token'))
                    ->withErrors(['recaptcha_token' => __('reCAPTCHA verification failed. Please try again.')]);
            }
        }

        unset($validated['recaptcha_token']);

        $validated['status'] = TenantApplication::STATUS_PENDING;

        TenantApplication::query()->create($validated);

        return redirect()
            ->route('tenant-applications.create')
            ->with('status', __('Thank you. Your barangay application has been submitted. A Super Admin must approve it before your site is created.'));
    }
}
