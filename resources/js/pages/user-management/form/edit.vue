<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Folder, Info } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
const page = usePage();

// Extract your application data
const application = page.props.application
const roles = ref<any[]>([]);
const office = ref<any[]>([]);
// ---------------------
// STATE
// ---------------------
const user = ref<any>({
    id: null,
    name: '',
    email: '',
    office_id: null,
    role_id: null,
});


// ---------------------
// BREADCRUMBS
// ---------------------
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applications', href: '/applicants/index' },
];

// get the data passed from Laravel
const updateUser = () => {
    router.put(`/user-management/${user.value.id}`, user.value);
    
};
// populate form when component loads
onMounted(() => {
    if (page.props.data) {
        user.value = { ...page.props.data };
    }

    if (page.props.roles) {
        roles.value = page.props.roles;
    }
    if (page.props.roles) {
        office.value = page.props.office;
    }
});
</script>




<template>

    <Head title="Chainsaw Purchase System" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 rounded-xl p-4 sm:grid-cols-3">


            <div class="box">
                <h2 class="title">Edit User Information</h2>

                <div class="grid gap-4">
                    <!-- Name -->
                    <div class="flex flex-col">
                        <label class="mb-1 font-medium">Name</label>
                        <InputText v-model="user.name" type="text" class="border rounded-md px-3 py-2" />
                    </div>
                    <div class="flex flex-col">
                        <label class="mb-1 font-medium">Password</label>
                        <Password required :tabindex="2" autocomplete="current-password"
                            v-model="user.password" placeholder="Password"  class="border rounded-md px-3 py-2w-full"/>
                    </div>

                    <!-- Username -->
                    <div class="flex flex-col">
                        <label class="mb-1 font-medium">Username</label>
                        <InputText v-model="user.uname" type="text" class="border rounded-md px-3 py-2" />
                    </div>

                    <!-- Office -->
                    <div class="flex flex-col">
                        <label class="mb-1 font-medium">Office</label>
                        <Select v-model="user.office_id" :options="office" optionLabel="office_title" optionValue="id"
                            placeholder="Select Role" class="w-full" />
                    </div>

                    <!-- Role -->
                    <div class="flex flex-col">
                        <label class="mb-1 font-medium">Role</label>
                        <Select v-model="user.role_id" :options="roles" optionLabel="role_title" optionValue="id"
                            placeholder="Select Role" class="w-full" />
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end mt-4">
                        <button @click="updateUser"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Update User
                        </button>
                    </div>
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