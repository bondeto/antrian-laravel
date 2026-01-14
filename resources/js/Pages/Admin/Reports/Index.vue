<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    dailySummary: Array,
    counterPerformance: Array,
    serviceBreakdown: Array,
    periodStats: Object,
    filters: Object,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const applyFilter = () => {
    router.get('/admin/reports', {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const exportCSV = () => {
    // Simple CSV export for daily summary
    let csv = 'Tanggal,Total Antrian,Selesai,Dilewati,Rata-rata Waktu (menit)\n';
    props.dailySummary.forEach(row => {
        csv += `${row.date},${row.total},${row.served},${row.skipped},${row.avg_service_time || 0}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `laporan_harian_${startDate.value}_${endDate.value}.csv`;
    a.click();
};
</script>

<template>
    <Layout>
        <Head title="Laporan Pelayanan" />

        <div class="mb-8">
            <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Laporan Pelayanan</h2>
            <p class="text-slate-500">Statistik dan analisis performa pelayanan antrian.</p>
        </div>

        <!-- Date Filter -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tanggal Mulai</label>
                    <input 
                        type="date" 
                        v-model="startDate"
                        class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tanggal Akhir</label>
                    <input 
                        type="date" 
                        v-model="endDate"
                        class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                <button @click="applyFilter" class="bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition-all">
                    🔍 Filter
                </button>
                <button @click="exportCSV" class="bg-green-50 text-green-600 border border-green-200 px-4 py-2 rounded-xl text-sm font-bold hover:bg-green-600 hover:text-white transition-all">
                    📥 Export CSV
                </button>
            </div>
        </div>

        <!-- Period Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Antrian</div>
                <div class="text-4xl font-black text-slate-800">{{ periodStats.total_queues }}</div>
                <div class="mt-2 text-xs text-slate-400">Periode terpilih</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Selesai</div>
                <div class="text-4xl font-black text-green-600">{{ periodStats.total_served }}</div>
                <div class="mt-2 text-xs text-green-400">Terlayani</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Dilewati</div>
                <div class="text-4xl font-black text-amber-600">{{ periodStats.total_skipped }}</div>
                <div class="mt-2 text-xs text-amber-400">Skipped</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Rata-rata/Hari</div>
                <div class="text-4xl font-black text-blue-600">{{ periodStats.avg_per_day }}</div>
                <div class="mt-2 text-xs text-blue-400">Antrian</div>
            </div>
        </div>

        <!-- Counter Performance -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">📊 Performa Per Loket</h3>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-black text-slate-500 uppercase">Loket</th>
                            <th class="text-left px-6 py-4 text-xs font-black text-slate-500 uppercase">Lantai</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Total Dilayani</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Hari Aktif</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Rata-rata/Hari</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Waktu Layanan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="counter in counterPerformance" :key="counter.counter_id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ counter.counter_name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ counter.floor_name }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-bold">{{ counter.total_served }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-600">{{ counter.days_active }} hari</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-bold">{{ counter.avg_per_day }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-500">{{ counter.avg_service_time }} menit</td>
                        </tr>
                        <tr v-if="counterPerformance.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data untuk periode ini
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Service Breakdown -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">🏷️ Statistik Per Layanan</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="service in serviceBreakdown" :key="service.service_id" 
                    class="bg-white p-5 rounded-xl border border-slate-100"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-xs font-black text-blue-600">{{ service.service_code }}</div>
                            <div class="font-bold text-slate-700">{{ service.service_name }}</div>
                        </div>
                        <div class="text-2xl font-black text-slate-800">{{ service.total }}</div>
                    </div>
                    <div class="flex gap-4 text-sm">
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-slate-500">Selesai:</span>
                            <span class="font-bold text-green-600">{{ service.served }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                            <span class="text-slate-500">Dilewati:</span>
                            <span class="font-bold text-amber-600">{{ service.skipped }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Summary Table -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">📅 Laporan Harian</h3>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-4 text-xs font-black text-slate-500 uppercase">Tanggal</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Total Antrian</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Selesai</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Dilewati</th>
                            <th class="text-center px-6 py-4 text-xs font-black text-slate-500 uppercase">Rata-rata Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="day in dailySummary" :key="day.date" class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ formatDate(day.date) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-full text-sm font-bold">{{ day.total }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-bold">{{ day.served }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-sm font-bold">{{ day.skipped }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-500">
                                {{ day.avg_service_time ? Math.round(day.avg_service_time) + ' menit' : '-' }}
                            </td>
                        </tr>
                        <tr v-if="dailySummary.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data untuk periode ini
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </Layout>
</template>
