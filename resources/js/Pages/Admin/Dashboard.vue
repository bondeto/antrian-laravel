<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from './Layout.vue';

const props = defineProps({
    stats: Object,
    services: Array,
    floors: Array,
    counters: Array,
});

const counters = ref(props.counters);
const stats = ref(props.stats);
const services = ref(props.services);

const reset = () => {
    if (confirm('PERINGATAN: Ini akan menghapus semua data antrian dan mereset nomor ke 0. Lanjutkan?')) {
        router.post('/admin/reset');
    }
};

onMounted(() => {
    // Listen to ALL events on one global channel for admin
    window.Echo.channel('monitor.all')
        .listen('QueueCreated', (e) => {
            stats.value.total++;
            stats.value.waiting++;
            // Update specific service count
            const sIdx = services.value.findIndex(s => s.id === e.queue.service_id);
            if (sIdx !== -1) services.value[sIdx].waiting_count++;
        })
        .listen('QueueCalled', (e) => {
            const queue = e.queue;
            
            // Decrement waiting, status is now 'called' (still in total)
            stats.value.waiting--;
            
            // Update service waiting count
            const sIdx = services.value.findIndex(s => s.id === queue.service_id);
            if (sIdx !== -1) services.value[sIdx].waiting_count--;

            // Update counter status
            const cIdx = counters.value.findIndex(c => c.id === queue.counter_id);
            if (cIdx !== -1) {
                counters.value[cIdx].active_queue = queue;
            }
        })
        .listen('QueueUpdated', (e) => {
            const queue = e.queue;
            
            if (queue.status === 'served') {
                stats.value.served++;
            }

            // If served or skipped, clear the active queue from the counter
            if (queue.status === 'served' || queue.status === 'skipped') {
                const cIdx = counters.value.findIndex(c => c.id === queue.counter_id);
                if (cIdx !== -1) {
                    counters.value[cIdx].active_queue = null;
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
                <p class="text-slate-500">Statistik real-time sistem antrian seluruh lantai.</p>
            </div>
            <button @click="reset" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-xl text-xs font-bold hover:bg-red-600 hover:text-white transition-all">
                RESET SYSTEM
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Total Antrian</div>
                <div class="text-4xl font-black text-slate-800">{{ stats.total }}</div>
                <div class="mt-4 text-xs text-green-500 font-bold">Hari Ini</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Menunggu</div>
                <div class="text-4xl font-black text-blue-600">{{ stats.waiting }}</div>
                <div class="mt-4 text-xs text-blue-400 font-bold">Waiting List</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-slate-400 text-xs font-black uppercase tracking-widest mb-2">Selesai</div>
                <div class="text-4xl font-black text-green-600">{{ stats.served }}</div>
                <div class="mt-4 text-xs text-green-400 font-bold">Total Terlayani</div>
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
        <!-- Live Counter Status -->
        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-4 mt-8">Monitor Status Loket</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="counter in counters" :key="counter.id" 
                class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden transition-all hover:shadow-md"
            >
                <div class="p-4 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-tight">{{ counter.floor?.name }}</div>
                        <div class="font-bold text-slate-800">{{ counter.name }}</div>
                    </div>
                    <div v-if="counter.active_queue" class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-[10px] font-black text-green-600 uppercase">Sibuk</span>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-slate-300 rounded-full"></span>
                        <span class="text-[10px] font-black text-slate-400 uppercase">Idle</span>
                    </div>
                </div>
                <div class="p-4">
                    <div v-if="counter.active_queue" class="flex items-center justify-between">
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-black mb-1">Sedang Melayani</div>
                            <div class="text-2xl font-black text-blue-600 font-mono">{{ counter.active_queue.full_number }}</div>
                            <div class="text-[10px] text-slate-500 font-bold uppercase">{{ counter.active_queue.service?.name }}</div>
                        </div>
                        <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-tighter">
                            {{ counter.active_queue.status === 'called' ? '📢 Calling' : '⏳ Serving' }}
                        </div>
                    </div>
                    <div v-else class="py-4 flex flex-col items-center justify-center text-slate-300">
                        <span class="text-3xl mb-1">💤</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest">Tidak ada aktivitas</span>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
