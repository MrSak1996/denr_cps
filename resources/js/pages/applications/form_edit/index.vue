<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Folder, Info } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import individial_form_edit from './individual_edit.vue';
import company_form_edit from './company_edit.vue';
import Tag from 'primevue/tag';
const page = usePage();

// Extract your application data
const application = page.props.application
// ---------------------
// STATE
// ---------------------
const checked = ref<boolean | null>(null);
const hasSelected = ref(false);

// ---------------------
// BREADCRUMBS
// ---------------------
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applicants/index' },
];

// ---------------------
// UTILITY: Get URL Parameter Safely
// ---------------------
const getApplicationTypeFromUrl = (): string | null => {
    const params = new URLSearchParams(window.location.search);
    return params.get('type');
};

// ---------------------
// UI REACTION: When user toggles button
// ---------------------
watch(checked, (value) => {
    if (value !== null) {
        hasSelected.value = true;
    }
});

const showIndividualApplicant = async () => {
    checked.value = false;
}

// ---------------------
// AUTO-SELECT BASED ON URL
// ---------------------
onMounted(() => {
    const type = page.props.application.application_type;
    if (type) {
        hasSelected.value = true;

        if (type === 'Individual') {
            checked.value = false;
        } else {
            checked.value = true;
        }
    }
});
</script>




<template>

    <Head title="Chainsaw Purchase System" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="flex flex-col gap-6 rounded-xl p-4 sm:grid-cols-3">
            <div class="flex items-center">
                <div class="flex items-center gap-2">
                    <Info class="h-5 w-5" />
                    <h1 class="text-xl font-semibold">
                        Application Status:
                    </h1>
                </div>

                <Tag severity="danger">
                    {{ application.status_title }}
                </Tag>

            </div>

            <div class="box">

                

                <!-- SHOW FORM AFTER SELECTION -->
                <div v-if="application.application_type == 'Individual'">
                    <individial_form_edit />
                </div>
                <div v-else>
                    <company_form_edit />
                </div>

            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.box {
    background-color: #fff;
    border-top: 4px solid #00943a;
    margin-bottom: 20px;
    padding: 20px;
    -moz-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -o-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    -webkit-box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}

.box .title {
    border-bottom: 1px solid #e0e0e0;
    color: #432c0b !important;
    font-weight: bold;
    margin-bottom: 20px;
    margin-top: 0;
    padding-bottom: 10px;
    padding-top: 0;
    text-transform: uppercase;
    font-size: 10pt;
}

/* Base style for ToggleButton - Green (unchecked/default state) */
/* Default state - Green */
/* Base style for ToggleButton - Green (unchecked/default state) */
.p-togglebutton {
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    background-color: #22c55e;
    /* green-500 */
    color: white;
    transition: background-color 0.3s ease, filter 0.3s ease;
}

/* Hover effect */
.p-togglebutton:hover {
    filter: brightness(1.1);
}

/* Checked state - Darker green */
.p-togglebutton.p-togglebutton-checked {
    background-color: #15803d !important;
    /* green-700 */
    border-color: #166534;
    color: rgb(0, 0, 0);
}

/* Fix inner white background */
.p-togglebutton.p-togglebutton-checked .p-togglebutton-content {
    background-color: #15803d !important;
    box-shadow: none;
    color: white !important;
}

/* Ensure label and icon are white in all states */
.p-togglebutton .p-togglebutton-icon,
.p-togglebutton .p-togglebutton-label {
    color: white !important;
}
</style>