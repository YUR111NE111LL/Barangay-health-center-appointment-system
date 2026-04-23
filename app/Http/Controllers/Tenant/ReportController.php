<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view reports');
        $tenant = $request->user()->tenant;
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $reportData = $this->buildReportData($from, $to);

        return view('tenant.reports.index', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'appointments' => $reportData['appointments'],
            'byStatus' => $reportData['byStatus'],
            'byServiceDisplay' => $reportData['byServiceDisplay'],
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('view reports');

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $tenant = $request->user()->tenant;
        $from = (string) $validated['from'];
        $to = (string) $validated['to'];
        $reportData = $this->buildReportData($from, $to);

        $pdf = Pdf::loadView('tenant.reports.export-pdf', [
            'tenant' => $tenant,
            'from' => $from,
            'to' => $to,
            'appointments' => $reportData['appointments'],
            'byStatus' => $reportData['byStatus'],
            'byServiceDisplay' => $reportData['byServiceDisplay'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeTenantName = preg_replace('/[^a-z0-9]+/i', '-', (string) $tenant->name) ?: 'tenant';
        $filename = 'report-'.$safeTenantName.'-'.$from.'-to-'.$to.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{
     *   appointments: \Illuminate\Support\Collection<int, \App\Models\Appointment>,
     *   byStatus: \Illuminate\Support\Collection<string, int>,
     *   byServiceDisplay: \Illuminate\Support\Collection<int, array{name: string, count: int}>
     * }
     */
    private function buildReportData(string $from, string $to): array
    {
        $appointments = Appointment::with(['resident', 'service'])
            ->whereBetween('scheduled_date', [$from, $to])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $byStatus = $appointments->groupBy('status')->map->count();
        $byServiceDisplay = $appointments->groupBy('service_id')->map(function ($serviceAppointments, $serviceId): array {
            $apt = $serviceAppointments->first();
            $serviceName = $apt?->service?->name ?? 'Service #'.$serviceId;

            return ['name' => $serviceName, 'count' => $serviceAppointments->count()];
        })->values();

        return [
            'appointments' => $appointments,
            'byStatus' => $byStatus,
            'byServiceDisplay' => $byServiceDisplay,
        ];
    }
}
