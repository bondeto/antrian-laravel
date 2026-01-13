<script setup>
import { router } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    counter: Object,
    currentQueue: Object,
    stats: Object,
    flash: Object,
});

const callNext = () => {
    router.post(`/operator/${props.counter.id}/call`);
};

const recall = () => {
    if (props.currentQueue) {
        router.post(`/operator/queue/${props.currentQueue.id}/recall`);
    }
};

const served = () => {
    if (props.currentQueue) {
        router.post(`/operator/queue/${props.currentQueue.id}/served`);
    }
};

const skip = () => {
    if (props.currentQueue) {
        router.post(`/operator/queue/${props.currentQueue.id}/skip`);
    }
};

const exit = () => {
    router.get('/operator');
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow p-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Operator Panel - {{ counter.name }}</h1>
                <p class="text-sm text-gray-500">{{ counter.floor.name }}</p>
            </div>
            <button @click="exit" class="text-red-500 hover:text-red-700 font-semibold">Exit</button>
        </header>

        <main class="flex-1 p-6 container mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left: Current Action -->
            <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center justify-center text-center">
                <h2 class="text-gray-400 font-bold uppercase tracking-widest mb-4">Sedang Melayani</h2>
                
                <div v-if="currentQueue" class="animate-pulse-once">
                    <div class="text-xs font-bold bg-blue-100 text-blue-800 px-3 py-1 rounded-full mb-2 inline-block">{{ currentQueue.service.name }}</div>
                    <div class="text-8xl font-black text-gray-900 mb-6 font-mono">{{ currentQueue.full_number }}</div>
                    <div class="text-green-500 font-bold text-xl mb-8">Status: {{ currentQueue.status.toUpperCase() }}</div>

                    <div class="grid grid-cols-2 gap-4 w-full max-w-md">
                        <button @click="recall" class="bg-yellow-500 text-white py-4 rounded-lg font-bold hover:bg-yellow-600 transition shadow">
                            📢 Panggil Ulang
                        </button>
                        <button @click="served" class="bg-green-600 text-white py-4 rounded-lg font-bold hover:bg-green-700 transition shadow">
                            ✅ Selesai
                        </button>
                        <button @click="skip" class="bg-red-500 text-white py-4 rounded-lg font-bold hover:bg-red-600 transition shadow col-span-2">
                            🚫 Skip / Lewati
                        </button>
                    </div>
                </div>

                <div v-else class="py-20 text-gray-400">
                    <div class="text-6xl mb-4">☕</div>
                    <p class="text-xl">Tidak ada antrian aktif.</p>
                    <p class="text-sm">Silakan panggil nomor berikutnya.</p>
                </div>
            </div>

            <!-- Right: Controls & Stats -->
            <div class="flex flex-col gap-6">
                <!-- Main Call Button -->
                <div class="bg-blue-600 p-8 rounded-xl shadow-lg text-white text-center hover:bg-blue-700 transition cursor-pointer" @click="callNext">
                    <div class="text-6xl mb-2">📞</div>
                    <h2 class="text-3xl font-bold">PANGGIL BERIKUTNYA</h2>
                    <p class="mt-2 opacity-80">Klik untuk memanggil antrian waiting terlama</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-6 rounded-xl shadow text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ stats.waiting }}</div>
                        <div class="text-gray-500 text-sm">Menunggu</div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow text-center">
                        <div class="text-3xl font-bold text-green-600">{{ stats.served }}</div>
                        <div class="text-gray-500 text-sm">Selesai (Counter Ini)</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
.animate-pulse-once {
    animation: bounce-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
</style>
