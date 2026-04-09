<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage inventory');
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            abort(403, 'Your plan does not include inventory tracking.');
        }

        return view('tenant.inventory.index');
    }
}
