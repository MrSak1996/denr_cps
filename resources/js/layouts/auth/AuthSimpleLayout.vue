<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';
import bg_cover from '../../../images/wall.png';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-screen flex items-center justify-center overflow-hidden bg-center bg-cover bg-no-repeat px-6 py-10"
        :style="{ backgroundImage: `url(${bg_cover})` }"
    >
        <!-- Overlay for blue-green tone -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950/70 via-emerald-950/40 to-cyan-900/60"></div>

        <!-- Content -->
        <div class="relative z-10 w-full max-w-7xl">
            <div class="flex flex-col items-center gap-8">

                <!-- Logo -->
                <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium text-white">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 backdrop-blur">
                        <AppLogoIcon class="size-8 fill-current text-white" />
                    </div>

                    <span class="sr-only">{{ title }}</span>
                </Link>

                <!-- Title -->
                <div class="text-center text-white space-y-2">
                    <h1 class="text-2xl font-semibold">{{ title }}</h1>
                    <p class="text-sm text-slate-200">{{ description }}</p>
                </div>

                <!-- Form Slot -->
                <slot />

            </div>
        </div>
    </div>
</template>