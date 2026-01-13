<script setup>
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    services: Array,
});

const reset = () => {
    if (confirm('Apakah Anda yakin ingin me-reset semua antrian hari ini?')) {
        router.post('/admin/reset');
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <div class="p-8 max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold">Admin Dashboard Antrian</h1>
            <button @click="reset" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                Reset Antrian
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
                <div class="text-gray-500 uppercase text-xs font-bold">Total Antrian</div>
                <div class="text-4xl font-bold">{{ stats.total }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-yellow-500">
                <div class="text-gray-500 uppercase text-xs font-bold">Menunggu</div>
                <div class="text-4xl font-bold">{{ stats.waiting }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-500">
                <div class="text-gray-500 uppercase text-xs font-bold">Selesai</div>
                <div class="text-4xl font-bold">{{ stats.served }}</div>
            </div>
        </div>

        <!-- Services -->
        <h2 class="text-2xl font-bold mb-4">Status per Layanan</h2>
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-4">Layanan</th>
                        <th class="p-4">Kode</th>
                        <th class="p-4">Terakhir</th>
                        <th class="p-4">Menunggu</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in services" :key="s.id" class="border-b last:border-0">
                        <td class="p-4 font-semibold">{{ s.name }}</td>
                        <td class="p-4">{{ s.code }}</td>
                        <td class="p-4">{{ s.last_number }}</td>
                        <td class="p-4">
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm font-bold">
                                {{ s.waiting_count }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
