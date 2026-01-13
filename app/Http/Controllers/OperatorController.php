<?php

namespace App\Http\Controllers;

use App\Events\QueueCalled;
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
        $floors = \App\Models\Floor::with('counters')->orderBy('level')->get()->map(function ($floor) {
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
        // Logic to find next queue
        // A counter might serve specific services (complex) or all floor services (simple).
        // Let's assume counter serves all services on the floor for now, or based on simple matching
        
        $queue = DB::transaction(function () use ($counter) {
            // Find oldest waiting queue for this floor 
            // Better: prioritize by service if needed.
            $next = Queue::where('floor_id', $counter->floor_id)
                ->where('status', 'waiting')
                ->orderBy('id', 'asc') // FIFO
                ->lockForUpdate()
                ->first();

            if ($next) {
                // Determine if we should set previous active queue to served?
                // Ideally operator clicks "Served" explicitly. 
                // But if they click "Call Next" while one is active, we might auto-close or error.
                // Let's assume they must finish previous one first.
                
                $next->update([
                    'status' => 'called',
                    'counter_id' => $counter->id,
                    'called_at' => now(),
                ]);
            }
            
            return $next;
        });

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
        $queue->update(['status' => 'skipped']);
        broadcast(new \App\Events\QueueUpdated($queue->load(['floor', 'counter', 'service'])));
        return redirect()->back();
    }
}
