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

const isAudioEnabled = ref(false);

const enableAudio = () => {
    isAudioEnabled.value = true;
    const audio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFRm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAP8A');
    audio.play().catch(() => {});
};

onMounted(() => {
    console.log('[Monitor/Show] Mounted for floor:', props.floor?.id, props.floor?.name);
    
    // Listen to queue changes
    window.Echo.channel(`monitor.floor.${props.floor.id}`)
        .listen('QueueReset', () => window.location.reload())
        .listen('QueueCalled', (e) => {
            console.log('[Monitor/Show] QueueCalled event received:', e);
            const queue = e.queue;
            if (isDuplicate('called', queue.id)) return;
            
            lastCalled.value = queue;
            const existingIndex = serving.value.findIndex(q => q.id === queue.id);
            if (existingIndex !== -1) serving.value.splice(existingIndex, 1);
            serving.value.unshift(queue);
            
            // Ensure no duplicates
            const seen = new Set();
            serving.value = serving.value.filter(q => {
                if (seen.has(q.id)) return false;
                seen.add(q.id);
                return true;
            });
            
            if (serving.value.length > 5) serving.value.pop();

            // Play voice announcement if enabled
            if (isAudioEnabled.value) {
                playQueueCall(queue);
            }

            setTimeout(() => {
                lastCalled.value = null;
            }, 8000);
        })
        .listen('QueueUpdated', (e) => {
            const queue = e.queue;
            if (isDuplicate('updated', queue.id)) return;
            if (queue.status === 'served' || queue.status === 'skipped') {
                const index = serving.value.findIndex(q => q.id === queue.id);
                if (index !== -1) serving.value.splice(index, 1);
            }
        });
    
    window.Echo.channel('settings')
        .listen('.SettingsUpdated', () => window.location.reload());

    // Auto-enable audio on first click anywhere (transparently)
    const handleFirstClick = () => {
        enableAudio();
        document.removeEventListener('click', handleFirstClick);
        console.log('[Monitor] Audio unlocked via user interaction');
    };
    document.addEventListener('click', handleFirstClick);
    
    // Try to auto-play a tiny silent sound on mount
    setTimeout(enableAudio, 1000);
});
</script>

<template>
    <Head :title="`Monitor - ${floor.name}`" />
    <div class="min-h-screen bg-gray-900 text-white overflow-hidden flex flex-col">
        <!-- Header -->
        <header class="bg-blue-800 p-4 shadow-lg text-center z-10">
            <h1 class="font-bold tracking-wider fluid-header">{{ floor.name }} - {{ mediaSettings.monitor_header || 'ANTREAN KANTOR' }}</h1>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex overflow-hidden">
            <!-- Left: Video / Media -->
            <div class="w-2/3 bg-black relative flex items-center justify-center">
                <PromoMedia :settings="mediaSettings" />
                
                <!-- Popup Overlay for Last Called -->
                <div v-if="lastCalled" key="lastCalled.id" class="absolute inset-0 flex items-center justify-center bg-black/80 z-20 animate-pulse">
                    <div class="text-center border-4 border-yellow-400 p-10 rounded-xl bg-blue-900">
                        <div class="text-yellow-400 mb-2 fluid-overlay-title">PANGGILAN</div>
                        <div class="font-black text-white mb-4 fluid-overlay-number">{{ lastCalled.full_number }}</div>
                        <div class="text-white fluid-overlay-counter">KE {{ lastCalled.counter?.name }}</div>
                    </div>
                </div>
            </div>

            <!-- Right: Queue List -->
            <div class="w-1/3 bg-gray-800 border-l border-gray-700 flex flex-col">
                <div class="p-4 bg-blue-900 text-center font-bold uppercase fluid-section-title">Sedang Dilayani</div>
                <div class="flex-1 overflow-hidden p-4 space-y-4">
                    <transition-group name="list" tag="div">
                        <div v-for="(q, index) in serving" :key="q.id" 
                            class="bg-white text-gray-900 p-6 rounded-lg shadow-lg flex justify-between items-center transform transition-all duration-500"
                            :class="{'scale-105 border-l-8 border-green-500': index === 0, 'opacity-75': index > 0}"
                        >
                            <div class="text-left">
                                <div class="text-gray-500 uppercase tracking-widest fluid-label">{{ q.service?.name }}</div>
                                <div class="font-bold fluid-queue-number">{{ q.full_number }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-500 fluid-label-sm">Loket</div>
                                <div class="font-bold text-blue-600 fluid-counter">{{ q.counter?.name ?? '-' }}</div>
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
/* ===== FLUID TYPOGRAPHY WITH CLAMP ===== */
/* Header */
.fluid-header {
    font-size: clamp(1.5rem, 2.5vw + 0.5rem, 2rem);
}

/* Section Title */
.fluid-section-title {
    font-size: clamp(1rem, 1.5vw + 0.25rem, 1.25rem);
}

/* Queue List Items */
.fluid-label {
    font-size: clamp(0.625rem, 0.9vw, 0.75rem);
}
.fluid-label-sm {
    font-size: clamp(0.75rem, 1vw, 0.875rem);
}
.fluid-queue-number {
    font-size: clamp(2rem, 4vw + 0.5rem, 3.5rem);
}
.fluid-counter {
    font-size: clamp(1.25rem, 2vw + 0.5rem, 2rem);
}

/* Overlay */
.fluid-overlay-title {
    font-size: clamp(1.5rem, 3vw + 0.5rem, 2.5rem);
}
.fluid-overlay-number {
    font-size: clamp(5rem, 10vw + 2rem, 10rem);
}
.fluid-overlay-counter {
    font-size: clamp(2rem, 4vw + 0.5rem, 3.5rem);
}

/* ===== ANIMATIONS ===== */
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
