<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    stats: Object,
    services: Array,
    floors: Array,
    activeServing: Array,
    selectedDate: String,
});

const stats = ref(props.stats);
const services = ref(props.services);
const activeServing = ref(props.activeServing || []);
const filterDate = ref(props.selectedDate || new Date().toISOString().split('T')[0]);

// Watch for props changes when filtering
watch(() => props.stats, (newStats) => {
    stats.value = newStats;
}, { deep: true });

const applyDateFilter = () => {
    router.get('/admin/dashboard', { date: filterDate.value }, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'selectedDate'],
    });
};

// Watch for date changes
watch(filterDate, (newDate) => {
    applyDateFilter();
});

const resetDaily = () => {
    if (confirm('Reset harian akan:\n• Mereset nomor urut ke 0\n• Menghapus antrian yang belum selesai\n• Menyimpan histori antrian yang sudah selesai\n\nLanjutkan?')) {
        router.post('/admin/reset');
    }
};

const resetAll = () => {
    if (confirm('⚠️ PERINGATAN: Ini akan MENGHAPUS SEMUA DATA antrian termasuk histori!\n\nTindakan ini tidak dapat dibatalkan.\n\nAnda yakin?')) {
        if (confirm('Konfirmasi sekali lagi: Hapus SEMUA data antrian?')) {
            router.post('/admin/reset-all');
        }
    }
};

onMounted(() => {
    window.Echo.channel('monitor.all')
        .listen('QueueCreated', (e) => {
            if (!e.queue) return;
            if (e.stats) {
                stats.value.total = e.stats.total;
                stats.value.waiting = e.stats.waiting;
                services.value.forEach(s => {
                    if (e.stats.services[s.id] !== undefined) {
                        s.waiting_count = e.stats.services[s.id];
                    }
                });
            } else {
                // Fallback if stats not provided
                stats.value.total++;
                stats.value.waiting++;
                const sIdx = services.value?.findIndex(s => s.id === e.queue.service_id) ?? -1;
                if (sIdx !== -1) services.value[sIdx].waiting_count++;
            }
        })
        .listen('QueueCalled', (e) => {
            if (!e.queue) return;
            if (e.stats) {
                stats.value.waiting = e.stats.waiting;
                services.value.forEach(s => {
                    if (e.stats.services[s.id] !== undefined) {
                        s.waiting_count = e.stats.services[s.id];
                    }
                });
            } else {
                stats.value.waiting--;
                const sIdx = services.value?.findIndex(s => s.id === e.queue.service_id) ?? -1;
                if (sIdx !== -1 && services.value[sIdx].waiting_count > 0) {
                    services.value[sIdx].waiting_count--;
                }
            }
            
            // Add to active serving list (remove if exists first)
            const existIdx = activeServing.value.findIndex(q => q.id === e.queue.id);
            if (existIdx !== -1) {
                activeServing.value.splice(existIdx, 1);
            }
            activeServing.value.unshift(e.queue);
            if (activeServing.value.length > 10) activeServing.value.pop();
        })
        .listen('QueueUpdated', (e) => {
            if (!e.queue) return;
            if (e.stats) {
                stats.value.waiting = e.stats.waiting;
                stats.value.served = e.stats.served;
                services.value.forEach(s => {
                    if (e.stats.services[s.id] !== undefined) {
                        s.waiting_count = e.stats.services[s.id];
                    }
                });
            } else if (e.queue.status === 'served') {
                stats.value.served++;
            }
            
            // Remove from active serving if served or skipped
            if (e.queue.status === 'served' || e.queue.status === 'skipped') {
                const idx = activeServing.value.findIndex(q => q.id === e.queue.id);
                if (idx !== -1) {
                    activeServing.value.splice(idx, 1);
                }
            }
        });
});
</script>

