<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
<!-- /* background: linear-gradient(90deg, rgba(42, 123, 155, 1) 0%, rgba(159, 237, 191, 1) 50%, rgba(87, 193, 225, 1) 100%, rgba(237, 221, 83, 1) 100%);"/> */ -->

    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" style=" background: #032282; background: linear-gradient(90deg,rgba(3, 34, 130, 1) 0%, rgba(87, 199, 160, 1) 50%, rgba(5, 102, 26, 1) 100%);color:#fff;" />

<AppContent >
            <slot />
        </AppContent>
    </AppShell>
</template>
