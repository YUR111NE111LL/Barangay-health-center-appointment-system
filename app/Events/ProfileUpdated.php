<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfileUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function broadcastOn(): array
    {
        // Tenant users are scoped by `tenant_id`. If it is missing, broadcast to a neutral channel.
        $tenantId = $this->user->tenant_id;
        $channelName = $tenantId ? ('tenant.'.$tenantId) : 'central.super-admin';

        return [
            new Channel($channelName),
        ];
    }

    public function broadcastAs(): string
    {
        return 'profile.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id,
            'message' => 'Profile updated. Refresh your view.',
        ];
    }
}
