<?php

namespace App\Http\Controllers\Admin;

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
        ]);

        Setting::set('media_type', $data['media_type']);
        Setting::set('youtube_url', $data['youtube_url'] ?? '');
        Setting::set('local_video_url', $data['local_video_url'] ?? '');
        Setting::set('slideshow_urls', $data['slideshow_urls'] ?? [], 'json');
        Setting::set('news_ticker', $data['news_ticker'] ?? '');
        Setting::set('skip_handling', $data['skip_handling']);
        Setting::set('monitor_header', $data['monitor_header'] ?? 'Pusat Antrian');
        Setting::set('monitor_subheader', $data['monitor_subheader'] ?? 'Lobby Utama');

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
