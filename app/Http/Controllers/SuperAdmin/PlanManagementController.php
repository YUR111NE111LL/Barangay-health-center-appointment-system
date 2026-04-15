<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdatePlanPriceRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanManagementController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->orderByRaw("CASE slug WHEN 'basic' THEN 1 WHEN 'standard' THEN 2 WHEN 'premium' THEN 3 ELSE 99 END")
            ->orderBy('name')
            ->get();

        return view('superadmin.plans.index', compact('plans'));
    }

    public function update(UpdatePlanPriceRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update([
            'price' => $request->validated()['price'],
        ]);

        return redirect()
            ->route('super-admin.plans.index')
            ->with('success', __(':plan plan price updated successfully.', ['plan' => $plan->name]));
    }
}
