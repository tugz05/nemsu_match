<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted } from 'vue';
import { X, Image as ImageIcon, Camera } from 'lucide-vue-next';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    create: [payload: { content: string; images: File[] }];
}>();

const content = ref('');
const images = ref<File[]>([]);
const imagePreviews = ref<string[]>([]);
const showCameraView = ref(false);
const cameraStream = ref<MediaStream | null>(null);
const cameraError = ref<string | null>(null);
const cameraLoading = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);

const maxImages = 10;

watch(() => props.open, (open) => {
    if (!open) {
        closeCamera();
        content.value = '';
        images.value = [];
        imagePreviews.value = [];
    }
});

function removeImage(index: number) {
    images.value.splice(index, 1);
    imagePreviews.value.splice(index, 1);
}

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    const remaining = maxImages - images.value.length;
    const files = Array.from(target.files).slice(0, remaining);
    files.forEach((file) => {
        images.value.push(file);
        const reader = new FileReader();
        reader.onload = (e) => imagePreviews.value.push(e.target?.result as string);
        reader.readAsDataURL(file);
    });
    target.value = '';
}

async function openCamera() {
    if (images.value.length >= maxImages) return;
    cameraError.value = null;
    cameraLoading.value = true;
    showCameraView.value = true;
    try {
        let stream: MediaStream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
        } catch {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        }
        cameraStream.value = stream;
        await nextTick();
        if (videoRef.value && stream) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
        }
    } catch (e: unknown) {
        cameraError.value = (e as Error)?.message || 'Camera access denied';
    } finally {
        cameraLoading.value = false;
    }
}

function closeCamera() {
    cameraStream.value?.getTracks().forEach((t) => t.stop());
    cameraStream.value = null;
    if (videoRef.value) videoRef.value.srcObject = null;
    showCameraView.value = false;
    cameraError.value = null;
}

function capturePhoto() {
    if (!videoRef.value || !cameraStream.value || images.value.length >= maxImages) return;
    const video = videoRef.value;
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.drawImage(video, 0, 0);
    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const file = new File([blob], `camera-${Date.now()}.jpg`, { type: 'image/jpeg' });
            images.value.push(file);
            const reader = new FileReader();
            reader.onload = (e) => imagePreviews.value.push(e.target?.result as string);
            reader.readAsDataURL(file);
            closeCamera();
        },
        'image/jpeg',
        0.9
    );
}

function handleClose() {
    emit('close');
}

function handleCreate() {
    if (!content.value.trim()) return;
    emit('create', { content: content.value.trim(), images: [...images.value] });
}

onUnmounted(closeCamera);
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 bg-black/50 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="handleClose"
    >
        <div class="bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl animate-slide-up max-h-[90vh] flex flex-col mb-20 sm:mb-0">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Create Post</h3>
                <button type="button" @click="handleClose" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <X class="w-5 h-5 text-gray-500" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="showCameraView" class="space-y-3">
                    <div class="relative aspect-[4/3] max-h-[280px] w-full rounded-2xl overflow-hidden bg-black">
                        <video ref="videoRef" autoplay playsinline muted class="w-full h-full object-cover" />
                        <div v-if="cameraLoading" class="absolute inset-0 flex items-center justify-center bg-black/50">
                            <div class="w-10 h-10 border-4 border-white border-t-transparent rounded-full animate-spin" />
                        </div>
                        <p v-if="cameraError" class="absolute inset-0 flex items-center justify-center p-4 text-center text-white text-sm bg-black/70">
                            {{ cameraError }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="closeCamera" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium text-sm">
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="capturePhoto"
                            :disabled="cameraLoading || !!cameraError"
                            class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-medium text-sm disabled:opacity-50"
                        >
                            Capture photo
                        </button>
                    </div>
                </div>
                <template v-else>
                    <textarea
                        v-model="content"
                        placeholder="What's happening at NEMSU? Share your thoughts..."
                        class="w-full min-h-[120px] text-base text-gray-900 placeholder-gray-400 outline-none resize-none"
                        maxlength="1000"
                    />
                    <div v-if="imagePreviews.length" class="mt-4 flex flex-wrap gap-2">
                        <div
                            v-for="(preview, idx) in imagePreviews"
                            :key="idx"
                            class="relative w-20 h-20 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0"
                        >
                            <img :src="preview" alt="Preview" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="removeImage(idx)"
                                class="absolute top-0.5 right-0.5 p-1 bg-gray-900/70 hover:bg-gray-900 rounded-full transition-colors"
                            >
                                <X class="w-3.5 h-3.5 text-white" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-1">
                        <template v-if="images.length < maxImages">
                            <label class="cursor-pointer p-2 hover:bg-blue-50 rounded-full transition-colors">
                                <ImageIcon class="w-5 h-5 text-blue-600" />
                                <input type="file" accept="image/*" multiple class="hidden" @change="handleImageUpload" />
                            </label>
                            <button
                                v-if="!showCameraView"
                                type="button"
                                @click="openCamera"
                                class="p-2 hover:bg-blue-50 rounded-full transition-colors"
                                title="Take a picture"
                            >
                                <Camera class="w-5 h-5 text-blue-600" />
                            </button>
                        </template>
                        <span v-else class="text-xs text-gray-500">Up to {{ maxImages }} photos</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ content.length }}/1000</span>
                </div>
                <button
                    type="button"
                    @click="handleCreate"
                    :disabled="!content.trim()"
                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white font-semibold py-3 rounded-full shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Post
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slide-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}
</style>
