<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    media_type: props.settings.media_type,
    youtube_url: props.settings.youtube_url,
    local_video_url: props.settings.local_video_url,
    slideshow_urls: props.settings.slideshow_urls || [],
    news_ticker: props.settings.news_ticker,
    skip_handling: props.settings.skip_handling || 'hangus',
    monitor_header: props.settings.monitor_header || 'Pusat Antrian',
    monitor_subheader: props.settings.monitor_subheader || 'Lobby Utama',
});

const newImageUrl = ref('');

const addImageUrl = () => {
    if (newImageUrl.value && !form.slideshow_urls.includes(newImageUrl.value)) {
        form.slideshow_urls.push(newImageUrl.value);
        newImageUrl.value = '';
    }
};

const removeImageUrl = (index) => {
    form.slideshow_urls.splice(index, 1);
};

const submit = () => {
    form.post('/admin/settings');
};
</script>

<template>
    <Layout>
        <Head title="Pengaturan Media" />

        <div class="mb-8">
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Pengaturan Media</h2>
            <p class="text-slate-500 text-sm">Atur konten promosi yang muncul di layar monitor.</p>
        </div>

        <form @submit.prevent="submit" class="max-w-4xl space-y-6">
            <!-- Media Type Selection -->
            <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Pilih Jenis Media</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="cursor-pointer group">
                        <input type="radio" v-model="form.media_type" value="youtube" class="hidden" />
                        <div class="p-4 border-2 rounded-xl text-center transition-all" :class="form.media_type === 'youtube' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 hover:border-slate-200'">
                            <span class="text-2xl mb-2 block">📺</span>
                            <span class="font-bold text-slate-800 group-hover:text-blue-600">YouTube Video</span>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" v-model="form.media_type" value="local_video" class="hidden" />
                        <div class="p-4 border-2 rounded-xl text-center transition-all" :class="form.media_type === 'local_video' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 hover:border-slate-200'">
                            <span class="text-2xl mb-2 block">📂</span>
                            <span class="font-bold text-slate-800 group-hover:text-blue-600">Video Lokal</span>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" v-model="form.media_type" value="slideshow" class="hidden" />
                        <div class="p-4 border-2 rounded-xl text-center transition-all" :class="form.media_type === 'slideshow' ? 'border-blue-600 bg-blue-50' : 'border-slate-100 hover:border-slate-200'">
                            <span class="text-2xl mb-2 block">🖼️</span>
                            <span class="font-bold text-slate-800 group-hover:text-blue-600">Slide Gambar</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- YouTube URL -->
            <div v-if="form.media_type === 'youtube'" class="bg-white p-6 rounded-2xl border shadow-sm animate-fade-in">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">YouTube URL / Video ID</label>
                <input v-model="form.youtube_url" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="https://www.youtube.com/watch?v=..." />
                <p class="mt-2 text-[10px] text-slate-400">Contoh: https://www.youtube.com/watch?v=dQw4w9WgXcQ atau cukup ID: dQw4w9WgXcQ</p>
            </div>

            <!-- Local Video -->
            <div v-if="form.media_type === 'local_video'" class="bg-white p-6 rounded-2xl border shadow-sm animate-fade-in">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Path File / URL Video Lokal</label>
                <input v-model="form.local_video_url" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="/videos/promo.mp4" />
                <p class="mt-2 text-[10px] text-slate-400">Pastikan file berada di folder public/ atau dapat diakses via URL.</p>
            </div>

            <!-- Slideshow -->
            <div v-if="form.media_type === 'slideshow'" class="bg-white p-6 rounded-2xl border shadow-sm animate-fade-in space-y-4">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Daftar Gambar Slide</label>
                
                <div class="flex gap-2">
                    <input v-model="newImageUrl" type="text" class="flex-1 bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="https://example.com/image.jpg" @keyup.enter="addImageUrl" />
                    <button type="button" @click="addImageUrl" class="bg-blue-600 text-white px-6 rounded-xl font-bold">Tambah</button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div v-for="(url, index) in form.slideshow_urls" :key="index" class="relative group aspect-video bg-slate-100 rounded-lg overflow-hidden border">
                        <img :src="url" class="w-full h-full object-cover" />
                        <button type="button" @click="removeImageUrl(index)" class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                    </div>
                </div>
            </div>

            <!-- News Ticker -->
            <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">News Ticker (Teks Berjalan)</label>
                <textarea v-model="form.news_ticker" rows="3" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="Masukkan pengumuman atau informasi yang akan ditampilkan di bawah layar..."></textarea>
                <p class="text-[10px] text-slate-400">Teks ini akan muncul di footer monitor antrian.</p>
            </div>

            <!-- Monitor Display Customization -->
            <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Tampilan Header Monitor</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Header Utama</label>
                        <input v-model="form.monitor_header" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="Pusat Antrian" />
                        <p class="text-[10px] text-slate-400 mt-1">Nama utama yang muncul di header monitor</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Sub-Header</label>
                        <input v-model="form.monitor_subheader" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500" placeholder="Lobby Utama" />
                        <p class="text-[10px] text-slate-400 mt-1">Keterangan tambahan di bawah header</p>
                    </div>
                </div>
            </div>

            <!-- Skip Ticket Handling -->
            <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Penanganan Tiket "Skip" (Lewati)</label>
                <select v-model="form.skip_handling" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-blue-500">
                    <option value="hangus">Hangus (Tiket Selesai/Dihapus)</option>
                    <option value="belakang">Pindah ke Paling Belakang</option>
                    <option value="pindah_1">Lewati 1 Permohonan (Urutan Berikutnya + 1)</option>
                    <option value="pindah_2">Lewati 2 Permohonan (Urutan Berikutnya + 2)</option>
                </select>
                <p class="text-[10px] text-slate-400 italic">Tentukan apa yang terjadi pada tiket saat operator menekan tombol "Skip".</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-black shadow-xl shadow-blue-500/20 active:scale-95 transition-all">
                    SIMPAN PENGATURAN
                </button>
            </div>
        </form>
    </Layout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
