<?php

namespace App\Observers;

use App\Support\TenantAuditRecorder;
use Illuminate\Database\Eloquent\Model;

class TenantAuditObserver
{
    public function created(Model $model): void
    {
        TenantAuditRecorder::record('created', $model, null, $this->attributesSnapshot($model));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        if ($changes === []) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        TenantAuditRecorder::record('updated', $model, $old, $changes);
    }

    public function deleted(Model $model): void
    {
        TenantAuditRecorder::record('deleted', $model, $this->attributesSnapshot($model), null);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesSnapshot(Model $model): array
    {
        return $model->getAttributes();
    }
}
