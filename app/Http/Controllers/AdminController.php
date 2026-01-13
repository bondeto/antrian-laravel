<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total' => Queue::count(),
                'waiting' => Queue::where('status', 'waiting')->count(),
                'served' => Queue::where('status', 'served')->count(),
            ],
            'services' => Service::withCount(['queues as waiting_count' => function($query) {
                $query->where('status', 'waiting');
            }])->get(),
            'floors' => Floor::all()
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
