<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'ticket' => $request->session()->get('ticket'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'app_settings' => [
                'logo_url' => \App\Models\Setting::get('app_logo') ? asset('storage/' . \App\Models\Setting::get('app_logo')) : null,
                'header' => \App\Models\Setting::get('monitor_header', 'Pusat Antrian'),
                'subheader' => \App\Models\Setting::get('monitor_subheader', 'Lobby Utama'),
            ]
        ];
    }
}
