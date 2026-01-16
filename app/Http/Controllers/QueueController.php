<?php

namespace App\Http\Controllers;

use App\Events\QueueCreated;
use App\Models\Floor;
use App\Models\Queue;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QueueController extends Controller
{
    public function kiosk(Request $request)
    {
        // Ideally pass floor_id via query or route param to show specific floor services
        // But for FULL CODE, let's assume we show all floors or have a selector.
        $floors = Floor::with('services')->get();
        
        return Inertia::render('Kiosk/Index', [
            'floors' => $floors,
            'ticketSettings' => [
                'ticket_mode' => Setting::get('ticket_mode', 'print'),
                'enable_photo_capture' => Setting::get('enable_photo_capture', false),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $ticketMode = Setting::get('ticket_mode', 'print');
        $enablePhotoCapture = Setting::get('enable_photo_capture', false);

        $rules = [
            'service_id' => 'required|exists:services,id',
        ];

        // Validate photo if photo capture is enabled
        if ($enablePhotoCapture) {
            $rules['photo'] = 'nullable|string'; // Base64 encoded image
        }

        $request->validate($rules);

        $queue = DB::transaction(function () use ($request, $ticketMode, $enablePhotoCapture) {
            // Lock the service row to ensure sequential numbering
            $service = Service::lockForUpdate()->find($request->service_id);
            
            $nextNumber = $service->last_number + 1;
            $service->update(['last_number' => $nextNumber]);

            $fullNumber = $service->code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Generate barcode token for paperless mode
            $barcodeToken = null;
            if ($ticketMode === 'paperless') {
                $barcodeToken = Str::random(32);
            }

            // Handle photo upload
            $photoPath = null;
            if ($enablePhotoCapture && $request->photo) {
                $photoPath = $this->storePhoto($request->photo, $fullNumber);
            }

            $queue = Queue::create([
                'service_id' => $service->id,
                'floor_id' => $service->floor_id,
                'number' => $nextNumber,
                'full_number' => $fullNumber,
                'status' => 'waiting',
                'photo_path' => $photoPath,
                'barcode_token' => $barcodeToken,
            ]);

            return $queue;
        });

        broadcast(new QueueCreated($queue));

        // Return different response based on ticket mode
        if ($ticketMode === 'paperless') {
            return redirect()->back()->with([
                'success' => 'Nomor Antrian: ' . $queue->full_number,
                'ticket' => [
                    'full_number' => $queue->full_number,
                    'barcode_token' => $queue->barcode_token,
                    'service_name' => $queue->service->name ?? '',
                    'created_at' => $queue->created_at->format('H:i'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Nomor Antrian: ' . $queue->full_number);
    }

    /**
     * Store photo with yyyy/mm/dd folder structure for easy maintenance
     */
    private function storePhoto(string $base64Image, string $queueNumber): ?string
    {
        try {
            // Extract base64 data (handle data URI format)
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                $extension = $matches[1];
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            } else {
                $extension = 'jpg';
            }

            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                return null;
            }

            // Create folder structure: queue-photos/yyyy/mm/dd
            $folderPath = 'queue-photos/' . now()->format('Y/m/d');
            
            // Generate unique filename
            $filename = $queueNumber . '_' . Str::random(8) . '.' . $extension;
            $fullPath = $folderPath . '/' . $filename;

            // Store the file
            Storage::disk('public')->put($fullPath, $imageData);

            return $fullPath;
        } catch (\Exception $e) {
            \Log::error('Failed to store queue photo: ' . $e->getMessage());
            return null;
        }
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
                'monitor_header' => \App\Models\Setting::get('monitor_header', 'Pusat Antrian'),
                'monitor_subheader' => \App\Models\Setting::get('monitor_subheader', 'Lobby Utama'),
                'logo_url' => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
            ]
        ]);
    }

    public function lobby()
    {
        $floors = Floor::all();
        $serving = Queue::whereIn('status', ['called', 'serving'])
            ->with(['counter', 'service', 'floor'])
            ->orderByDesc('called_at')
            ->take(5)
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
                'monitor_header' => \App\Models\Setting::get('monitor_header', 'Pusat Antrian'),
                'monitor_subheader' => \App\Models\Setting::get('monitor_subheader', 'Lobby Utama'),
                'logo_url' => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
            ]
        ]);
    }
}
