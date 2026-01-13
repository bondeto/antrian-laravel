<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    floor: Object,
    initialServing: Array,
    initialWaiting: Array,
});

const serving = ref(props.initialServing);
// We might not show waiting list in detail, just stats?
// Let's list active serving queues.

const lastCalled = ref(null);

const speak = (text) => {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        window.speechSynthesis.speak(utterance);
    }
};

const playChime = () => {
    // Ideally use an audio object. For now just TTS directly or simple beep if no file.
    // let audio = new Audio('/chime.mp3'); audio.play();
};

onMounted(() => {
    // Listen to changes
    window.Echo.channel(`monitor.floor.${props.floor.id}`)
        .listen('QueueCalled', (e) => {
            const queue = e.queue;
            lastCalled.value = queue;
            
            // Add to top of serving list
            serving.value.unshift(queue);
            if (serving.value.length > 5) serving.value.pop();

            // Speak
            // e.g. "Nomor Antrian A-001, Silakan ke Loket 1"
            const text = `Nomor Antrian ${queue.full_number.split('-').join(' ')}, Silakan ke ${queue.counter?.name || 'Loket'}`;
            playChime();
            setTimeout(() => speak(text), 1000);
        });
});
</script>

<template>
    <Head :title="`Monitor - ${floor.name}`" />
    <div class="min-h-screen bg-gray-900 text-white overflow-hidden flex flex-col">
        <!-- Header -->
        <header class="bg-blue-800 p-4 shadow-lg text-center z-10">
            <h1 class="text-3xl font-bold tracking-wider">{{ floor.name }} - ANTREAN KANTOR</h1>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex overflow-hidden">
            <!-- Left: Video / Media -->
            <div class="w-2/3 bg-black relative flex items-center justify-center">
                <!-- Placeholder for Video -->
                <p class="text-gray-500">Video Promosi / Company Profile</p>
                
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
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
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
                Selamat Datang di Kantor Kami. Budayakan Antre untuk Kenyamanan Bersama. Jam Operasional 08:00 - 16:00.
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-marquee {
    animation: marquee 20s linear infinite;
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
