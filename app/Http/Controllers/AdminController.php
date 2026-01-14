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
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        
        $stats = [
            'total' => Queue::whereDate('created_at', $date)->count(),
            'waiting' => Queue::where('status', 'waiting')->count(),
            'served' => Queue::whereDate('created_at', $date)->where('status', 'served')->count(),
            'skipped' => Queue::whereDate('created_at', $date)->where('status', 'skipped')->count(),
        ];
        $services = Service::withCount(['queues as waiting_count' => function($query) {
            $query->where('status', 'waiting');
        }])->get();
        $floors = Floor::all();
        
        // Get currently serving queues
        $activeServing = Queue::whereIn('status', ['called', 'serving'])
            ->with(['counter', 'service', 'floor'])
            ->orderByDesc('called_at')
            ->take(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'services' => $services,
            'floors' => $floors,
            'activeServing' => $activeServing,
            'selectedDate' => $date,
        ]);
    }

    public function counterStatus()
    {
        $counters = Counter::with(['floor', 'activeQueue.service'])->get();
        $floors = Floor::all();
        // We reuse stats if needed, or just focus on counters
        $stats = [
            'total' => Queue::whereDate('created_at', now()->toDateString())->count(),
            'waiting' => Queue::where('status', 'waiting')->count(),
            'served' => Queue::whereDate('created_at', now()->toDateString())->where('status', 'served')->count(),
        ];
        $services = Service::all();

        return Inertia::render('Admin/Counters/Status', [
            'counters' => $counters,
            'floors' => $floors,
            'stats' => $stats,
            'services' => $services
        ]);
    }

    /**
     * Reset harian - Reset nomor urut dan hapus antrian pending, simpan histori
     */
    public function resetDaily()
    {
        // Reset last_number in services
        Service::query()->update(['last_number' => 0]);
        
        // Only delete pending queues (waiting, called, serving)
        Queue::whereIn('status', ['waiting', 'called', 'serving'])->delete();
        
        broadcast(new \App\Events\QueueReset());

        return redirect()->back()->with('success', 'Reset harian berhasil. Nomor urut direset, histori tetap tersimpan.');
    }

    /**
     * Reset total - Hapus semua data antrian (gunakan dengan hati-hati)
     */
    public function resetAll()
    {
        // Reset last_number in services and truncate queues
        Service::query()->update(['last_number' => 0]);
        Queue::truncate();
        
        broadcast(new \App\Events\QueueReset());

        return redirect()->back()->with('success', 'Semua data antrian telah dihapus dan sistem direset.');
    }
}
