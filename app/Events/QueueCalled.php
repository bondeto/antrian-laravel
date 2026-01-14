<?php

namespace App\Events;

use App\Models\Queue;
use App\Models\Service;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;
    public $stats;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
        $this->stats = [
            'total' => Queue::whereDate('created_at', now()->toDateString())->count(),
            'waiting' => Queue::where('status', 'waiting')->count(),
            'served' => Queue::whereDate('created_at', now()->toDateString())->where('status', 'served')->count(),
            'services' => Service::all()->mapWithKeys(function ($service) {
                return [$service->id => Queue::where('service_id', $service->id)->where('status', 'waiting')->count()];
            })->toArray()
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('monitor.floor.' . $this->queue->floor_id),
        ];
    }
}
