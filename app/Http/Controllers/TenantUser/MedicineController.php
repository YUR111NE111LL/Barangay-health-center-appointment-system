<?php

namespace App\Http\Controllers\TenantUser;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineAcquisition;
use App\Support\TenantAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            abort(403, 'Medicine is not available for your barangay plan.');
        }

        $medicines = Medicine::query()->orderBy('name')->paginate(12);

        return view('tenant-user.medicines.index', compact('medicines'));
    }

    public function acquire(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->authorize('acquire medicine');
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            abort(403, 'Medicine is not available for your barangay plan.');
        }

        $this->ensureSameTenant($medicine);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $qty = (int) $validated['quantity'];

        $insufficient = false;
        $stockBefore = 0;
        DB::transaction(function () use ($medicine, $qty, &$insufficient, &$stockBefore): void {
            /** @var Medicine $locked */
            $locked = Medicine::query()->whereKey($medicine->id)->lockForUpdate()->firstOrFail();
            $stockBefore = (int) $locked->quantity;
            if ($locked->quantity < $qty) {
                $insufficient = true;

                return;
            }
            $locked->decrement('quantity', $qty);
        });

        if ($insufficient) {
            return back()->with('error', 'Not enough stock. This item may be out of stock.');
        }

        $medicine->refresh();

        $isFreeAcquire = ! $medicine->isPricedSupply();
        $unitPrice = $isFreeAcquire ? null : (float) $medicine->price_per_unit;
        $lineTotal = $isFreeAcquire ? 0.0 : round($qty * $unitPrice, 2);

        if (Schema::hasTable('medicine_acquisitions')) {
            MedicineAcquisition::create([
                'tenant_id' => (int) auth()->user()->tenant_id,
                'user_id' => (int) auth()->id(),
                'medicine_id' => $medicine->id,
                'quantity' => $qty,
                'unit_price_snapshot' => $unitPrice,
                'line_total' => $lineTotal,
                'is_free' => $isFreeAcquire,
            ]);
        }

        TenantAuditRecorder::record(
            'acquired',
            $medicine,
            ['quantity' => $stockBefore],
            [
                'name' => $medicine->name,
                'quantity_acquired' => $qty,
                'quantity_remaining' => (int) $medicine->quantity,
                'is_free' => $isFreeAcquire,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]
        );

        $thanks = 'You acquired '.$qty.' unit(s). Thank you.';
        if (! $isFreeAcquire) {
            $thanks .= ' Amount due: '.config('bhcas.currency_symbol', '₱').number_format($lineTotal, 2).' (set by your health center for this medicine).';
        }

        return back()->with('success', $thanks);
    }

    private function ensureSameTenant(Medicine $medicine): void
    {
        if ((int) $medicine->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
