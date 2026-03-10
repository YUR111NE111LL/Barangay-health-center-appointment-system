<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view reports');
        $tenant = auth()->user()->tenant;
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $appointments = Appointment::with(['resident', 'service'])
            ->whereBetween('scheduled_date', [$from, $to])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $byStatus = $appointments->groupBy('status')->map->count();
        $byService = $appointments->groupBy('service_id')->map->count();
        $byServiceDisplay = $byService->map(function (int $count, $serviceId) use ($appointments) {
            $apt = $appointments->first(fn ($a) => (int) $a->service_id === (int) $serviceId);
            $name = $apt?->service?->name ?? 'Service #' . $serviceId;
            return ['name' => $name, 'count' => $count];
        })->values();

        return view('backend.reports.index', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'appointments' => $appointments,
            'byStatus' => $byStatus,
            'byServiceDisplay' => $byServiceDisplay,
        ]);
    }
}
