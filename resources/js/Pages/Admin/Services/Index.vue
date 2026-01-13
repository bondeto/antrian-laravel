<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    services: Array,
    floors: Array,
});

const showModal = ref(false);
const editingItem = ref(null);

const form = useForm({
    name: '',
    code: '',
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
    form.code = item.code;
    form.floor_id = item.floor_id;
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(`/admin/services/${editingItem.value.id}`, {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post('/admin/services', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Hapus layanan ini? Nomor antrian terkait akan ikut terhapus.')) {
        form.delete(`/admin/services/${id}`);
    }
};
</script>

<template>
    <Layout>
        <Head title="Manajemen Layanan" />

        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manajemen Layanan</h2>
                <p class="text-slate-500 text-sm">Kelola kategori layanan antrian.</p>
            </div>
            <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20">
                + TAMBAH LAYANAN
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b">
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Kode</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Nama Layanan</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Lokasi</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in services" :key="s.id" class="border-b hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded font-black">{{ s.code }}</span>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-slate-700">{{ s.name }}</span>
                        </td>
                        <td class="p-4 text-slate-500 text-sm">{{ s.floor?.name }}</td>
                        <td class="p-4 text-right space-x-2">
                            <button @click="openEdit(s)" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase">Edit</button>
                            <button @click="destroy(s.id)" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                <div class="p-6 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase">{{ editingItem ? 'Edit Layanan' : 'Tambah Layanan' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-1">
                            <label class="block text-xs font-black text-slate-400 uppercase mb-1">Kode</label>
                            <input v-model="form.code" type="text" maxlength="2" class="w-full bg-slate-50 border-slate-200 rounded-xl uppercase text-center font-bold" required placeholder="A" />
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs font-black text-slate-400 uppercase mb-1">Nama Layanan</label>
                            <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl" required placeholder="Customer Service" />
                        </div>
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
