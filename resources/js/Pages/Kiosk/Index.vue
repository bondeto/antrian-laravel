<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import QRCode from 'qrcode';

const props = defineProps({
    floors: Array,
    ticketSettings: Object,
});

const page = usePage();

const form = useForm({
    service_id: null,
    photo: null,
});

// Camera state
const videoRef = ref(null);
const canvasRef = ref(null);
const capturedPhoto = ref(null);
const isCameraActive = ref(false);
const cameraError = ref(null);
const selectedService = ref(null);
const showCameraModal = ref(false);

// Flash message handling
const printModal = ref(false);
const printData = ref(null);
const ticketData = ref(null);
const qrCodeUrl = ref(null);

// Check if photo capture is enabled
const enablePhotoCapture = computed(() => props.ticketSettings?.enable_photo_capture || false);
const ticketMode = computed(() => props.ticketSettings?.ticket_mode || 'print');

// Start camera
const startCamera = async () => {
    try {
        cameraError.value = null;
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        });
        
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
            isCameraActive.value = true;
        }
    } catch (error) {
        console.error('Camera error:', error);
        cameraError.value = 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.';
    }
};

// Stop camera
const stopCamera = () => {
    if (videoRef.value?.srcObject) {
        const tracks = videoRef.value.srcObject.getTracks();
        tracks.forEach(track => track.stop());
        videoRef.value.srcObject = null;
    }
    isCameraActive.value = false;
};

// Capture photo from video
const capturePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;
    
    const canvas = canvasRef.value;
    const video = videoRef.value;
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    // Mirror the image for selfie-style capture
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0);
    
    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.8);
    stopCamera();
};

// Retake photo
const retakePhoto = () => {
    capturedPhoto.value = null;
    startCamera();
};

// Cancel photo capture modal
const cancelPhotoCapture = () => {
    stopCamera();
    capturedPhoto.value = null;
    selectedService.value = null;
    showCameraModal.value = false;
};

// Generate QR Code
const generateQRCode = async (token) => {
    try {
        qrCodeUrl.value = await QRCode.toDataURL(token, {
            width: 200,
            margin: 2,
            color: {
                dark: '#1e293b',
                light: '#ffffff'
            }
        });
    } catch (error) {
        console.error('QR Code generation failed:', error);
    }
};

// Submit the queue request
const submit = (service_id) => {
    // If photo capture is enabled, show camera modal first
    if (enablePhotoCapture.value && !capturedPhoto.value) {
        selectedService.value = service_id;
        showCameraModal.value = true;
        startCamera();
        return;
    }
    
    proceedSubmit(service_id);
};

// Proceed with form submission
const proceedSubmit = (service_id) => {
    form.service_id = service_id || selectedService.value;
    form.photo = capturedPhoto.value;
    
    form.post('/queue', {
        onSuccess: async (page) => {
            // Reset camera state
            showCameraModal.value = false;
            capturedPhoto.value = null;
            selectedService.value = null;
            
            // Handle flash data
            printData.value = page.props.flash?.success;
            ticketData.value = page.props.flash?.ticket;
            
            // Generate QR code for paperless mode
            if (ticketData.value?.barcode_token) {
                await generateQRCode(ticketData.value.barcode_token);
            }
            
            printModal.value = true;
            
            // Auto close after 8s for paperless, 5s for print
            const timeout = ticketMode.value === 'paperless' ? 12000 : 5000;
            setTimeout(() => {
                printModal.value = false;
                ticketData.value = null;
                qrCodeUrl.value = null;
            }, timeout);
        },
        onError: () => {
            showCameraModal.value = false;
            capturedPhoto.value = null;
        }
    });
};

