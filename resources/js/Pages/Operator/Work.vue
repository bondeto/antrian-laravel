<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, Head } from '@inertiajs/vue3';

const props = defineProps({
    counter: Object,
    currentQueue: Object,
    stats: Object,
    flash: Object,
});

const isLoading = ref(false);
const currentTime = ref(new Date());
let timeInterval = null;

onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

const callNext = () => {
    if (isLoading.value) return;
    isLoading.value = true;
    router.post(`/operator/${props.counter.id}/call`, {}, {
        onFinish: () => isLoading.value = false
    });
};

const recall = () => {
    if (props.currentQueue && !isLoading.value) {
        isLoading.value = true;
        router.post(`/operator/queue/${props.currentQueue.id}/recall`, {}, {
            onFinish: () => isLoading.value = false
        });
    }
};

const served = () => {
    if (props.currentQueue && !isLoading.value) {
        isLoading.value = true;
        router.post(`/operator/queue/${props.currentQueue.id}/served`, {}, {
            onFinish: () => isLoading.value = false
        });
    }
};

const skip = () => {
    if (props.currentQueue && !isLoading.value) {
        isLoading.value = true;
        router.post(`/operator/queue/${props.currentQueue.id}/skip`, {}, {
            onFinish: () => isLoading.value = false
        });
    }
};

const exit = () => {
    router.get('/operator');
};

const formatTime = (date) => {
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const formatDate = (date) => {
    return date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Operator - ${counter.name}`" />
    
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col font-sans">
        <!-- Header -->
        <header class="bg-white/5 backdrop-blur-xl border-b border-white/10 px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-3 rounded-2xl shadow-lg shadow-blue-500/20">
                    <span class="text-2xl">🖥️</span>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">{{ counter.name }}</h1>
                    <p class="text-blue-400 text-xs font-bold uppercase tracking-widest">{{ counter.floor?.name }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- Clock -->
                <div class="text-right hidden sm:block">
                    <div class="text-2xl font-mono font-bold text-white">{{ formatTime(currentTime) }}</div>
                    <div class="text-slate-400 text-[10px] uppercase tracking-widest">{{ formatDate(currentTime) }}</div>
                </div>
                
                <button @click="exit" class="bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white px-4 py-2 rounded-xl font-bold text-sm transition-all border border-red-500/30 hover:border-red-500">
                    ← Keluar
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-8">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-6 h-full">
                
                <!-- Left: Currently Serving (3 cols) -->
                <div class="lg:col-span-3 bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 flex flex-col items-center justify-center relative overflow-hidden">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10 text-center w-full">
                        <div class="flex items-center justify-center gap-2 mb-6">
                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                            <h2 class="text-slate-400 text-xs font-black uppercase tracking-[0.3em]">Sedang Melayani</h2>
                        </div>
                        
                        <div v-if="currentQueue" class="animate-fade-in">
                            <!-- Customer Photo (if available) -->
                            <div v-if="currentQueue.photo_url" class="mb-6">
                                <div class="inline-block relative">
                                    <img 
                                        :src="currentQueue.photo_url" 
                                        alt="Foto Pengambil Tiket"
                                        class="w-32 h-32 object-cover rounded-2xl ring-4 ring-blue-500/30 shadow-2xl"
                                    />
                                    <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-lg">
                                        📸 Foto Pengambil
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Service Badge -->
                            <div class="inline-block bg-gradient-to-r from-blue-500/20 to-indigo-500/20 border border-blue-500/30 text-blue-400 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6">
                                {{ currentQueue.service?.name }}
                            </div>
                            
                            <!-- Queue Number - HUGE -->
                            <div class="text-[10rem] leading-none font-black text-transparent bg-clip-text bg-gradient-to-br from-white via-blue-100 to-blue-300 font-mono tracking-tighter mb-6 drop-shadow-2xl">
                                {{ currentQueue.full_number }}
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="inline-flex items-center gap-2 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-5 py-2 rounded-full text-sm font-bold uppercase tracking-wider mb-10">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                {{ currentQueue.status === 'called' ? 'Dipanggil' : 'Sedang Dilayani' }}
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto">
                                <button 
                                    @click="recall" 
                                    :disabled="isLoading"
                                    class="group bg-amber-500/10 hover:bg-amber-500 border border-amber-500/30 hover:border-amber-500 text-amber-400 hover:text-white py-5 rounded-2xl font-bold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">📢</div>
                                    <div class="text-xs uppercase tracking-wider">Panggil Ulang</div>
                                </button>
                                
                                <button 
                                    @click="served" 
                                    :disabled="isLoading"
                                    class="group bg-emerald-500/10 hover:bg-emerald-500 border border-emerald-500/30 hover:border-emerald-500 text-emerald-400 hover:text-white py-5 rounded-2xl font-bold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">✅</div>
                                    <div class="text-xs uppercase tracking-wider">Selesai</div>
                                </button>
                                
                                <button 
                                    @click="skip" 
                                    :disabled="isLoading"
                                    class="group bg-red-500/10 hover:bg-red-500 border border-red-500/30 hover:border-red-500 text-red-400 hover:text-white py-5 rounded-2xl font-bold transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">⏭️</div>
                                    <div class="text-xs uppercase tracking-wider">Lewati</div>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-16">
                            <div class="text-8xl mb-6 opacity-30">🎯</div>
                            <p class="text-slate-400 text-xl font-bold mb-2">Tidak Ada Antrian Aktif</p>
                            <p class="text-slate-500 text-sm">Silakan panggil nomor berikutnya untuk memulai pelayanan.</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Controls & Stats (2 cols) -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <!-- Main Call Button -->
                    <button 
                        @click="callNext" 
                        :disabled="isLoading"
                        class="group relative bg-gradient-to-br from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 p-8 rounded-3xl shadow-2xl shadow-blue-500/30 text-white text-center transition-all duration-300 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden"
                    >
                        <!-- Animated Background -->
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
                        
                        <div class="relative z-10">
                            <div class="text-6xl mb-3 group-hover:scale-110 transition-transform duration-300">
                                {{ isLoading ? '⏳' : '📞' }}
                            </div>
                            <h2 class="text-2xl font-black tracking-tight mb-1">PANGGIL BERIKUTNYA</h2>
                            <p class="text-blue-200 text-sm font-medium">Klik untuk memanggil antrian</p>
                        </div>
                    </button>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-2xl text-center">
                            <div class="text-4xl font-black text-blue-400 font-mono mb-1">{{ stats.waiting }}</div>
                            <div class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Menunggu</div>
                            <div class="mt-3 w-full bg-slate-700 rounded-full h-1.5">
                                <div class="bg-blue-500 h-1.5 rounded-full transition-all" :style="`width: ${Math.min(stats.waiting * 10, 100)}%`"></div>
                            </div>
                        </div>
                        
                        <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-2xl text-center">
                            <div class="text-4xl font-black text-emerald-400 font-mono mb-1">{{ stats.served }}</div>
                            <div class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Selesai</div>
                            <div class="mt-3 w-full bg-slate-700 rounded-full h-1.5">
                                <div class="bg-emerald-500 h-1.5 rounded-full transition-all" :style="`width: ${Math.min(stats.served * 5, 100)}%`"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-5 rounded-2xl">
                        <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-3">Pintasan Keyboard</div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-slate-300">
                                <span>Panggil Berikutnya</span>
                                <kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs font-mono">Space</kbd>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Selesai</span>
                                <kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs font-mono">Enter</kbd>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Panggil Ulang</span>
                                <kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs font-mono">R</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
