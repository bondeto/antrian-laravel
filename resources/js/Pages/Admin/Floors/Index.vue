<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    floors: Array,
});

const showModal = ref(false);
const editingItem = ref(null);

const form = useForm({
    name: '',
    level: 1,
});

const openCreate = () => {
    editingItem.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.level = item.level;
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(`/admin/floors/${editingItem.value.id}`, {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post('/admin/floors', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Hapus lantai ini? Semua layanan dan loket di lantai ini akan ikut terhapus.')) {
        form.delete(`/admin/floors/${id}`);
    }
};
</script>

<template>
    <Layout>
        <Head title="Manajemen Lantai" />

        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manajemen Lantai</h2>
                <p class="text-slate-500 text-sm">Atur lokasi lantai gedung.</p>
            </div>
            <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20">
                + TAMBAH LANTAI
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b">
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Nama Lantai</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Level</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="floor in floors" :key="floor.id" class="border-b hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <span class="font-bold text-slate-700">{{ floor.name }}</span>
                        </td>
                        <td class="p-4 text-slate-500 text-sm">{{ floor.level }}</td>
                        <td class="p-4 text-right space-x-2">
                            <button @click="openEdit(floor)" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase">Edit</button>
                            <button @click="destroy(floor.id)" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                <div class="p-6 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase">{{ editingItem ? 'Edit Lantai' : 'Tambah Lantai' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Nama Lantai</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" required placeholder="Lantai 1" />
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Level (Urutan)</label>
                        <input v-model="form.level" type="number" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" required />
                    </div>
                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white p-3 rounded-xl font-bold shadow-lg">
                            {{ editingItem ? 'SIMPAN PERUBAHAN' : 'SIMPAN' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Layout>
</template>
