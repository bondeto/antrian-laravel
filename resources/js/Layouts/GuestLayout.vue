<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50 relative overflow-hidden">
        <!-- Decoration Background -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
            <div class="absolute -top-[30%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-100/40 blur-3xl"></div>
            <div class="absolute top-[40%] -right-[10%] w-[60%] h-[60%] rounded-full bg-indigo-100/40 blur-3xl"></div>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-2xl overflow-hidden sm:rounded-3xl border border-gray-100 relative z-10 transition-all duration-300 hover:shadow-blue-900/5">
            <div class="flex justify-center mb-8">
                <Link href="/">
                    <img v-if="$page.props.app_settings?.logo_url" :src="$page.props.app_settings.logo_url" alt="Logo" class="w-20 h-20 object-contain drop-shadow-md" />
                    <ApplicationLogo v-else class="w-20 h-20 fill-current text-blue-600 scale-110" />
                </Link>
            </div>
            
            <slot />
        </div>

        <div class="mt-8 text-center text-xs text-gray-400 relative z-10">
            &copy; {{ new Date().getFullYear() }} Queue System. Professional & Secure.
        </div>
    </div>
</template>
