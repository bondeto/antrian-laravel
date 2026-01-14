<?php

namespace App\Http\Controllers\Admin;

use App\Events\SettingsUpdated;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'media_type' => Setting::get('media_type', 'youtube'), // youtube, local_video, slideshow
                'youtube_url' => Setting::get('youtube_url', ''),
                'slideshow_urls' => Setting::get('slideshow_urls', []), // array of urls
                'local_video_url' => Setting::get('local_video_url', ''),
                'news_ticker' => Setting::get('news_ticker', ''),
                'skip_handling' => Setting::get('skip_handling', 'hangus'),
                'monitor_header' => Setting::get('monitor_header', 'Pusat Antrian'),
                'monitor_subheader' => Setting::get('monitor_subheader', 'Lobby Utama'),
                // Ticket features
                'ticket_mode' => Setting::get('ticket_mode', 'print'), // print, paperless
                'enable_photo_capture' => Setting::get('enable_photo_capture', false),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'media_type' => 'required|in:youtube,local_video,slideshow',
            'youtube_url' => 'nullable|string',
            'local_video_url' => 'nullable|string',
            'slideshow_urls' => 'nullable|array',
            'news_ticker' => 'nullable|string',
            'skip_handling' => 'required|in:hangus,belakang,pindah_1,pindah_2',
            'monitor_header' => 'nullable|string|max:100',
            'monitor_subheader' => 'nullable|string|max:100',
            // Ticket features
            'ticket_mode' => 'required|in:print,paperless',
            'enable_photo_capture' => 'boolean',
        ]);

        Setting::set('media_type', $data['media_type']);
        Setting::set('youtube_url', $data['youtube_url'] ?? '');
        Setting::set('local_video_url', $data['local_video_url'] ?? '');
        Setting::set('slideshow_urls', $data['slideshow_urls'] ?? [], 'json');
        Setting::set('news_ticker', $data['news_ticker'] ?? '');
        Setting::set('skip_handling', $data['skip_handling']);
        Setting::set('monitor_header', $data['monitor_header'] ?? 'Pusat Antrian');
        Setting::set('monitor_subheader', $data['monitor_subheader'] ?? 'Lobby Utama');
        
        // Save ticket features
        Setting::set('ticket_mode', $data['ticket_mode']);
        Setting::set('enable_photo_capture', $data['enable_photo_capture'] ? '1' : '0', 'boolean');

        // Broadcast settings update to all monitors
        broadcast(new SettingsUpdated([
            'media_type' => $data['media_type'],
            'youtube_url' => $data['youtube_url'] ?? '',
            'local_video_url' => $data['local_video_url'] ?? '',
            'slideshow_urls' => $data['slideshow_urls'] ?? [],
            'news_ticker' => $data['news_ticker'] ?? '',
            'monitor_header' => $data['monitor_header'] ?? 'Pusat Antrian',
            'monitor_subheader' => $data['monitor_subheader'] ?? 'Lobby Utama',
            'enable_photo_capture' => $data['enable_photo_capture'],
            'ticket_mode' => $data['ticket_mode'],
        ]));

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan. Monitor akan refresh otomatis.');
    }
}
