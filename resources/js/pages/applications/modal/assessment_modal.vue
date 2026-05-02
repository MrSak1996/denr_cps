<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { usePage, router } from '@inertiajs/vue3';
import { LoaderCircle, CircleCheckBig } from 'lucide-vue-next';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';


const props = defineProps({
    status_id: Number,
    applicationId: {
        type: Number,
        required: true
    },

});


// ✅ Event emit for parent submission
const emit = defineEmits(['submit-assessments']);

const confirm = useConfirm();
const toast = useToast();
const isLoading = ref(false);

// Show confirmation modal


// const requireConfirmation = () => {
//     confirm.require({
//         group: 'headless',
//         header: 'Are you sure?',
//         message: 'Please confirm to submit all assessments.',
//         accept: () => {
//             emit('submit-assessments', props.applicationId);
//             toast.add({
//                 severity: 'info',
//                 summary: 'Submitting',
//                 detail: 'Submitting assessments...',
//                 life: 1500
//             });
//             // setTimeout(() => {
//             //     router.get(route('applications.pending_application'));

//             // }, 2000);
//         },
//         reject: () => {
//             toast.add({
//                 severity: 'warn',
//                 summary: 'Cancelled',
//                 detail: 'Submission cancelled',
//                 life: 1500
//             });
//         }
//     });
// };

const requireConfirmation = () => {
    const isRegionalDirector = [13, 24, 28].includes(props.status_id);

    confirm.require({
        group: 'headless',
        header: 'Are you sure?',
        message: isRegionalDirector
            ? 'Please confirm to approve this application.'
            : 'Please confirm to submit all assessments.',
        accept: () => {
            emit('submit-assessments', props.applicationId);
            toast.add({
                severity: 'info',
                summary: isRegionalDirector ? 'Approving' : 'Submitting',
                detail: isRegionalDirector
                    ? 'Approving application...'
                    : 'Submitting assessments...',
                life: 1500
            });
        },
        reject: () => {
            toast.add({
                severity: 'warn',
                summary: 'Cancelled',
                detail: isRegionalDirector
                    ? 'Approval cancelled'
                    : 'Submission cancelled',
                life: 1500
            });
        }
    });
};


// Optional method if you want to disable button while loading
const startLoading = () => (isLoading.value = true);
const stopLoading = () => (isLoading.value = false);


onMounted(() => {
})
</script>

<template>
    <div>

        <!-- Confirm Dialog -->
        <ConfirmDialog group="headless">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div class="flex flex-col items-center p-8 bg-white dark:bg-gray-900 rounded-lg shadow-lg">
                    <div class="rounded-full bg-white inline-flex justify-center items-center h-20 w-20 -mt-16">
                        <img src="../../../../images/denr_logo.png" class="h-16 w-16" />
                    </div>
                    <span class="font-bold text-xl block mb-2 mt-6">{{ message.header }}</span>
                    <p class="mb-0">{{ message.message }}</p>
                    <div class="flex items-center gap-3 mt-6">
                        <Button label="Submit" class="bg-green-900 text-white hover:bg-green-800"
                            @click="acceptCallback" />
                        <Button label="Cancel" variant="outlined" @click="rejectCallback" />
                    </div>
                </div>
            </template>
        </ConfirmDialog>

        <!-- Submit Button -->
        <div class="flex justify-center">
            <Button @click="requireConfirmation()" style="background-color: rgba(0,77,64,1) !important;"
                class="ml-auto px-4 py-2 flex items-center gap-2 rounded-md bg-green-900 text-white hover:bg-green-800"
                :disabled="isLoading">
                <LoaderCircle v-if="isLoading" class="h-4 w-4 animate-spin" />
                <CircleCheckBig />

                {{ [13, 24, 28].includes(props.status_id) ? 'Approve Application' : 'Submit Application' }}
            </Button>
        </div>
    </div>

    <!-- Toast notifications -->
    <Toast />
</template>