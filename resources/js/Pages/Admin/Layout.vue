<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const navItems = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: '📊' },
    { name: 'Monitor Status', href: '/admin/counter-status', icon: '📡' },
    { name: 'Users & Roles', href: '/admin/users', icon: '👥' },
    { name: 'Lantai', href: '/admin/floors', icon: '🏢' },
    { name: 'Layanan', href: '/admin/services', icon: '🛠️' },
    { name: 'Loket', href: '/admin/counters', icon: '🖥️' },
    { name: 'Pengaturan', href: '/admin/settings', icon: '⚙️' },
];
</script>

<template>
    <div class="h-screen bg-slate-100 flex overflow-hidden font-sans">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0 shadow-xl overflow-y-auto">
            <div class="p-6 bg-blue-600 sticky top-0 z-10">
                <h1 class="text-xl font-black tracking-tighter italic">ADMIN PANEL</h1>
                <p class="text-xs text-blue-100 opacity-70">Antrian System v1.0</p>
            </div>
            
            <nav class="flex-1 p-4 space-y-1">
                <Link v-for="item in navItems" :key="item.name" :href="item.href"
                    class="flex items-center gap-3 p-3 rounded-xl transition-all duration-200"
                    :class="$page.url.startsWith(item.href) ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20 translate-x-1' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                >
                    <span class="text-lg">{{ item.icon }}</span>
                    <span class="font-bold text-sm">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-800 bg-slate-900/50">
                <div class="flex items-center gap-3 p-2 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center font-bold text-blue-400 border border-slate-600">
                        {{ user?.name[0] }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-sm font-bold truncate text-white">{{ user?.name }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-black">{{ user?.role }}</div>
                    </div>
                </div>
                <Link href="/logout" method="post" as="button" class="w-full p-2 text-[10px] font-black tracking-widest text-slate-500 hover:text-red-400 border border-slate-800 rounded-lg hover:border-red-400/30 transition-all uppercase">
                    Keluar Sistem
                </Link>
            </div>
        </aside>

        <!-- Main Content Container -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white border-b px-8 py-4 flex justify-between items-center shadow-sm z-30">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-xs font-medium uppercase tracking-widest">Admin</span>
                    <span class="text-slate-300">/</span>
                    <span class="font-black text-slate-800 uppercase tracking-widest text-xs">
                        {{ $page.url.split('/')[2]?.replace('-', ' ') || 'Dashboard' }}
                    </span>
                </div>
                
                <!-- Flash Message -->
                <transition name="fade">
                    <div v-if="flash.success" class="bg-emerald-500 text-white px-6 py-2 rounded-full text-xs font-black shadow-lg shadow-emerald-500/20 animate-bounce">
                        ✨ {{ flash.success }}
                    </div>
                </transition>

                <div class="text-slate-300 text-xs font-medium">
                    {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-8">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
