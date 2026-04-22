<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSplitLayout.vue';
import AuthLayoutTemplate from '@/layouts/auth/AuthSimpleLayout.vue'; 

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayoutTemplate :title="title" :description="description">
        <slot />
    </AuthLayoutTemplate>
</template>
