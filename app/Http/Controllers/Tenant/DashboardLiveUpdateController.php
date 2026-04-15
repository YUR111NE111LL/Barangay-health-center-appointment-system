<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardLiveUpdateController extends Controller
{
    /**
     * JSON snapshot for the main backend dashboard stat cards (auto-refresh).
     */
    public function summary(): JsonResponse
    {
        if (! Schema::hasTable('appointments')) {
            return response()->json([
                'todayCount' => 0,
                'pendingCount' => 0,
                'approvedToday' => 0,
                'generated_at' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'todayCount' => Appointment::today()->count(),
            'pendingCount' => Appointment::pending()->count(),
            'approvedToday' => Appointment::today()->approved()->count(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * JSON snapshot for the Health Center Admin dashboard table (auto-refresh).
     */
    public function admin(): JsonResponse
    {
        if (! Schema::hasTable('appointments')) {
            return response()->json([
                'pendingCount' => 0,
                'appointments' => [],
                'generated_at' => now()->toIso8601String(),
            ]);
        }

        $todayAppointments = Appointment::query()
            ->with(['resident', 'service'])
            ->today()
            ->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'pendingCount' => Appointment::pending()->count(),
            'appointments' => $todayAppointments->map(fn (Appointment $apt) => $this->serializeAdminRow($apt))->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * JSON snapshot for the Nurse dashboard table (auto-refresh).
     */
    public function nurse(): JsonResponse
    {
        if (! Schema::hasTable('appointments')) {
            return response()->json([
                'appointments' => [],
                'generated_at' => now()->toIso8601String(),
            ]);
        }

        $todayAppointments = Appointment::query()
            ->with(['resident', 'service'])
            ->today()
            ->approved()
            ->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'appointments' => $todayAppointments->map(fn (Appointment $apt) => $this->serializeNurseRow($apt))->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * JSON snapshot for the Staff dashboard table (auto-refresh).
     */
    public function staff(): JsonResponse
    {
        if (! Schema::hasTable('appointments')) {
            return response()->json([
                'appointments' => [],
                'generated_at' => now()->toIso8601String(),
            ]);
        }

        $todayAppointments = Appointment::query()
            ->with(['resident', 'service'])
            ->today()
            ->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'appointments' => $todayAppointments->map(fn (Appointment $apt) => $this->serializeStaffRow($apt))->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAdminRow(Appointment $apt): array
    {
        $time = Carbon::parse($apt->scheduled_time)->format('g:i A');
        $isApproved = $apt->status === Appointment::STATUS_APPROVED;

        return [
            'id' => $apt->id,
            'scheduled_time_display' => $time,
            'patient_name' => $apt->resident->name,
            'service_name' => $apt->service->name,
            'status' => $apt->status,
            'is_approved' => $isApproved,
            'show_url' => route('backend.appointments.show', $apt),
            'approve_url' => $isApproved ? null : route('backend.appointments.approve', $apt),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNurseRow(Appointment $apt): array
    {
        $time = Carbon::parse($apt->scheduled_time)->format('g:i A');

        return [
            'id' => $apt->id,
            'scheduled_time_display' => $time,
            'patient_name' => $apt->resident->name,
            'service_name' => $apt->service->name,
            'complaint_excerpt' => Str::limit((string) $apt->complaint, 40),
            'show_url' => route('backend.appointments.show', $apt),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStaffRow(Appointment $apt): array
    {
        $time = Carbon::parse($apt->scheduled_time)->format('g:i A');
        $isApproved = $apt->status === Appointment::STATUS_APPROVED;

        return [
            'id' => $apt->id,
            'scheduled_time_display' => $time,
            'patient_name' => $apt->resident->name,
            'service_name' => $apt->service->name,
            'status' => $apt->status,
            'is_approved' => $isApproved,
            'show_url' => route('backend.appointments.show', $apt),
        ];
    }
}
