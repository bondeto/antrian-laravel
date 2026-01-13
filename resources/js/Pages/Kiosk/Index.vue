<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    floors: Array,
});

const form = useForm({
    service_id: null,
});

// Flash message handling
const printModal = ref(false);
const printData = ref(null);

const submit = (service_id) => {
    form.service_id = service_id;
    form.post('/queue', {
        onSuccess: (page) => {
            // Usually Inertia flash messages.
            // Let's grab the flash data from props if Inertia shares it globally or from page prop
            // Ideally we rely on page.props.flash
            printData.value = page.props.flash?.success; // Assuming string logic
            printModal.value = true;
            
            // Auto close after 3s
            setTimeout(() => {
                printModal.value = false;
            }, 5000);
        }
    });
};
</script>

<template>
    <Head title="Ambil Antrian" />
    <div class="min-h-screen bg-gray-100 p-8 flex flex-col items-center justify-center">
        <h1 class="text-4xl font-bold mb-8 text-gray-800">SILAKAN PILIH LAYANAN</h1>

        <div v-for="floor in floors" :key="floor.id" class="mb-8 w-full max-w-4xl">
            <h2 class="text-2xl font-semibold mb-4 text-gray-700 border-b pb-2">{{ floor.name }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <button 
                    v-for="service in floor.services" 
                    :key="service.id"
                    @click="submit(service.id)"
                    class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 border-l-8 border-blue-500 group flex flex-col items-center"
                    :disabled="form.processing"
                >
                    <div class="text-6xl mb-4 group-hover:text-blue-500 transition-colors">🎫</div>
                    <div class="text-2xl font-bold text-gray-800">{{ service.name }}</div>
                    <div class="text-gray-500 mt-2">Kode: {{ service.code }}</div>
                </button>
            </div>
        </div>

        <!-- Print Modal/Overlay -->
        <div v-if="printModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-10 rounded-xl shadow-2xl text-center animate-bounce-in max-w-sm w-full">
                <div class="text-green-500 text-6xl mb-4">🖨️</div>
                <h3 class="text-2xl font-bold mb-2">Nomor Antrian Anda</h3>
                <div class="text-5xl font-black text-blue-600 my-6 bg-gray-50 p-4 rounded">{{ printData?.replace('Nomor Antrian: ', '') }}</div>
                <p class="text-gray-500">Silakan menunggu panggilan.</p>
                <div class="mt-4 text-sm text-gray-400">Menutup otomatis...</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-bounce-in {
    animation: bounce-in 0.5s;
}
@keyframes bounce-in {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 1; }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
