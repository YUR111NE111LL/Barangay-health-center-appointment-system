<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when Super Admin updates RBAC for a tenant. Resident/staff pages can listen and refresh UI.
 */
class TenantRbacUpdated implements ShouldBroadcast
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
        return 'rbac.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'message' => 'Permissions updated. Refresh your view.',
        ];
    }
}
