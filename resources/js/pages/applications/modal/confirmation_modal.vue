<script lang="ts" setup>
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from "vue";
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from "primevue/button";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from "primevue/toast";
const confirm = useConfirm();
const toast = useToast();
const isLoading = ref(false);

const props = defineProps({
    applicationId: {
        type: Number,
        required: true
    },
    role_id:{
      type: Number,
    },  
});

const requireConfirmation = () => {
    const statusType = props.role_id === 4 ? 6 : 4;
  
    confirm.require({
        group: 'headless',
        header: 'Are you sure?',
        message: 'Please confirm to proceed.',
        accept: () => {
            updateApplicationStatus(1); // update to status 1
        },
        reject: () => {
            toast.add({
                severity: 'error',
                summary: 'Cancelled',
                detail: 'Action cancelled.',
                life: 3000
            });
        }
    }); 
};
const updateApplicationStatus = async (status) => {
    isLoading.value = true;
    const status_type = props.role_id === 4 ? 6 : 3;


    try {
        const response = await axios.post(route('applications.updateStatus'), {
            id: props.applicationId,
            status: 1
        })

        toast.add({
            severity: 'success',
            summary: 'Status Updated',
            detail: response.data.message || 'Application status has been updated.',
            life: 3000
        });

          router.get(route('applications.pending_application'));

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
  <div class="w-full">
    <!-- Confirmation Dialog -->
    <ConfirmDialog group="headless">
      <template #container="{ message, acceptCallback, rejectCallback }">
        <div class="flex flex-col items-center p-8 bg-surface-0 dark:bg-surface-900 rounded">
          <div
            class="rounded-full bg-[#fff] inline-flex justify-center items-center h-25 w-25 -mt-20">
            <img src="../../../../images/denr_logo.png" class="w-25 h-25" />
          </div>

          <span class="font-bold text-2xl block mb-2 mt-6">
            {{ message.header }}
          </span>

          <p class="mb-0 text-center">
            {{ message.message }}
          </p>

          <div class="flex items-center gap-3 mt-6">
            <Button
              label="Confirm"
              @click="acceptCallback"
              style="background-color: #004D40;"
              :disabled="isLoading"
            />
            <Button
              label="Cancel"
              variant="outlined"
              @click="rejectCallback"
              :disabled="isLoading"
            />
          </div>
        </div>
      </template>
    </ConfirmDialog>

    <!-- Submit Button -->
    <Button
      @click="requireConfirmation"
      class="w-full bg-green-900 transition-colors hover:bg-green-500 text-white"
      :disabled="isLoading"
    ><LoaderCircle
        v-if="isLoading"
        class="h-4 w-4 animate-spin mr-2"
      />
      Submit Application
    </Button>
    <Toast />
  </div>
</template>