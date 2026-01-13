<script setup>
import { Link, Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    floors: Array,
});

const floors = ref(props.floors);

onMounted(() => {
    window.Echo.channel('monitor.all')
        .listen('QueueCreated', (e) => {
            const fIdx = floors.value.findIndex(f => f.id === e.queue.floor_id);
            if (fIdx !== -1) floors.value[fIdx].waiting_count++;
        })
        .listen('QueueCalled', (e) => {
            // Update floor waiting count
            const fIdx = floors.value.findIndex(f => f.id === e.queue.floor_id);
            if (fIdx !== -1) floors.value[fIdx].waiting_count--;

            // Update counter status
            floors.value.forEach(f => {
                const cIdx = f.counters.findIndex(c => c.id === e.queue.counter_id);
                if (cIdx !== -1) {
                    f.counters[cIdx].active_queue = e.queue;
                }
            });
        })
        .listen('QueueUpdated', (e) => {
            if (e.queue.status === 'served' || e.queue.status === 'skipped') {
                floors.value.forEach(f => {
                    const cIdx = f.counters.findIndex(c => c.id === e.queue.counter_id);
                    if (cIdx !== -1) {
                        f.counters[cIdx].active_queue = null;
                    }
                });
            }
        });
});
</script>

<template>
    <Head title="Pilih Loket" />
    <div class="min-h-screen bg-slate-50 flex flex-col items-center py-12 px-6 font-sans">
        <div class="max-w-6xl w-full">
            <div class="text-center mb-16 relative">
                <div class="absolute -top-10 left-1/2 -translate-x-1/2 text-8xl opacity-5 select-none font-black tracking-tighter uppercase">Operator</div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase mb-4">Pilih Loket Tugas</h1>
                <p class="text-slate-500 font-medium text-lg">Silakan pilih loket yang akan Anda operasikan hari ini.</p>
                <div class="mt-8 flex justify-center gap-6">
                    <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
                        <div class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mb-1">Total Lantai</div>
                        <div class="text-2xl font-black text-blue-600">{{ floors.length }}</div>
                    </div>
                    <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100">
                        <div class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mb-1">Total Antrian Tunggu</div>
                        <div class="text-2xl font-black text-amber-500">
                            {{ floors.reduce((acc, f) => acc + f.waiting_count, 0) }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-16">
                <div v-for="floor in floors" :key="floor.id" class="space-y-8 animate-fade-in">
                    <!-- Floor Header -->
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <div class="bg-blue-600 text-white px-6 py-2 rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg shadow-blue-600/20">
                                Lantai {{ floor.level }}
                            </div>
                            <div v-if="floor.waiting_count > 0" class="absolute -top-3 -right-3 bg-red-500 text-white text-[10px] font-black px-2 py-1 rounded-full border-2 border-white animate-bounce">
                                {{ floor.waiting_count }} JALUR
                            </div>
                        </div>
                        <div class="h-[2px] flex-1 bg-gradient-to-r from-slate-200 to-transparent"></div>
                        <div class="text-right">
                            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">{{ floor.name }}</h2>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">{{ floor.waiting_count || 0 }} Antrian Menunggu</p>
                        </div>
                    </div>

                    <!-- Counters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                        <Link 
                            v-for="counter in floor.counters" 
                            :key="counter.id" 
                            :href="`/operator/${counter.id}`"
                            class="group relative"
                        >
                            <div class="absolute inset-0 bg-blue-600 rounded-3xl translate-y-2 opacity-0 group-hover:opacity-20 transition-all blur-xl"></div>
                            
                            <div class="relative bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-2xl hover:border-blue-500 transition-all duration-300 hover:-translate-y-2 overflow-hidden flex flex-col h-full">
                                <!-- Status Indicator -->
                                <div class="absolute top-4 right-4 flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :class="counter.active_queue ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></div>
                                    <span class="text-[10px] font-black uppercase tracking-tighter" :class="counter.active_queue ? 'text-amber-600' : 'text-emerald-600'">
                                        {{ counter.active_queue ? 'Sibuk' : 'Tersedia' }}
                                    </span>
                                </div>

                                <div class="mb-6 relative">
                                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl group-hover:bg-blue-600 group-hover:text-white group-hover:scale-110 transition-all duration-300 shadow-inner">
                                        🖥️
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="font-black text-2xl text-slate-800 leading-tight group-hover:text-blue-600 transition-colors uppercase tracking-tighter">{{ counter.name }}</div>
                                    
                                    <!-- Operator Status -->
                                    <div v-if="counter.active_queue" class="mt-4 p-3 bg-amber-50 rounded-xl border border-amber-100 flex items-center gap-3">
                                        <div class="text-lg">📢</div>
                                        <div class="overflow-hidden">
                                            <div class="text-[9px] text-amber-400 font-black uppercase tracking-widest">Melayani</div>
                                            <div class="text-sm font-black text-amber-800 truncate">{{ counter.active_queue.full_number }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-3 opacity-60">
                                        <div class="text-lg">💤</div>
                                        <div>
                                            <div class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Status</div>
                                            <div class="text-sm font-black text-slate-500">Loket Kosong</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 flex items-center justify-between">
                                    <div class="text-blue-600 font-black text-[10px] uppercase tracking-widest">Mulai Bekerja</div>
                                    <div class="w-8 h-8 rounded-full border border-blue-100 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition-all">
                                        &rarr;
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <!-- Empty state per floor if no counters -->
                        <div v-if="floor.counters.length === 0" class="col-span-full py-16 flex flex-col items-center justify-center border-4 border-dashed border-slate-100 rounded-[3rem] text-slate-400 group">
                            <span class="text-5xl mb-4 group-hover:scale-125 transition-transform duration-500">🏢</span>
                            <p class="font-black uppercase tracking-widest text-sm text-slate-300">Belum ada loket di lantai ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="mt-24 text-center">
                <Link href="/" class="inline-flex items-center gap-2 px-8 py-3 bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-sm hover:shadow-lg">
                    &larr; Kembali ke Beranda
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
