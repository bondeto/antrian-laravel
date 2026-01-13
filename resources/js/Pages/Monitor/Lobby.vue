<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PromoMedia from '@/Components/PromoMedia.vue';

const props = defineProps({
    floors: Array,
    initialServing: Array,
    mediaSettings: Object,
});

const serving = ref(props.initialServing);
const lastCalled = ref(null);
const showOverlay = ref(false);

const speak = (text) => {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 0.9;
        window.speechSynthesis.speak(utterance);
    }
};

onMounted(() => {
    // Listen for calls on ALL floors for the lobby
    props.floors.forEach(floor => {
        window.Echo.channel(`monitor.floor.${floor.id}`)
            .listen('QueueCalled', (e) => {
                const queue = e.queue;
                
                // Overlay logic
                lastCalled.value = queue;
                showOverlay.value = true;
                
                // Update list
                const exists = serving.value.findIndex(q => q.id === queue.id);
                if (exists !== -1) {
                    serving.value.splice(exists, 1);
                }
                serving.value.unshift(queue);
                if (serving.value.length > 12) serving.value.pop();

                // TTS
                const text = `Nomor Antrian ${queue.full_number.split('-').join(' ')}, Silakan ke ${queue.counter?.name || 'Loket'}, di ${queue.floor?.name || 'Lantai'}`;
                speak(text);

                // Clear overlay after 10s
                setTimeout(() => {
                    showOverlay.value = false;
                }, 10000);
            });
    });
});
</script>

<template>
    <Head title="Lobby Utama - Monitor Panggilan" />
    <div class="min-h-screen bg-[#0f172a] text-white flex flex-col font-sans overflow-hidden">
        <!-- Header -->
        <header class="bg-gradient-to-r from-blue-900 to-indigo-900 p-6 shadow-2xl flex justify-between items-center border-b border-blue-500/30">
            <div class="flex items-center gap-4">
                <div class="bg-blue-500 p-3 rounded-lg shadow-lg shadow-blue-500/20">
                    <span class="text-3xl">🏛️</span>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tighter uppercase">Lobby Utama</h1>
                    <p class="text-blue-300 text-sm font-medium tracking-widest uppercase">Pusat Informasi Antrian</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-mono font-bold text-blue-100" id="clock">
                    {{ new Date().toLocaleTimeString('id-ID') }}
                </div>
                <div class="text-blue-400 text-sm">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6 grid grid-cols-12 gap-6 relative overflow-hidden">
            <!-- Left: Media Section -->
            <div class="col-span-8 rounded-3xl overflow-hidden shadow-2xl border border-slate-700/50 relative">
                <PromoMedia :settings="mediaSettings" />
            </div>

            <!-- Right: Serving List -->
            <div class="col-span-4 flex flex-col gap-4 overflow-y-auto">
                <div class="text-xs font-black text-blue-400 uppercase tracking-[0.3em] mb-2 px-2 flex justify-between">
                    <span>Panggilan Aktif</span>
                    <span>Total: {{ serving.length }}</span>
                </div>
                <transition-group name="list">
                    <div v-for="(q, index) in serving" :key="q.id" 
                        class="bg-slate-800/80 backdrop-blur-md border border-slate-700/50 p-4 rounded-2xl shadow-lg flex justify-between items-center transition-all duration-300"
                        :class="{'ring-2 ring-blue-500 bg-slate-700 border-blue-400/50': index === 0}"
                    >
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase font-black mb-1">{{ q.floor?.name }} • {{ q.service?.name }}</div>
                            <div class="text-4xl font-black text-white font-mono">{{ q.full_number }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-blue-400 uppercase font-bold mb-1">Ke</div>
                            <div class="text-xl font-bold uppercase text-white leading-none">{{ q.counter?.name }}</div>
                        </div>
                    </div>
                </transition-group>

                <!-- Empty State -->
                <div v-if="serving.length === 0" class="flex-1 flex flex-col items-center justify-center opacity-20 border-2 border-dashed border-slate-700 rounded-2xl">
                    <span class="text-6xl mb-4">🎫</span>
                    <p class="text-sm font-bold uppercase tracking-widest">Menunggu Panggilan</p>
                </div>
            </div>

            <!-- Call Overlay -->
            <transition name="fade">
                <div v-if="showOverlay && lastCalled" class="fixed inset-0 z-50 flex items-center justify-center p-10 bg-black/90 backdrop-blur-xl">
                    <div class="bg-gradient-to-br from-blue-600 via-indigo-700 to-purple-800 p-1 rounded-3xl shadow-[0_0_100px_rgba(37,99,235,0.4)] animate-call">
                        <div class="bg-slate-900 rounded-[calc(1.5rem-2px)] p-16 text-center">
                            <div class="text-blue-400 font-black tracking-[0.5em] uppercase mb-8 animate-pulse text-2xl">Panggilan Antrian</div>
                            <div class="text-[15rem] leading-none font-black text-white mb-10 drop-shadow-[0_20px_50px_rgba(255,255,255,0.2)] font-mono">
                                {{ lastCalled.full_number }}
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="text-6xl text-blue-200 font-bold uppercase tracking-tight">Menuju {{ lastCalled.counter?.name }}</div>
                                <div class="text-3xl text-blue-400 font-medium uppercase tracking-[0.2em] mt-4">{{ lastCalled.floor?.name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 border-t border-slate-800 p-4 relative overflow-hidden">
            <div class="flex items-center gap-6">
                <div class="bg-red-600 px-4 py-1 font-bold text-sm rounded shadow-lg shadow-red-600/20 z-10 animate-pulse">BREAKING</div>
                <div class="carousel-container overflow-hidden flex-1 relative h-6">
                    <div class="carousel-track whitespace-nowrap absolute animate-marquee font-medium text-slate-300">
                        Selamat datang di Layanan Kami • Pastikan Anda telah mengambil nomor antrian sesuai layanan yang diinginkan • Budayakan antre untuk kenyamanan bersama • Jam operasional hari ini 08:00 - 15:30 WIB • Harap siapkan dokumen pendukung untuk mempercepat pelayanan.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-marquee {
    display: inline-block;
    padding-left: 100%;
    animation: marquee 25s linear infinite;
}

@keyframes marquee {
    0% { transform: translate(0, 0); }
    100% { transform: translate(-100%, 0); }
}

.animate-call {
    animation: bounce-in 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes bounce-in {
    0% { transform: scale(0.5); opacity: 0; }
    70% { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.list-enter-active, .list-leave-active {
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from, .list-leave-to {
  opacity: 0;
  transform: translateY(-30px) scale(0.9);
}
</style>
