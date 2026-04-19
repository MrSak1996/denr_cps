<script lang="ts" setup>
import { defineExpose, ref } from 'vue';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Button from 'primevue/button';
const visible = ref(false);
const message = ref({
  header: '',
  message: '',
  showTextarea: false,
  showDropdown: false,
  offices: [] as { label: string; value: string }[],
});
const remarks = ref('');
const returnTo = ref<string | null>(null);
let onConfirmCallback: ((data?: { remarks?: string; returnTo?: string | number }) => void) | null = null;

const open = (options: {
  header: string;
  message: string;
  showTextarea?: boolean;
  showDropdown?: boolean;
  offices?: { label: string; value: string }[];
  onConfirm: (data?: { remarks?: string; returnTo?: string | number }) => void;
}) => {
  message.value = {
    header: options.header,
    message: options.message,
    showTextarea: options.showTextarea ?? false,
    showDropdown: options.showDropdown ?? false,
    offices: options.offices ?? [],
  };
  remarks.value = '';
  returnTo.value = null;
  onConfirmCallback = options.onConfirm;
  visible.value = true;
};

const accept = () => {
  if (onConfirmCallback) {
    onConfirmCallback({
      remarks: remarks.value,
      returnTo: returnTo.value,
    });
  }
  visible.value = false;
};

const reject = () => {
  visible.value = false;
};

defineExpose({ open });
</script>

<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
    <div class="bg-white rounded p-8 w-[28rem] relative">

      <!-- CENTERED LOGO -->
      <div class="flex justify-center">
        <img
          src="../../../../images/denr_logo.png"
          class="-mt-20 h-24 w-24 rounded-full border-4 border-white"
        />
      </div>

      <span class="mt-6 mb-2 block text-center text-2xl font-bold">
        {{ message.header }}
      </span>

      <p class="mb-4 text-center">
        {{ message.message }}
      </p>

      <Select
        v-if="message.showDropdown"
        v-model="returnTo"
        :options="message.offices"
        optionLabel="label"
        optionValue="value"
        placeholder="Select office to return"
        class="mb-4 w-full"
      />

      <Textarea
        v-if="message.showTextarea"
        v-model="remarks"
        rows="4"
        class="mb-4 w-full"
        placeholder="Enter reason here..."
        autoResize
      />

      <div class="mt-4 flex justify-end gap-2">
        <Button
          label="Confirm"
          :disabled="(message.showTextarea && !remarks) || (message.showDropdown && !returnTo)"
          @click="accept"
          style="background-color: #004d40"
        />
        <Button label="Cancel" variant="outlined" @click="reject" />
      </div>

    </div>
  </div>
</template>