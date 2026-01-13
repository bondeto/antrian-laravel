<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Floor;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Queue::count(),
            'waiting' => Queue::where('status', 'waiting')->count(),
            'served' => Queue::where('status', 'served')->count(),
        ];
        $services = Service::withCount(['queues as waiting_count' => function($query) {
            $query->where('status', 'waiting');
        }])->get();
        $floors = Floor::all();
        $counters = Counter::with(['floor', 'activeQueue.service'])->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'services' => $services,
            'floors' => $floors,
            'counters' => $counters
        ]);
    }

    public function reset()
    {
        // Reset last_number in services and truncate queues
        Service::query()->update(['last_number' => 0]);
        Queue::truncate();

        return redirect()->back()->with('success', 'Sistem antrian telah di-reset.');
    }
}
