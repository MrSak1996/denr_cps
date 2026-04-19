<script setup lang="ts">
import { Button } from '@/components/ui/button'

const emit = defineEmits(['back','submit'])

const props = defineProps({
  application: Object,
  form: Object
})
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-xl font-semibold">Review & Submit</h2>

    <div class="rounded-xl border p-4 bg-gray-50 space-y-2 text-sm">
      <p><b>Applicant:</b> {{ application?.first_name }} {{ application?.last_name }}</p>
      <p><b>Application No:</b> {{ application?.application_no }}</p>
      <p><b>Type:</b> {{ application?.type_of_transaction }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <Button variant="outline" @click="emit('back')">Back</Button>
      <Button class="bg-green-700 text-white" @click="emit('submit')">Submit Application</Button>
    </div>
  </div>
</template>