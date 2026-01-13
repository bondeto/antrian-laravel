<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PromoMedia from '@/Components/PromoMedia.vue';
import { useQueueVoice } from '@/Composables/useQueueVoice';

const props = defineProps({
    floors: Array,
    initialServing: Array,
    mediaSettings: Object,
});

const { playQueueCall } = useQueueVoice();
const serving = ref(props.initialServing || []);
const lastCalled = ref(null);
const showOverlay = ref(false);

// Debounce mechanism to prevent duplicate events
const recentlyProcessed = new Map();
const DEBOUNCE_MS = 2000;

const isDuplicate = (eventType, queueId) => {
    const key = `${eventType}-${queueId}`;
    const now = Date.now();
    const lastTime = recentlyProcessed.get(key);
    
    if (lastTime && (now - lastTime) < DEBOUNCE_MS) {
        console.log(`[Lobby] Ignoring duplicate ${eventType} for queue ${queueId}`);
        return true;
    }
    
    recentlyProcessed.set(key, now);
    return false;
};

onMounted(() => {
    console.log('[Lobby] Mounted. Floors:', props.floors);
    console.log('[Lobby] Initial Serving:', props.initialServing);
    console.log('[Lobby] Media Settings:', props.mediaSettings);

    // Listen for calls on ALL floors for the lobby
    if (props.floors && props.floors.length > 0) {
        props.floors.forEach(floor => {
            console.log(`[Lobby] Subscribing to channel: monitor.floor.${floor.id}`);
            window.Echo.channel(`monitor.floor.${floor.id}`)
                .listen('QueueCalled', (e) => {
                    console.log('[Lobby] QueueCalled event received:', e);
                    const queue = e.queue;
                    
                    // Skip if duplicate event
                    if (isDuplicate('called', queue.id)) return;
                    
                    // Overlay logic
                    lastCalled.value = queue;
                    showOverlay.value = true;
                    
                    // Update list (remove if exists first)
                    const exists = serving.value.findIndex(q => q.id === queue.id);
                    if (exists !== -1) {
                        serving.value.splice(exists, 1);
                    }
                    serving.value.unshift(queue);
                    if (serving.value.length > 5) serving.value.pop();

                    // Play voice announcement
                    playQueueCall(queue);

                    // Clear overlay after 10s
                    setTimeout(() => {
                        showOverlay.value = false;
                    }, 10000);
                })
                .listen('QueueUpdated', (e) => {
                    console.log('[Lobby] QueueUpdated event received:', e);
                    const queue = e.queue;
                    
                    // Skip if duplicate event
                    if (isDuplicate('updated', queue.id)) return;
                    
                    // If queue is served or skipped, remove from the list
                    if (queue.status === 'served' || queue.status === 'skipped') {
                        const index = serving.value.findIndex(q => q.id === queue.id);
                        if (index !== -1) {
                            serving.value.splice(index, 1);
                            console.log('[Lobby] Removed queue from list:', queue.full_number);
                        }
                    }
                });
        });
    } else {
        console.warn('[Lobby] No floors found! Cannot subscribe to any channels.');
    }
    
    // Listen for settings updates (remote refresh)
    window.Echo.channel('settings')
        .listen('.SettingsUpdated', (e) => {
            console.log('[Lobby] Settings updated, refreshing page...', e);
            // Reload the page to apply new settings
            window.location.reload();
        });
});
</script>

<template>
    <Head title="Lobby Utama - Monitor Panggilan" />
    <div class="h-screen bg-[#020617] text-white flex flex-col font-sans overflow-hidden">
        <!-- Header -->
        <header class="bg-slate-900/50 backdrop-blur-md p-6 shadow-2xl flex justify-between items-center border-b border-white/5 relative z-10">
            <div class="flex items-center gap-4">
                <div class="bg-blue-600 p-3 rounded-2xl shadow-lg shadow-blue-500/20">
                    <span class="text-3xl">🏛️</span>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tighter uppercase leading-none mb-1">{{ mediaSettings.monitor_header || 'Pusat Antrian' }}</h1>
                    <p class="text-blue-400 text-[10px] font-black tracking-[0.3em] uppercase opacity-70">{{ mediaSettings.monitor_subheader || 'Lobby Utama' }} • {{ floors.length }} Lantai Terintegrasi</p>
                </div>
            </div>
            <div class="flex items-center gap-8">
                <div class="text-right">
                    <div class="text-4xl font-mono font-black text-white" id="clock">
                        {{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                    </div>
                    <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }) }}</div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-8 flex gap-8 relative overflow-hidden bg-radial-at-t from-slate-900 via-[#020617] to-[#020617]">
            <!-- Left: Media Section (Stronger Presence) -->
            <div class="flex-[1.8] rounded-[2.5rem] overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.5)] border border-white/5 relative bg-black group transition-all">
                <PromoMedia :settings="mediaSettings" />
                <div class="absolute inset-0 pointer-events-none border-[12px] border-slate-900/50 rounded-[2.5rem]"></div>
            </div>

            <!-- Right: Serving List (Clean & Spaced) -->
            <div class="flex-1 flex flex-col gap-6">
                <div class="flex items-center justify-between px-4">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                        <h2 class="text-xs font-black text-slate-400 uppercase tracking-[0.4em]">Panggilan Aktif</h2>
                    </div>
                    <div class="bg-slate-800/50 px-3 py-1 rounded-full text-[10px] font-black text-slate-500 border border-white/5 uppercase">5 Terakhir</div>
                </div>

                <div class="flex-1 flex flex-col gap-4">
                    <transition-group name="list">
                        <div v-for="(q, index) in serving" :key="q.id" 
                            class="bg-white/5 backdrop-blur-xl border border-white/10 p-5 rounded-[2rem] shadow-2xl flex justify-between items-center relative overflow-hidden group"
                            :class="{'ring-2 ring-blue-500 bg-blue-600/10 border-blue-500/30': index === 0}"
                        >
                            <!-- Index Indicator -->
                            <div v-if="index === 0" class="absolute -left-2 top-1/2 -translate-y-1/2 w-1 h-12 bg-blue-500 rounded-full shadow-[0_0_20px_rgba(59,130,246,0.8)]"></div>

                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full uppercase tracking-widest">{{ q.floor?.name }}</span>
                                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ q.service?.name }}</span>
                                </div>
                                <div class="text-6xl font-black text-white font-mono tracking-tighter">{{ q.full_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Silakan Ke</div>
                                <div class="text-2xl font-black text-blue-500 uppercase leading-none">{{ q.counter?.name }}</div>
                            </div>
                        </div>
                    </transition-group>

                    <!-- Empty State -->
                    <div v-if="serving.length === 0" class="flex-1 flex flex-col items-center justify-center opacity-10 border-4 border-dashed border-slate-700/50 rounded-[3rem]">
                        <span class="text-8xl mb-6">📢</span>
                        <p class="text-lg font-black uppercase tracking-[0.5em] text-slate-400">Siap Melayani</p>
                    </div>
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
                        {{ mediaSettings.news_ticker }}
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
    animation: marquee 80s linear infinite;
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
