<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\View\View;

class BackendDashboardController extends Controller
{
    /**
     * Show the backend dashboard (role-specific view chosen by route/controller).
     */
    public function index(): View
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $todayCount = Appointment::today()->count();
        $pendingCount = Appointment::pending()->count();
        $approvedToday = Appointment::today()->approved()->count();

        return view('backend.dashboard', [
            'tenant' => $tenant,
            'todayCount' => $todayCount,
            'pendingCount' => $pendingCount,
            'approvedToday' => $approvedToday,
        ]);
    }

    /**
     * Admin dashboard.
     */
    public function admin(): View
    {
        $tenant = auth()->user()->tenant;
        $todayAppointments = Appointment::with(['resident', 'service'])
            ->today()
            ->orderBy('scheduled_time')
            ->get();
        $pendingCount = Appointment::pending()->count();

        return view('backend.admin.dashboard', [
            'tenant' => $tenant,
            'todayAppointments' => $todayAppointments,
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * Nurse dashboard.
     */
    public function nurse(): View
    {
        $tenant = auth()->user()->tenant;
        $todayAppointments = Appointment::with(['resident', 'service'])
            ->today()
            ->approved()
            ->orderBy('scheduled_time')
            ->get();

        return view('backend.nurse.dashboard', [
            'tenant' => $tenant,
            'todayAppointments' => $todayAppointments,
        ]);
    }

    /**
     * Staff dashboard.
     */
    public function staff(): View
    {
        $tenant = auth()->user()->tenant;
        $todayAppointments = Appointment::with(['resident', 'service'])
            ->today()
            ->orderBy('scheduled_time')
            ->get();

        return view('backend.staff.dashboard', [
            'tenant' => $tenant,
            'todayAppointments' => $todayAppointments,
        ]);
    }
}
