<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    counters: Array,
    floors: Array,
    stats: Object,
    services: Array,
});

const counters = ref(props.counters);
const stats = ref(props.stats);
const services = ref(props.services || []);

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
            
            // Decrement waiting
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
        <Head title="Monitor Status Loket" />

        <div class="mb-8">
            <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">Monitor Status Loket</h2>
            <p class="text-slate-500">Pemantauan aktivitas seluruh loket secara real-time.</p>
        </div>

        <!-- Quick Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-blue-600 p-4 rounded-xl text-white shadow-lg shadow-blue-200">
                <div class="text-[10px] uppercase font-black opacity-80 mb-1">Total Antrian</div>
                <div class="text-2xl font-black">{{ stats.total }}</div>
            </div>
            <div class="bg-indigo-600 p-4 rounded-xl text-white shadow-lg shadow-indigo-200">
                <div class="text-[10px] uppercase font-black opacity-80 mb-1">Menunggu</div>
                <div class="text-2xl font-black">{{ stats.waiting }}</div>
            </div>
            <div class="bg-emerald-600 p-4 rounded-xl text-white shadow-lg shadow-emerald-200">
                <div class="text-[10px] uppercase font-black opacity-80 mb-1">Telah Dilayani</div>
                <div class="text-2xl font-black">{{ stats.served }}</div>
            </div>
        </div>

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
