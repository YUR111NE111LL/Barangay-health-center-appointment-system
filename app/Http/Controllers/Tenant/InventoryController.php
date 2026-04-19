<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MedicineAcquisition;
use App\Support\MedicineAcquisitionNavBadge;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage inventory');
        MedicineAcquisitionNavBadge::acknowledgeFor(auth()->user());
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            abort(403, 'Your plan does not include inventory tracking.');
        }

        $acquisitions = null;
        $totals = null;
        $acquisitionsTableMissing = false;

        if (Schema::hasTable('medicine_acquisitions')) {
            $acquisitions = MedicineAcquisition::query()
                ->with(['user:id,name,email,role', 'medicine:id,name'])
                ->orderByDesc('created_at')
                ->paginate(40);

            $totals = MedicineAcquisition::query()
                ->selectRaw('COUNT(*) as acquisition_count, COALESCE(SUM(quantity), 0) as units_total, COALESCE(SUM(line_total), 0) as amount_total')
                ->first();
        } else {
            $acquisitionsTableMissing = true;
        }

        return view('tenant.inventory.index', compact('acquisitions', 'totals', 'acquisitionsTableMissing'));
    }
}
