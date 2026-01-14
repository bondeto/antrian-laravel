<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Floor;
use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Daily summary (PostgreSQL compatible)
        $dailySummary = Queue::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served"),
                DB::raw("SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped"),
                DB::raw('AVG(EXTRACT(EPOCH FROM (served_at - called_at)) / 60) as avg_service_time')
            )
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('date')
            ->get();

        // Counter performance (PostgreSQL compatible)
        $counterPerformance = Queue::select(
                'counter_id',
                DB::raw('COUNT(*) as total_served'),
                DB::raw('COUNT(DISTINCT DATE(created_at)) as days_active'),
                DB::raw('AVG(EXTRACT(EPOCH FROM (served_at - called_at)) / 60) as avg_service_time')
            )
            ->whereNotNull('counter_id')
            ->whereIn('status', ['served', 'skipped'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('counter_id')
            ->get()
            ->map(function ($item) {
                $counter = Counter::with('floor')->find($item->counter_id);
                return [
                    'counter_id' => $item->counter_id,
                    'counter_name' => $counter->name ?? 'Unknown',
                    'floor_name' => $counter->floor->name ?? 'Unknown',
                    'total_served' => $item->total_served,
                    'days_active' => $item->days_active,
                    'avg_per_day' => $item->days_active > 0 ? round($item->total_served / $item->days_active, 1) : 0,
                    'avg_service_time' => round($item->avg_service_time ?? 0, 1),
                ];
            });

        // Service breakdown (PostgreSQL compatible)
        $serviceBreakdown = Queue::select(
                'service_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served"),
                DB::raw("SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped")
            )
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy('service_id')
            ->get()
            ->map(function ($item) {
                $service = Service::find($item->service_id);
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $service->name ?? 'Unknown',
                    'service_code' => $service->code ?? '-',
                    'total' => $item->total,
                    'served' => $item->served,
                    'skipped' => $item->skipped,
                ];
            });

        // Overall stats for period
        $periodStats = [
            'total_queues' => Queue::whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->count(),
            'total_served' => Queue::whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->where('status', 'served')->count(),
            'total_skipped' => Queue::whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->where('status', 'skipped')->count(),
            'avg_per_day' => $dailySummary->count() > 0 ? round($dailySummary->avg('total'), 1) : 0,
        ];

        return Inertia::render('Admin/Reports/Index', [
            'dailySummary' => $dailySummary,
            'counterPerformance' => $counterPerformance,
            'serviceBreakdown' => $serviceBreakdown,
            'periodStats' => $periodStats,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
