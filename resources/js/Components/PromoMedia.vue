<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    settings: Object,
});

const currentSlide = ref(0);
let slideInterval = null;

const youtubeEmbedUrl = computed(() => {
    let id = props.settings.youtube_url;
    if (id.includes('v=')) {
        id = id.split('v=')[1].split('&')[0];
    } else if (id.includes('youtu.be/')) {
        id = id.split('youtu.be/')[1].split('?')[0];
    }
    return `https://www.youtube.com/embed/${id}?autoplay=1&mute=1&controls=0&loop=1&playlist=${id}`;
});

const startSlideshow = () => {
    if (props.settings.type === 'slideshow' && props.settings.slideshow_urls?.length > 1) {
        slideInterval = setInterval(() => {
            currentSlide.value = (currentSlide.value + 1) % props.settings.slideshow_urls.length;
        }, 5000); // 5 seconds per slide
    }
};

onMounted(() => {
    startSlideshow();
});

onUnmounted(() => {
    if (slideInterval) clearInterval(slideInterval);
});
</script>

<template>
    <div class="w-full h-full relative overflow-hidden bg-black">
        <!-- YouTube -->
        <iframe v-if="settings.type === 'youtube' && settings.youtube_url"
            class="w-full h-full absolute inset-0 border-0"
            :src="youtubeEmbedUrl"
            allow="autoplay; encrypted-media"
            allowfullscreen
        ></iframe>

        <!-- Local Video -->
        <video v-else-if="settings.type === 'local_video' && settings.local_video_url"
            class="w-full h-full object-cover"
            autoplay loop muted
        >
            <source :src="settings.local_video_url" type="video/mp4">
        </video>

        <!-- Slideshow -->
        <div v-else-if="settings.type === 'slideshow' && settings.slideshow_urls?.length" class="w-full h-full relative">
            <transition-group name="fade">
                <div v-for="(url, index) in settings.slideshow_urls" :key="url"
                    v-show="index === currentSlide"
                    class="absolute inset-0 w-full h-full"
                >
                    <img :src="url" class="w-full h-full object-cover" />
                </div>
            </transition-group>
        </div>

        <!-- Placeholder if nothing configured -->
        <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-600 gap-4">
            <div class="text-6xl">📽️</div>
            <div class="text-xl font-bold uppercase tracking-widest italic opacity-50">Konten Promosi</div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 1.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