<template>
    <Layout>
        <Head title="Admin Dashboard" />

        <div class="mb-8 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Dashboard Overview</h2>
                <p class="text-slate-500">Statistik sistem antrian seluruh lantai.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-500 uppercase">Filter Tanggal:</label>
                    <input 
                        type="date" 
                        v-model="filterDate"
                        class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                <button @click="resetDaily" class="bg-amber-50 text-amber-600 border border-amber-200 px-4 py-2 rounded-xl text-xs font-bold hover:bg-amber-500 hover:text-white transition-all" title="Reset nomor urut, hapus antrian pending, simpan histori">
                    🔄 RESET HARIAN
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Antrian</div>
                <div class="text-4xl font-black text-slate-800">{{ stats.total }}</div>
                <div class="mt-4 text-xs text-slate-400 font-bold">{{ filterDate }}</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Menunggu</div>
                <div class="text-4xl font-black text-blue-600">{{ stats.waiting }}</div>
                <div class="mt-4 text-xs text-blue-400 font-bold">Waiting List</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Selesai</div>
                <div class="text-4xl font-black text-green-600">{{ stats.served }}</div>
                <div class="mt-4 text-xs text-green-400 font-bold">Terlayani</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Dilewati</div>
                <div class="text-4xl font-black text-amber-600">{{ stats.skipped || 0 }}</div>
                <div class="mt-4 text-xs text-amber-400 font-bold">Skipped</div>
            </div>
        </div>

        <!-- Active Serving Monitor -->
        <div class="mb-8">
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4 flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                Sedang Dilayani (Real-time)
            </h3>
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                <div v-if="activeServing.length > 0" class="divide-y divide-slate-100">
                    <div v-for="q in activeServing" :key="q.id" class="p-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="text-2xl font-black text-blue-600 font-mono">{{ q.full_number }}</div>
                            <div>
                                <div class="text-xs text-slate-400 uppercase font-bold">{{ q.service?.name || 'Service' }}</div>
                                <div class="text-sm text-slate-600">{{ q.floor?.name || 'Floor' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-800">{{ q.counter?.name || '-' }}</div>
                            <div class="text-xs text-green-500 uppercase font-bold">{{ q.status === 'called' ? 'Dipanggil' : 'Dilayani' }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-8 text-center text-slate-400">
                    <div class="text-4xl mb-2">☕</div>
                    <div class="text-sm font-bold">Belum ada antrian yang sedang dilayani</div>
                </div>
            </div>
        </div>

        <!-- Monitor Shortcuts -->
        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Layar Monitor (Buka di Tab Baru)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="/lobby" target="_blank" class="bg-indigo-600 p-4 rounded-xl shadow-lg border border-indigo-500 hover:bg-indigo-700 transition-all group">
                <div class="text-white font-black uppercase tracking-tighter text-sm mb-1">Lobby Utama</div>
                <div class="text-indigo-200 text-[10px] font-bold uppercase tracking-widest group-hover:text-white transition-colors">Semua Lantai &rarr;</div>
            </a>
            
            <a v-for="floor in floors" :key="floor.id" :href="`/monitor/${floor.id}`" target="_blank" 
                class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:shadow-md transition-all group"
            >
                <div class="text-slate-800 font-black uppercase tracking-tighter text-sm mb-1">{{ floor.name }}</div>
                <div class="text-slate-400 text-[10px] font-bold uppercase tracking-widest group-hover:text-blue-500 transition-colors">Monitor Lantai &rarr;</div>
            </a>
        </div>

        <!-- Services Status -->
        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4">Status Per Layanan</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="service in services" :key="service.id" 
                class="bg-white p-5 rounded-xl border border-slate-100 flex justify-between items-center"
            >
                <div>
                    <div class="text-xs font-black text-blue-600">{{ service.code }}</div>
                    <div class="font-bold text-slate-700">{{ service.name }}</div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black text-slate-800">{{ service.waiting_count }}</div>
                    <div class="text-[10px] text-slate-400 uppercase font-black tracking-tighter">Waiting</div>
                </div>
            </div>
        </div>
    </Layout>
</template>
