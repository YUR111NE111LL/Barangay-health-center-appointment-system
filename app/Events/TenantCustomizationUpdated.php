<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when Barangay Admin saves web customization. Other sessions can listen and refresh to show the new look in real time.
 */
class TenantCustomizationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.'.$this->tenant->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'customization.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'message' => 'Web customization updated. Refresh to see changes.',
        ];
    }
}
