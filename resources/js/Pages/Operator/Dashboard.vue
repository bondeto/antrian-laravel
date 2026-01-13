<script setup>
import { Link, Head } from '@inertiajs/vue3';

const props = defineProps({
    floors: Array,
});
</script>

<template>
    <Head title="Pilih Loket" />
    <div class="min-h-screen bg-slate-50 flex flex-col items-center py-12 px-6">
        <div class="max-w-6xl w-full">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-black text-slate-900 tracking-tight uppercase">Pilih Loket Tugas</h1>
                <p class="text-slate-500 mt-2">Silakan pilih loket yang akan Anda operasikan hari ini.</p>
                <p v-if="!floors || floors.length === 0" class="text-red-500 font-bold mt-4 animate-bounce">
                    DEBUG: Data lantai/loket tidak ditemukan atau kosong.
                </p>
            </div>
            
            <div class="space-y-12">
                <div v-for="floor in floors" :key="floor.id" class="space-y-6">
                    <!-- Floor Header -->
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-600 text-white px-4 py-1 rounded-full font-black text-sm uppercase tracking-widest">
                            Lantai {{ floor.level }}
                        </div>
                        <div class="h-[2px] flex-1 bg-slate-200"></div>
                        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-tight">{{ floor.name }}</h2>
                    </div>

                    <!-- Counters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <Link 
                            v-for="counter in floor.counters" 
                            :key="counter.id" 
                            :href="`/operator/${counter.id}`"
                            class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-blue-500 transition-all hover:-translate-y-1 group relative overflow-hidden"
                        >
                            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <span class="text-6xl">🖥️</span>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-blue-50 group-hover:scale-110 transition-all">
                                    🖥️
                                </div>
                                <div class="font-black text-xl text-slate-800 leading-tight">{{ counter.name }}</div>
                                <div class="mt-4 flex items-center text-blue-600 font-bold text-sm">
                                    Mulai Bekerja
                                    <span class="ml-2 group-hover:translate-x-1 transition-transform">&rarr;</span>
                                </div>
                            </div>
                        </Link>

                        <!-- Empty state per floor if no counters -->
                        <div v-if="floor.counters.length === 0" class="col-span-full py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 font-medium">
                            Belum ada loket terdaftar di lantai ini.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="mt-16 text-center">
                <Link href="/" class="text-slate-400 hover:text-slate-600 font-bold uppercase text-xs tracking-widest transition-colors">
                    &larr; Kembali ke Beranda
                </Link>
            </div>
        </div>
    </div>
</template>
