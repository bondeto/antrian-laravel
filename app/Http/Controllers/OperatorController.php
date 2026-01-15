<?php

namespace App\Http\Controllers;

use App\Events\QueueCalled;
use App\Events\QueueUpdated;
use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperatorController extends Controller
{
    public function index()
    {
        $floors = \App\Models\Floor::with(['counters' => function($q) {
            $q->with('activeQueue.service');
        }])->orderBy('level')->get()->map(function ($floor) {
            // Count waiting queues specifically for this floor
            $floor->waiting_count = Queue::where('floor_id', $floor->id)
                ->where('status', 'waiting')
                ->count();

            // Ensure counters is a sequential array for JS
            $floor->setRelation('counters', $floor->counters->values());
            return $floor;
        });

        return Inertia::render('Operator/Dashboard', [
            'floors' => $floors
        ]);
    }

    public function work(Counter $counter)
    {
        // This is the active workspace for the operator
        return Inertia::render('Operator/Work', [
            'counter' => $counter->load('floor'),
            // Get current serving queue if any
            'currentQueue' => Queue::where('counter_id', $counter->id)
                ->whereIn('status', ['called', 'serving'])
                ->with('service')
                ->first(),
            'stats' => [
                'waiting' => Queue::where('floor_id', $counter->floor_id)->where('status', 'waiting')->count(),
                'served' => Queue::where('counter_id', $counter->id)->where('status', 'served')->count(),
            ]
        ]);
    }

    public function callNext(Request $request, Counter $counter)
    {
        // Get old active queues to auto-serve
        $oldQueues = Queue::where('counter_id', $counter->id)
            ->whereIn('status', ['called', 'serving'])
            ->get();

        $queue = DB::transaction(function () use ($counter, $oldQueues) {
            // Auto-serve old queue(s)
            foreach ($oldQueues as $oldQueue) {
                $oldQueue->update([
                    'status' => 'served',
                    'served_at' => now()
                ]);
            }

            // Find oldest waiting queue for this floor 
            $next = Queue::where('floor_id', $counter->floor_id)
                ->where('status', 'waiting')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->first();

            if ($next) {
                $next->update([
                    'status' => 'called',
                    'counter_id' => $counter->id,
                    'called_at' => now(),
                ]);
            }
            
            return $next;
        });

        // Broadcast status update for old queues so monitors remove them
        foreach ($oldQueues as $oldQueue) {
            broadcast(new QueueUpdated($oldQueue->fresh()->load(['counter', 'floor', 'service'])));
        }

        if ($queue) {
            $queue->load(['floor', 'counter', 'service']);
            broadcast(new QueueCalled($queue));
            return redirect()->back()->with('success', 'Calling ' . $queue->full_number);
        }

        return redirect()->back()->with('info', 'Tidak ada antrian menunggu.');
    }

    public function served(Request $request, Queue $queue)
    {
        $queue->update([
            'status' => 'served',
            'served_at' => now(),
        ]);
        
        broadcast(new \App\Events\QueueUpdated($queue->load(['floor', 'counter', 'service'])));
        
        return redirect()->back();
    }
    
    public function recall(Request $request, Queue $queue)
    {
        // Re-broadcast
        $queue->touch(); // Updated at
        $queue->load(['floor', 'counter', 'service']);
        broadcast(new QueueCalled($queue));
        return redirect()->back();
    }

    public function skip(Request $request, Queue $queue)
    {
        $handler = \App\Models\Setting::get('skip_handling', 'hangus');

        if ($handler === 'hangus') {
            $queue->update(['status' => 'skipped']);
        } else {
            // Re-queue logic
            $data = [
                'status' => 'waiting',
                'counter_id' => null,
                'called_at' => null,
            ];

            if ($handler === 'belakang') {
                $data['created_at'] = now();
            } elseif ($handler === 'pindah_1') {
                // Find the next person in line
                $next = Queue::where('floor_id', $queue->floor_id)
                    ->where('status', 'waiting')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                
                if ($next) {
                    // Set created_at to be slightly after the next person
                    $data['created_at'] = $next->created_at->addSecond();
                } else {
                    $data['created_at'] = now();
                }
            } elseif ($handler === 'pindah_2') {
                // Find the second person in line
                $waiters = Queue::where('floor_id', $queue->floor_id)
                    ->where('status', 'waiting')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->limit(2)
                    ->get();
                
                if ($waiters->count() >= 2) {
                    $target = $waiters[1]; // The second person
                    $data['created_at'] = $target->created_at->addSecond();
                } elseif ($waiters->count() === 1) {
                    $data['created_at'] = $waiters[0]->created_at->addSecond();
                } else {
                    $data['created_at'] = now();
                }
            }

            $queue->update($data);
        }

        broadcast(new \App\Events\QueueUpdated($queue->load(['floor', 'counter', 'service'])));
        return redirect()->back()->with('success', 'Tiket ' . $queue->full_number . ' telah di-' . ($handler === 'hangus' ? 'skip' : 'antrekan kembali') . '.');
    }
}
