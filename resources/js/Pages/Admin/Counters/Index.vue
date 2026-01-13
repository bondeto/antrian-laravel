<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    counters: Array,
    floors: Array,
});

const showModal = ref(false);
const editingItem = ref(null);

const form = useForm({
    name: '',
    floor_id: '',
});

const openCreate = () => {
    editingItem.value = null;
    form.reset();
    if (props.floors.length > 0) form.floor_id = props.floors[0].id;
    showModal.value = true;
};

const openEdit = (item) => {
    editingItem.value = item;
    form.name = item.name;
    form.floor_id = item.floor_id;
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(`/admin/counters/${editingItem.value.id}`, {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post('/admin/counters', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Hapus loket ini?')) {
        form.delete(`/admin/counters/${id}`);
    }
};
</script>

<template>
    <Layout>
        <Head title="Manajemen Loket" />

        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manajemen Loket</h2>
                <p class="text-slate-500 text-sm">Kelola titik pelayanan (Loket/Meja).</p>
            </div>
            <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20">
                + TAMBAH LOKET
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b">
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Nama Loket</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Lokasi Lantai</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in counters" :key="c.id" class="border-b hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <span class="font-bold text-slate-700">{{ c.name }}</span>
                        </td>
                        <td class="p-4 text-slate-500 text-sm">{{ c.floor?.name }}</td>
                        <td class="p-4 text-right space-x-2">
                            <button @click="openEdit(c)" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase">Edit</button>
                            <button @click="destroy(c.id)" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                <div class="p-6 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase">{{ editingItem ? 'Edit Loket' : 'Tambah Loket' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Nama Loket</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl" required placeholder="Loket 1" />
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Lokasi Lantai</label>
                        <select v-model="form.floor_id" class="w-full bg-slate-50 border-slate-200 rounded-xl" required>
                            <option v-for="f in floors" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                    </div>
                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white p-3 rounded-xl font-bold shadow-lg">
                            SIMPAN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Layout>
</template>
