<script setup lang="ts">
import { reactive } from 'vue'
import { Button } from '@/components/ui/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import FloatLabel from 'primevue/floatlabel'
import Fieldset from 'primevue/fieldset'

const emit = defineEmits(['next', 'back'])

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  application_type: String,
  currentStep: Number,
  isProcessing: {
    type: Boolean,
    default: false
  }
})

const payment = reactive({
  application_attachment_id: 0,
  official_receipt: '',
  permit_fee: 500,
  or_copy: null as File | null,
  date_of_payment: new Date().toISOString().slice(0, 10),
  remarks: ''
})

const handleORFileUpload = (event: Event, field: 'or_copy') => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  payment[field] = file
}

/* ✅ MISSING METHOD (FIXED) */
const submitStep = () => {


  if (props.isProcessing) return

  emit('next', {
    ...props.form,
    application_id: props.form.application_id,
    application_type: props.application_type
  })
}
</script>

<template>
  <div class="space-y-6">
    <Fieldset legend="Payment of Application Fee">

      <div :class="{ 'pointer-events-none opacity-60': isProcessing }">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

          <!-- Application No -->
          <FloatLabel>
            <InputText v-model="props.form.application_no" disabled class="w-full font-bold" />
            <label>Application No.</label>
          </FloatLabel>

          <!-- OR Number -->
          <FloatLabel>
            <InputText v-model="payment.official_receipt" class="w-full" />
            <label>O.R No.</label>
          </FloatLabel>

          <!-- Fee -->
          <FloatLabel>
            <InputNumber v-model="payment.permit_fee" class="w-full" />
            <label>Permit Fee</label>
          </FloatLabel>

          <!-- File Upload -->
          <div class="md:col-span-2">
            <label class="text-sm font-medium">
              Upload Official Receipt
            </label>

            <input type="file" accept=".jpg,.jpeg,.pdf"
              class="mt-2 w-full cursor-pointer border border-dashed p-3 rounded"
              @change="(e) => handleORFileUpload(e, 'or_copy')" />
          </div>

        </div>
      </div>
    </Fieldset>

    <!-- Actions -->
    <div :class="[
      'w-full pt-6',
      currentStep > 1 ? 'grid grid-cols-2 gap-4' : 'flex justify-end'
    ]">
      <Button v-if="currentStep > 1" @click="$emit('back')" class="w-full bg-gray-300 hover:bg-gray-400">
        Back
      </Button>

      <Button :disabled="isProcessing" class="w-full bg-green-900 text-white hover:bg-sky-500" @click="save">
        {{ isProcessing ? 'Saving...' : 'Save & Continue' }}
      </Button>
    </div>

  </div>
</template>