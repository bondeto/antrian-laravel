<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    counters: Array,
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
        <div class="max-w-4xl w-full">
            <h1 class="text-3xl font-bold text-center mb-10 text-gray-800">PILIH LOKET ANDA</h1>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <Link 
                    v-for="counter in counters" 
                    :key="counter.id" 
                    :href="`/operator/${counter.id}`"
                    class="bg-white p-8 rounded-xl shadow hover:shadow-xl transition-all hover:-translate-y-1 group"
                >
                    <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🖥️</div>
                    <div class="font-bold text-xl text-gray-800">{{ counter.name }}</div>
                    <div class="text-gray-500">{{ counter.floor?.name || 'Unknown Floor' }}</div>
                    <div class="mt-4 text-blue-500 font-semibold group-hover:underline">Masuk &rarr;</div>
                </Link>
            </div>
        </div>
    </div>
</template>
