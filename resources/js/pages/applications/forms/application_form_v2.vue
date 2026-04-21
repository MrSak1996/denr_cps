<script setup lang="ts">
import { ref, onMounted, computed, defineAsyncComponent, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast';

import { usePrivacyConsent } from './composables/usePrivacyConsent'
import { useApplicationStepper } from './composables/useApplicationStepper'
import { saveApplicant, saveChainsaw, savePayment, saveSupplierInfo, getApplicationReview } from '../service/applicationApi'

const props = defineProps({
    application_id: [String, Number, null],
    step: Number,
    type: String
})

const { currentStep, next, prevStep } = useApplicationStepper(props.step)

const { showPrivacyDialog, hasAgreedPrivacy, checkConsent, accept } = usePrivacyConsent()

const form = ref({
    application_no: '',
    application_id: null
})

/* 🔥 SINGLE SOURCE OF TRUTH */
const application = ref<any>({})
const suppliers = ref<any[]>([])
const files = ref([])
const isProcessing = ref(false)
const defaultSupplierDialog = ref(false)
const toast = useToast();

/* Lazy components */
const StepApplicant = defineAsyncComponent(() => import('./components/steps/StepApplicant.vue'))
const StepChainsaw = defineAsyncComponent(() => import('./components/steps/StepChainsaw.vue'))
const StepPayment = defineAsyncComponent(() => import('./components/steps/StepPayment.vue'))
const StepReview = defineAsyncComponent(() => import('./components/steps/StepReview.vue'))

const activeComponent = computed(() => ({
    1: StepApplicant,
    2: StepChainsaw,
    3: StepPayment,
    4: StepReview
}[currentStep.value]))

/* 🔥 MAIN STEPPER LOGIC */
const nextStep = async (payload: any) => {
    if (isProcessing.value) return
    isProcessing.value = true

    try {
        if (currentStep.value === 1) {
            const res = await saveApplicant({
                ...payload,
                application_type: payload.application_type
            })

            toast.add({ severity: 'success', summary: 'Saved', detail: 'Applicant saved', life: 3000 })
            next()

            router.visit(route('applications.create.citizen', {
                application_id: form.value.application_id,
                type: props.type,
                step: currentStep.value
            }), {
                preserveState: true,
                preserveScroll: true
            })
        }

        else if (currentStep.value === 2) {
            const res = await saveChainsaw({
                ...payload,
                suppliers: suppliers.value,
                application_type: payload.application_type,
            }, form.value.application_id)


            toast.add({ severity: 'success', summary: 'Saved', detail: 'Chainsaw saved', life: 3000 })
            next()

            router.visit(route('applications.create.citizen', {
                application_id: form.value.application_id,
                type: props.type,
                step: currentStep.value
            }), {
                preserveState: true,
                preserveScroll: true
            })
        }

        else if (currentStep.value === 3) {
            const res = await savePayment({
                ...payload,
                application_type: payload.application_type,
            }, form.value.application_id)

            toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment saved', life: 3000 })
            next()

            router.visit(route('applications.create.citizen', {
                application_id: form.value.application_id,
                type: props.type,
                step: currentStep.value
            }), {
                preserveState: true,
                preserveScroll: true
            })
        }

    } catch (error: any) {
        console.error(error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error?.response?.data?.message || 'Something went wrong',
            life: 4000,
        })
    } finally {
        isProcessing.value = false
    }
}

/* Privacy */
const handleAcceptPrivacy = async () => {
    const data = await accept()
    form.value.application_no = data.application_no
    form.value.application_id = data.application_id
    showPrivacyDialog.value = false
}

const supplierSaved = async (data: any) => {
    suppliers.value = data

    await saveSupplierInfo({
        suppliers: data,
        application_id: form.value.application_id
    })
}

const loadReviewData = async () => {
    const id = form.value.application_id

    if (!id) return

    const res = await getApplicationReview(id)

    application.value = res.application
    suppliers.value = res.suppliers
    files.value = res.files
}

const goBack = () => {
    prevStep()

    router.visit(route('applications.create.citizen', {
        application_id: form.value.application_id,
        type: props.type,
        step: currentStep.value
    }), {
        preserveState: true,
        preserveScroll: true
    })
}
watch(currentStep, async (step) => {
    if (step === 4) {
        await loadReviewData()
    }
})

onMounted(async () => {
    if (props.application_id || currentStep.value === 4) {
        form.value.application_id = props.application_id

        const hasConsent = await checkConsent(form.value.application_id)

        if (!hasConsent) {
            showPrivacyDialog.value = true
        }
    } else {
        showPrivacyDialog.value = true
    }

    if (currentStep.value === 4) {
        await loadReviewData()
    }
})
</script>

<template>

    <Head title="Chainsaw Purchase System" />
    <AppLayout>
        <Toast />
        <div class="space-y-6 p-6">
            <component :is="activeComponent" :application="application" :form="form" :suppliers="suppliers"
                :application_type="type" :isProcessing="isProcessing" :currentStep="currentStep" :supplier="suppliers"
                :files="files" @next="nextStep" @back="goBack" @supplierSaved="supplierSaved" />
        </div>

        <Dialog header="Privacy Consent" v-model:visible="showPrivacyDialog" modal :closable="false" :draggable="false"
            :style="{ width: '500px' }">
            <div class="space-y-4 text-sm text-gray-700">
                <p> In compliance with the <b>Data Privacy Act of 2012 (RA 10173)</b>, we collect and process your
                    personal information solely for the purpose of processing your Chainsaw Purchase System. </p>
                <p>Your data will be treated confidentially and will not be shared without your consent unless required
                    by law.</p>
                <div class="mt-4 flex items-start gap-2">
                    <Checkbox v-model="hasAgreedPrivacy" binary /> <label class="text-sm"> I have read and agree to the
                        Data Privacy Policy. </label>
                </div>
            </div> <template #footer> <Button label="Decline" class="p-button-text"
                    @click="router.get(route('applications.create.citizen'))">Decline</Button> <Button
                    label="Agree & Continue" :disabled="!hasAgreedPrivacy" class="bg-green-900 text-white"
                    @click="handleAcceptPrivacy">Agree & Continue</Button> </template>
        </Dialog>
    </AppLayout>
</template>