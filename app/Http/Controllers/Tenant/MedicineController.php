<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Medicine;
use App\Models\MedicineAcquisition;
use App\Services\CloudinaryService;
use App\Support\MedicineAcquisitionNavBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage medicine');
        MedicineAcquisitionNavBadge::acknowledgeFor(auth()->user());
        $this->ensureInventoryPlan();

        $medicines = Medicine::query()->orderBy('name')->paginate(15);

        $recentAcquisitions = collect();
        if (Schema::hasTable('medicine_acquisitions')) {
            $recentAcquisitions = MedicineAcquisition::query()
                ->with(['user', 'medicine'])
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
        } elseif (Schema::hasTable('audit_logs')) {
            $recentAcquisitions = AuditLog::query()
                ->with('user')
                ->where('event', 'acquired')
                ->where('auditable_type', Medicine::class)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
        }

        return view('tenant.medicines.index', compact('medicines', 'recentAcquisitions'));
    }

    public function create(): View
    {
        $this->authorize('manage medicine');
        $this->ensureInventoryPlan();

        return view('tenant.medicines.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage medicine');
        $this->ensureInventoryPlan();

        $validated = $this->validatedMedicineFields($request);
        $request->validate([
            'image' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)],
        ]);

        $tenant = auth()->user()->tenant;
        $validated['tenant_id'] = $tenant->id;

        if ($request->hasFile('image')) {
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('image'),
                "medicines/{$tenant->id}",
                [
                    'transformation' => [
                        'width' => 800,
                        'height' => 800,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'format' => 'auto',
                    ],
                ]
            );
            if ($uploadResult) {
                $validated['image_path'] = $uploadResult['secure_url'];
            }
        }

        Medicine::create($validated);

        return redirect()->route('backend.medicines.index')->with('success', 'Medicine added.');
    }

    public function edit(Medicine $medicine): View
    {
        $this->authorize('manage medicine');
        $this->ensureInventoryPlan();
        $this->ensureSameTenant($medicine);

        return view('tenant.medicines.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->authorize('manage medicine');
        $this->ensureInventoryPlan();
        $this->ensureSameTenant($medicine);

        $validated = $this->validatedMedicineFields($request);
        $request->validate([
            'image' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'gif', 'webp'])->max(2048)],
        ]);

        if ($request->hasFile('image')) {
            if ($medicine->image_path) {
                if (str_contains($medicine->image_path, 'cloudinary.com')) {
                    $publicId = basename(parse_url($medicine->image_path, PHP_URL_PATH), '.'.pathinfo($medicine->image_path, PATHINFO_EXTENSION));
                    CloudinaryService::delete($publicId, 'image');
                } else {
                    Storage::disk('public')->delete($medicine->image_path);
                }
            }
            $uploadResult = CloudinaryService::uploadImage(
                $request->file('image'),
                "medicines/{$medicine->tenant_id}",
                [
                    'transformation' => [
                        'width' => 800,
                        'height' => 800,
                        'crop' => 'limit',
                        'quality' => 'auto',
                        'format' => 'auto',
                    ],
                ]
            );
            if ($uploadResult) {
                $validated['image_path'] = $uploadResult['secure_url'];
            }
        }

        $medicine->update($validated);

        return redirect()->route('backend.medicines.index')->with('success', 'Medicine updated.');
    }

    public function destroy(Medicine $medicine): RedirectResponse
    {
        $this->authorize('manage medicine');
        $this->ensureInventoryPlan();
        $this->ensureSameTenant($medicine);

        if ($medicine->image_path) {
            if (str_contains($medicine->image_path, 'cloudinary.com')) {
                $publicId = basename(parse_url($medicine->image_path, PHP_URL_PATH), '.'.pathinfo($medicine->image_path, PATHINFO_EXTENSION));
                CloudinaryService::delete($publicId, 'image');
            } else {
                Storage::disk('public')->delete($medicine->image_path);
            }
        }
        $medicine->delete();

        return redirect()->route('backend.medicines.index')->with('success', 'Medicine removed.');
    }

    private function ensureInventoryPlan(): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant || ! $tenant->hasFeature('inventory')) {
            abort(403, 'Medicine management requires inventory tracking on your plan.');
        }
    }

    private function ensureSameTenant(Medicine $medicine): void
    {
        if ((int) $medicine->tenant_id !== (int) auth()->user()->tenant_id) {
            abort(403);
        }
    }

    /**
     * @return array{name: string, description: ?string, quantity: int, is_free: bool, price_per_unit: ?float}
     */
    private function validatedMedicineFields(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_free' => ['required', 'in:0,1'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $isFree = (bool) (int) $validated['is_free'];
        if (! $isFree && (empty($validated['price_per_unit']) || (float) $validated['price_per_unit'] <= 0)) {
            throw ValidationException::withMessages([
                'price_per_unit' => __('Enter a valid price per unit, or choose “Free — no charge to residents”.'),
            ]);
        }

        $validated['is_free'] = $isFree;
        $validated['price_per_unit'] = $isFree ? null : round((float) $validated['price_per_unit'], 2);

        return $validated;
    }
}
