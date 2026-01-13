<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PromoMedia from '@/Components/PromoMedia.vue';

import { useQueueVoice } from '@/Composables/useQueueVoice';

const props = defineProps({
    floor: Object,
    initialServing: Array,
    initialWaiting: Array,
    mediaSettings: Object,
});

const { playQueueCall } = useQueueVoice();
const serving = ref(props.initialServing || []);
const lastCalled = ref(null);

// Debounce mechanism to prevent duplicate events
const recentlyProcessed = new Map();
const DEBOUNCE_MS = 2000;

const isDuplicate = (eventType, queueId) => {
    const key = `${eventType}-${queueId}`;
    const now = Date.now();
    const lastTime = recentlyProcessed.get(key);
    
    if (lastTime && (now - lastTime) < DEBOUNCE_MS) {
        console.log(`[Monitor/Show] Ignoring duplicate ${eventType} for queue ${queueId}`);
        return true;
    }
    
    recentlyProcessed.set(key, now);
    return false;
};

onMounted(() => {
    console.log('[Monitor/Show] Mounted for floor:', props.floor?.id, props.floor?.name);
    
    // Listen to queue changes
    window.Echo.channel(`monitor.floor.${props.floor.id}`)
        .listen('QueueCalled', (e) => {
            console.log('[Monitor/Show] QueueCalled event received:', e);
            const queue = e.queue;
            
            // Skip if duplicate event
            if (isDuplicate('called', queue.id)) return;
            
            lastCalled.value = queue;
            
            // Remove existing entry if recalled (prevent duplicates in list)
            const existingIndex = serving.value.findIndex(q => q.id === queue.id);
            if (existingIndex !== -1) {
                serving.value.splice(existingIndex, 1);
            }
            
            // Add to top of serving list
            serving.value.unshift(queue);
            if (serving.value.length > 5) serving.value.pop();

            // Play voice announcement
            playQueueCall(queue);

            // Clear overlay after 8s
            setTimeout(() => {
                lastCalled.value = null;
            }, 8000);
        })
        .listen('QueueUpdated', (e) => {
            console.log('[Monitor/Show] QueueUpdated event received:', e);
            const queue = e.queue;
            
            // Skip if duplicate event
            if (isDuplicate('updated', queue.id)) return;
            
            // If queue is served or skipped, remove from the list
            if (queue.status === 'served' || queue.status === 'skipped') {
                const index = serving.value.findIndex(q => q.id === queue.id);
                if (index !== -1) {
                    serving.value.splice(index, 1);
                    console.log('[Monitor/Show] Removed queue from list:', queue.full_number);
                }
            }
        });
    
    // Listen for settings updates (remote refresh)
    window.Echo.channel('settings')
        .listen('.SettingsUpdated', (e) => {
            console.log('[Monitor/Show] Settings updated, refreshing page...', e);
            // Reload the page to apply new settings
            window.location.reload();
        });
});
</script>

<template>
    <Head :title="`Monitor - ${floor.name}`" />
    <div class="min-h-screen bg-gray-900 text-white overflow-hidden flex flex-col">
        <!-- Header -->
        <header class="bg-blue-800 p-4 shadow-lg text-center z-10">
            <h1 class="text-3xl font-bold tracking-wider">{{ floor.name }} - {{ mediaSettings.monitor_header || 'ANTREAN KANTOR' }}</h1>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex overflow-hidden">
            <!-- Left: Video / Media -->
            <div class="w-2/3 bg-black relative flex items-center justify-center">
                <PromoMedia :settings="mediaSettings" />
                
                <!-- Popup Overlay for Last Called -->
                <div v-if="lastCalled" key="lastCalled.id" class="absolute inset-0 flex items-center justify-center bg-black/80 z-20 animate-pulse">
                    <div class="text-center border-4 border-yellow-400 p-10 rounded-xl bg-blue-900">
                        <div class="text-4xl text-yellow-400 mb-2">PANGGILAN</div>
                        <div class="text-9xl font-black text-white mb-4">{{ lastCalled.full_number }}</div>
                        <div class="text-5xl text-white">KE {{ lastCalled.counter?.name }}</div>
                    </div>
                </div>
            </div>

            <!-- Right: Queue List -->
            <div class="w-1/3 bg-gray-800 border-l border-gray-700 flex flex-col">
                <div class="p-4 bg-blue-900 text-center font-bold text-xl uppercase">Sedang Dilayani</div>
                <div class="flex-1 overflow-hidden p-4 space-y-4">
                    <transition-group name="list" tag="div">
                        <div v-for="(q, index) in serving" :key="q.id" 
                            class="bg-white text-gray-900 p-6 rounded-lg shadow-lg flex justify-between items-center transform transition-all duration-500"
                            :class="{'scale-105 border-l-8 border-green-500': index === 0, 'opacity-75': index > 0}"
                        >
                            <div class="text-left">
                                <div class="text-xs text-gray-500 uppercase tracking-widest">{{ q.service?.name }}</div>
                                <div class="text-5xl font-bold">{{ q.full_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Loket</div>
                                <div class="text-3xl font-bold text-blue-600">{{ q.counter?.name ?? '-' }}</div>
                            </div>
                        </div>
                    </transition-group>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-blue-900 p-2 text-white overflow-hidden whitespace-nowrap">
            <div class="animate-marquee inline-block">
                {{ mediaSettings.news_ticker }}
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-marquee {
    animation: marquee 70s linear infinite;
}
@keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

.list-enter-active, .list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from, .list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
