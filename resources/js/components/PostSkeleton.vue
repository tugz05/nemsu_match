<script setup lang="ts">
defineProps<{
    count?: number;
}>();
</script>

<template>
    <article
        v-for="i in count ?? 1"
        :key="i"
        class="border-b border-gray-200 p-4 animate-fade-in"
    >
        <!-- User row -->
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 rounded-full flex-shrink-0 skeleton-shimmer" />
            <div class="flex-1 min-w-0 space-y-2">
                <div class="h-4 w-32 skeleton-shimmer rounded" />
                <div class="h-3 w-48 skeleton-shimmer rounded" />
            </div>
        </div>
        <!-- Content -->
        <div class="ml-13 space-y-2">
            <div class="h-3 w-full skeleton-shimmer rounded" />
            <div class="h-3 w-full skeleton-shimmer rounded" />
            <div class="h-3 w-2/3 skeleton-shimmer rounded" />
            <!-- Content block (some posts have images) -->
            <div class="h-24 w-full skeleton-shimmer rounded-xl mt-3" />
        </div>
        <!-- Actions -->
        <div class="ml-13 flex gap-6 pt-4 mt-3">
            <div class="h-5 w-12 skeleton-shimmer rounded" />
            <div class="h-5 w-12 skeleton-shimmer rounded" />
            <div class="h-5 w-12 skeleton-shimmer rounded" />
        </div>
    </article>
</template>

<style scoped>
.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        #e5e7eb 0%,
        #f3f4f6 20%,
        #e5e7eb 40%,
        #e5e7eb 100%
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.ml-13 {
    margin-left: 52px;
}

@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