// Confirm photo and submit
const confirmPhotoAndSubmit = () => {
    if (!capturedPhoto.value) {
        // Proceed without photo
        proceedSubmit(selectedService.value);
    } else {
        proceedSubmit(selectedService.value);
    }
};

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <Head title="Ambil Antrian" />
    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-blue-50 to-indigo-100 p-8 flex flex-col items-center justify-center">
        <h1 class="text-4xl font-black mb-2 text-slate-800 uppercase tracking-tight">Silakan Pilih Layanan</h1>
        <p class="text-slate-500 mb-8 text-sm">Pilih layanan yang Anda butuhkan untuk mendapatkan nomor antrian</p>

        <div v-for="floor in floors" :key="floor.id" class="mb-8 w-full max-w-4xl">
            <h2 class="text-2xl font-bold mb-4 text-slate-700 border-b border-slate-200 pb-2 flex items-center gap-2">
                <span class="bg-blue-100 text-blue-600 px-3 py-1 rounded-lg text-sm font-black">{{ floor.name }}</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <button 
                    v-for="service in floor.services" 
                    :key="service.id"
                    @click="submit(service.id)"
                    class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl hover:scale-[1.02] transition-all duration-300 border-l-8 border-blue-500 group flex flex-col items-center relative overflow-hidden"
                    :disabled="form.processing"
                >
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-blue-100 transition-colors"></div>
                    <div class="relative z-10">
                        <div class="text-6xl mb-4 group-hover:scale-110 transition-transform">🎫</div>
                        <div class="text-2xl font-black text-slate-800">{{ service.name }}</div>
                        <div class="text-slate-500 mt-2 bg-slate-100 px-3 py-1 rounded-lg text-sm font-bold">Kode: {{ service.code }}</div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Camera Modal -->
        <div v-if="showCameraModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-3xl shadow-2xl max-w-lg w-full mx-4 animate-bounce-in">
                <div class="text-center mb-4">
                    <div class="text-4xl mb-2">📸</div>
                    <h3 class="text-xl font-black text-slate-800">Foto Wajah Anda</h3>
                    <p class="text-slate-500 text-sm">Foto akan digunakan untuk verifikasi saat pemanggilan</p>
                </div>
                
                <!-- Camera View -->
                <div class="relative aspect-video bg-slate-900 rounded-2xl overflow-hidden mb-4">
                    <video 
                        ref="videoRef" 
                        v-show="!capturedPhoto && isCameraActive"
                        class="w-full h-full object-cover transform scale-x-[-1]"
                        autoplay 
                        playsinline
                    ></video>
                    
                    <!-- Captured Photo Preview -->
                    <img 
                        v-if="capturedPhoto" 
                        :src="capturedPhoto" 
                        class="w-full h-full object-cover"
                    />
                    
                    <!-- Camera Error -->
                    <div v-if="cameraError" class="absolute inset-0 flex items-center justify-center bg-slate-900 text-white p-4 text-center">
                        <div>
                            <div class="text-4xl mb-2">⚠️</div>
                            <p class="text-sm">{{ cameraError }}</p>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div v-if="!isCameraActive && !capturedPhoto && !cameraError" class="absolute inset-0 flex items-center justify-center bg-slate-900 text-white">
                        <div class="text-center">
                            <div class="text-4xl mb-2 animate-pulse">📷</div>
                            <p class="text-sm">Memuat kamera...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden Canvas for Capture -->
                <canvas ref="canvasRef" class="hidden"></canvas>
                
                <!-- Camera Controls -->
                <div class="flex gap-3">
                    <button 
                        v-if="!capturedPhoto && isCameraActive"
                        @click="capturePhoto"
                        class="flex-1 bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                    >
                        <span>📸</span> Ambil Foto
                    </button>
                    
                    <button 
                        v-if="capturedPhoto"
                        @click="retakePhoto"
                        class="flex-1 bg-amber-500 text-white py-4 rounded-xl font-bold hover:bg-amber-600 transition-colors flex items-center justify-center gap-2"
                    >
                        <span>🔄</span> Ulangi
                    </button>
                    
                    <button 
                        v-if="capturedPhoto"
                        @click="confirmPhotoAndSubmit"
                        class="flex-1 bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
                    >
                        <span>✅</span> Lanjutkan
                    </button>
                    
                    <button 
                        @click="cancelPhotoCapture"
                        class="bg-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold hover:bg-slate-300 transition-colors"
                    >
                        Batal
                    </button>
                </div>
                
                <!-- Skip Photo Option -->
                <button 
                    v-if="!capturedPhoto"
                    @click="confirmPhotoAndSubmit"
                    class="w-full mt-3 text-slate-500 text-sm hover:text-slate-700 underline"
                >
                    Lewati foto, lanjutkan tanpa foto
                </button>
            </div>
        </div>

        <!-- Print/Ticket Modal -->
        <div v-if="printModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white p-10 rounded-3xl shadow-2xl text-center animate-bounce-in max-w-sm w-full mx-4">
                <!-- Paperless Mode with QR -->
                <div v-if="ticketMode === 'paperless' && ticketData" class="space-y-6">
                    <div class="text-green-500 text-5xl">✅</div>
                    <div>
                        <p class="text-slate-500 text-sm uppercase tracking-widest font-bold mb-2">Nomor Antrian Anda</p>
                        <div class="text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 my-4">
                            {{ ticketData.full_number }}
                        </div>
                        <div class="text-slate-500 text-sm mb-4">{{ ticketData.service_name }}</div>
                    </div>
                    
                    <!-- QR Code -->
                    <div class="bg-slate-50 p-4 rounded-2xl inline-block">
                        <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="QR Code" class="mx-auto" />
                        <p class="text-[10px] text-slate-400 mt-2 uppercase tracking-widest">Scan untuk verifikasi</p>
                    </div>
                    
                    <div class="text-xs text-slate-400 mt-4">
                        📱 Simpan atau foto layar ini untuk referensi
                    </div>
                </div>
                
                <!-- Print Mode (Classic) -->
                <div v-else>
                    <div class="text-green-500 text-6xl mb-4">🖨️</div>
                    <h3 class="text-2xl font-bold mb-2">Nomor Antrian Anda</h3>
                    <div class="text-5xl font-black text-blue-600 my-6 bg-slate-50 p-4 rounded-xl">
                        {{ printData?.replace('Nomor Antrian: ', '') }}
                    </div>
                    <p class="text-slate-500">Silakan menunggu panggilan.</p>
                </div>
                
                <div class="mt-6 text-sm text-slate-400">Menutup otomatis...</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-bounce-in {
    animation: bounce-in 0.5s;
}
@keyframes bounce-in {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 1; }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}
</style>
