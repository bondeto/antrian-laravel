<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    settings: Object,
});

const currentSlide = ref(0);
let slideInterval = null;
const player = ref(null);
const playerReady = ref(false);

const videoId = computed(() => {
    let id = props.settings.youtube_url;
    if (!id) return '';
    if (id.includes('v=')) {
        id = id.split('v=')[1].split('&')[0];
    } else if (id.includes('youtu.be/')) {
        id = id.split('youtu.be/')[1].split('?')[0];
    }
    return id;
});

const startSlideshow = () => {
    if (props.settings.type === 'slideshow' && props.settings.slideshow_urls?.length > 1) {
        slideInterval = setInterval(() => {
            currentSlide.value = (currentSlide.value + 1) % props.settings.slideshow_urls.length;
        }, 5000);
    }
};

const initYouTubeAPI = () => {
    if (window.YT) {
        createPlayer();
        return;
    }
    
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    window.onYouTubeIframeAPIReady = () => {
        createPlayer();
    };
};

const createPlayer = () => {
    if (!videoId.value) return;
    
    player.value = new window.YT.Player('youtube-player', {
        height: '100%',
        width: '100%',
        videoId: videoId.value,
        playerVars: {
            'autoplay': 1,
            'mute': 1,
            'controls': 0,
            'loop': 1,
            'playlist': videoId.value,
            'modestbranding': 1,
            'showinfo': 0,
            'rel': 0
        },
        events: {
            'onReady': (event) => {
                event.target.playVideo();
                playerReady.value = true;
            },
            'onStateChange': (event) => {
                // Ensure loop works if playlist param fails
                if (event.data === window.YT.PlayerState.ENDED) {
                    player.value.playVideo();
                }
            }
        }
    });
};

onMounted(() => {
    if (props.settings.type === 'youtube') {
        initYouTubeAPI();
    } else if (props.settings.type === 'slideshow') {
        startSlideshow();
    }
});

onUnmounted(() => {
    if (slideInterval) clearInterval(slideInterval);
    if (player.value && player.value.destroy) {
        player.value.destroy();
    }
});
</script>

<template>
    <div class="w-full h-full relative overflow-hidden bg-black">
        <!-- YouTube with IFrame API -->
        <div v-show="settings.type === 'youtube' && videoId" class="w-full h-full pointer-events-none">
            <div id="youtube-player"></div>
            <!-- Overlay to prevent interaction if needed -->
            <div class="absolute inset-0 z-10"></div>
        </div>

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
#youtube-player {
    width: 100%;
    height: 100%;
    transform: scale(1.35); /* Zoom slightly to hide black bars/info if needed */
}
</style>
