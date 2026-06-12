import { ref } from 'vue'

export function useApplicationStepper(step = 1){

    const currentStep = ref(Number(step) || 1)
    

    const next = () => {

        if(currentStep.value < 4) currentStep.value++
        
    }

    const prevStep = () => {
        if(currentStep.value > 1) currentStep.value--
        
    }

    return {
        currentStep,
        next,
        prevStep
    }
}