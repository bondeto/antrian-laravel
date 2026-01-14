<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Events\QueueCalled;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatorApiController extends Controller
{
    public function status(Request $request, $counterId)
    {
        $counter = Counter::with(['floor', 'activeQueue.service'])->findOrFail($counterId);
        
        // Get current queue for this counter
        $currentQueue = Queue::where('counter_id', $counterId)
            ->whereIn('status', ['called', 'serving'])
            ->with('service')
            ->latest('updated_at')
            ->first();

        $stats = [
            'waiting_count' => Queue::where('status', 'waiting')->count(),
            'current_queue' => $currentQueue ? $currentQueue->toArray() : null,
            'enable_photo_capture' => \App\Models\Setting::get('enable_photo_capture', false),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'counter' => $counter,
                'stats' => $stats,
            ]
        ]);
    }

    public function callNext(Request $request, $counterId)
    {
        $counter = Counter::findOrFail($counterId);
        
        // Find next queue
        $nextQueue = Queue::where('status', 'waiting')
            ->where('floor_id', $counter->floor_id)
            ->orderBy('created_at')
            ->first();

        if (!$nextQueue) {
            return response()->json(['success' => false, 'message' => 'Tidak ada antrian menunggu.']);
        }

        DB::transaction(function () use ($nextQueue, $counter) {
            // Update old active queue if any
            Queue::where('counter_id', $counter->id)
                ->whereIn('status', ['called', 'serving'])
                ->update(['status' => 'served']);

            $nextQueue->update([
                'counter_id' => $counter->id,
                'status' => 'called',
                'called_at' => now(),
            ]);
        });

        broadcast(new QueueCalled($nextQueue->load(['counter', 'floor', 'service'])))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $nextQueue->toArray()
        ]);
    }

    public function recall(Request $request, $queueId)
    {
        $queue = Queue::findOrFail($queueId);
        $queue->touch(); // Update updated_at
        
        broadcast(new QueueCalled($queue->load(['counter', 'floor', 'service'])))->toOthers();

        return response()->json(['success' => true]);
    }

    public function served(Request $request, $queueId)
    {
        $queue = Queue::findOrFail($queueId);
        $queue->update(['status' => 'served', 'served_at' => now()]);
        
        // Refresh the model to get updated status
        $queue->refresh();
        
        \Log::info('Broadcasting QueueUpdated for served queue', [
            'queue_id' => $queue->id,
            'status' => $queue->status,
            'floor_id' => $queue->floor_id
        ]);
        
        broadcast(new QueueUpdated($queue->load(['counter', 'floor', 'service'])));

        return response()->json(['success' => true]);
    }

    public function skip(Request $request, $queueId)
    {
        $queue = Queue::findOrFail($queueId);
        $handler = \App\Models\Setting::get('skip_handling', 'hangus');

        if ($handler === 'hangus') {
            $queue->update(['status' => 'skipped']);
        } else {
            $data = [
                'status' => 'waiting',
                'counter_id' => null,
                'called_at' => null,
            ];

            if ($handler === 'belakang') {
                $data['created_at'] = now();
            }

            $queue->update($data);
        }

        broadcast(new QueueUpdated($queue->load(['counter', 'floor', 'service'])));

        return response()->json(['success' => true, 'handler' => $handler]);
    }
}
