<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    users: Array,
});

const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'operator',
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    showModal.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(`/admin/users/${editingUser.value.id}`, {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post('/admin/users', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Yakin ingin menghapus user ini?')) {
        form.delete(`/admin/users/${id}`);
    }
};
</script>

<template>
    <Layout>
        <Head title="Manajemen User" />

        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Manajemen User</h2>
                <p class="text-slate-500 text-sm">Kelola akses administrator dan operator.</p>
            </div>
            <button @click="openCreate" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20 transition-all active:scale-95">
                + TAMBAH USER
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b">
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">User</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Email</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest">Role</th>
                        <th class="p-4 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-b hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                    {{ user.name[0] }}
                                </div>
                                <span class="font-bold text-slate-700">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-slate-500 text-sm">{{ user.email }}</td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter"
                                :class="user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                            >
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <button @click="openEdit(user)" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase">Edit</button>
                            <button @click="destroy(user.id)" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase" v-if="user.id !== $page.props.auth.user.id">Hapus</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase">{{ editingUser ? 'Edit User' : 'Tambah User' }}</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Nama Lengkap</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500 focus:border-blue-500" required />
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500 focus:border-blue-500" required />
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">{{ editingUser ? 'Password (kosongkan jika tidak ganti)' : 'Password' }}</label>
                        <input v-model="form.password" type="password" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500 focus:border-blue-500" :required="!editingUser" />
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase mb-1">Role</label>
                        <select v-model="form.role" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500 focus:border-blue-500">
                            <option value="operator">OPERATOR</option>
                            <option value="admin">ADMINISTRATOR</option>
                        </select>
                    </div>
                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white p-3 rounded-xl font-bold shadow-lg shadow-blue-500/20">
                            {{ editingUser ? 'SIMPAN PERUBAHAN' : 'TAMBAH USER' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Layout>
</template>
