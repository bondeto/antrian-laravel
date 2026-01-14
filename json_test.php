<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$counterId = 1;
$counter = App\Models\Counter::with(['floor', 'activeQueue.service'])->find($counterId);

if (!$counter) {
    die(json_encode(['error' => 'Counter not found']));
}

$stats = [
    'waiting_count' => App\Models\Queue::where('status', 'waiting')->count(),
    'current_queue' => $counter->activeQueue ? $counter->activeQueue->load('service') : null,
    'enable_photo_capture' => \App\Models\Setting::get('enable_photo_capture', false),
];

$response = [
    'success' => true,
    'data' => [
        'counter' => $counter,
        'stats' => $stats,
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
