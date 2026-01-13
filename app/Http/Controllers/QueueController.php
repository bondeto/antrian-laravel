<?php

namespace App\Http\Controllers;

use App\Events\QueueCreated;
use App\Models\Floor;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QueueController extends Controller
{
    public function kiosk(Request $request)
    {
        // Ideally pass floor_id via query or route param to show specific floor services
        // But for FULL CODE, let's assume we show all floors or have a selector.
        $floors = Floor::with('services')->get();
        return Inertia::render('Kiosk/Index', [
            'floors' => $floors
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $queue = DB::transaction(function () use ($request) {
            // Lock the service row to ensure sequential numbering
            $service = Service::lockForUpdate()->find($request->service_id);
            
            $nextNumber = $service->last_number + 1;
            $service->update(['last_number' => $nextNumber]);

            $fullNumber = $service->code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $queue = Queue::create([
                'service_id' => $service->id,
                'floor_id' => $service->floor_id,
                'number' => $nextNumber,
                'full_number' => $fullNumber,
                'status' => 'waiting',
            ]);

            return $queue;
        });

        broadcast(new QueueCreated($queue));

        return redirect()->back()->with('success', 'Nomor Antrian: ' . $queue->full_number);
    }
    
    public function monitor($floor_id)
    {
        $floor = Floor::findOrFail($floor_id);
        
        // Initial state
        $serving = Queue::where('floor_id', $floor_id)
            ->whereIn('status', ['called', 'serving'])
            ->with(['counter', 'service', 'floor'])
            ->orderByDesc('called_at')
            ->take(5)
            ->get();
            
        $waiting = Queue::where('floor_id', $floor_id)
            ->where('status', 'waiting')
            ->groupBy('service_id')
            ->selectRaw('service_id, count(*) as count')
            ->with('service')
            ->get();

        return Inertia::render('Monitor/Show', [
            'floor' => $floor,
            'initialServing' => $serving,
            'initialWaiting' => $waiting,
            'mediaSettings' => [
                'type' => \App\Models\Setting::get('media_type', 'youtube'),
                'youtube_url' => \App\Models\Setting::get('youtube_url', ''),
                'local_video_url' => \App\Models\Setting::get('local_video_url', ''),
                'slideshow_urls' => \App\Models\Setting::get('slideshow_urls', []),
                'news_ticker' => \App\Models\Setting::get('news_ticker', 'Selamat Datang di Kantor Kami. Budayakan Antre untuk Kenyamanan Bersama.'),
            ]
        ]);
    }

    public function lobby()
    {
        $floors = Floor::all();
        $serving = Queue::whereIn('status', ['called', 'serving'])
            ->with(['counter', 'service', 'floor'])
            ->orderByDesc('called_at')
            ->take(6)
            ->get();

        return Inertia::render('Monitor/Lobby', [
            'floors' => $floors,
            'initialServing' => $serving,
            'mediaSettings' => [
                'type' => \App\Models\Setting::get('media_type', 'youtube'),
                'youtube_url' => \App\Models\Setting::get('youtube_url', ''),
                'local_video_url' => \App\Models\Setting::get('local_video_url', ''),
                'slideshow_urls' => \App\Models\Setting::get('slideshow_urls', []),
                'news_ticker' => \App\Models\Setting::get('news_ticker', 'Selamat datang di Layanan Kami. Budayakan antre untuk kenyamanan bersama.'),
            ]
        ]);
    }
}
