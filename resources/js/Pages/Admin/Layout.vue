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
    <div class="min-h-screen bg-slate-50 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex flex-col shadow-2xl z-20">
            <div class="p-6 bg-blue-600">
                <h1 class="text-xl font-black tracking-tighter italic">ADMIN PANEL</h1>
                <p class="text-xs text-blue-100 opacity-70">Antrian System v1.0</p>
            </div>
            
            <nav class="flex-1 p-4 space-y-1">
                <Link v-for="item in navItems" :key="item.name" :href="item.href"
                    class="flex items-center gap-3 p-3 rounded-lg transition-all"
                    :class="$page.url.startsWith(item.href) ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                >
                    <span>{{ item.icon }}</span>
                    <span class="font-medium">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-3 p-2">
                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center font-bold text-blue-400">
                        {{ user?.name[0] }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-sm font-bold truncate">{{ user?.name }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-black">{{ user?.role }}</div>
                    </div>
                </div>
                <Link href="/logout" method="post" as="button" class="w-full mt-4 p-2 text-xs font-bold text-slate-500 hover:text-red-400 border border-slate-800 rounded-md transition-colors">
                    LOGOUT
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Top Bar -->
            <header class="bg-white border-b p-4 flex justify-between items-center shadow-sm sticky top-0 z-10">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400">Admin</span>
                    <span class="text-slate-300">/</span>
                    <span class="font-bold text-slate-800 uppercase tracking-widest text-xs">
                        {{ $page.url.split('/')[2] }}
                    </span>
                </div>
                
                <div v-if="flash.success" class="absolute left-1/2 -translate-x-1/2 bg-green-500 text-white px-4 py-1.5 rounded-full text-xs font-bold shadow-lg animate-bounce">
                    {{ flash.success }}
                </div>
            </header>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <slot />
            </div>
        </main>
    </div>
</template>
