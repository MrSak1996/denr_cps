import { ref } from 'vue'
import debounce from 'lodash/debounce'

export function useAutoSave(saveFn: Function, interval = 3000) {
    const saving = ref(false)

    const triggerSave = debounce(async (data: any) => {
        saving.value = true
        try {
            await saveFn(data)
        } finally {
            saving.value = false
        }
    }, interval)

    return {
        saving,
        triggerSave
    }
}