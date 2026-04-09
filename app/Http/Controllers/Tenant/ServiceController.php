<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('tenant.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('tenant.services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        $data['is_active'] = $request->boolean('is_active', true);
        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null) {
            $data['sort_order'] = (int) (Service::query()->max('sort_order') ?? 0) + 1;
        }

        Service::create($data);

        return redirect()->route('backend.services.index')->with('success', __('Service added. Residents can select it when booking.'));
    }

    public function edit(Service $service): View
    {
        return view('tenant.services.edit', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $service->update($data);

        return redirect()->route('backend.services.index')->with('success', __('Service updated.'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->appointments()->exists()) {
            return redirect()->route('backend.services.index')->with('error', __('This service cannot be deleted because it is linked to existing appointments. Turn it off instead.'));
        }

        $service->delete();

        return redirect()->route('backend.services.index')->with('success', __('Service removed.'));
    }
}
