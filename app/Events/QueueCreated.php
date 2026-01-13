<?php

namespace App\Events;

use App\Models\Queue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('monitor.floor.' . $this->queue->floor_id),
            new Channel('service.' . $this->queue->service_id),
        ];
    }
}
