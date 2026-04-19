import { ref } from 'vue'
import axios from 'axios'

export function usePrivacyConsent() {
    const showPrivacyDialog = ref(false)
    const hasAgreedPrivacy = ref(false)

    const checkConsent = (applicationId: any) => {
        if (!applicationId) {
            showPrivacyDialog.value = true
        } else {
            const saved = sessionStorage.getItem('privacy_accepted')
            hasAgreedPrivacy.value = saved === 'true'
        }
    }

    const generateApplicationNumber = async () => {
        const res = await axios.get('/generateApplicationNumber')

        return {
            application_no: res.data.application_no,
            application_id: res.data.application_id
        }
    }

    const accept = async () => {
        hasAgreedPrivacy.value = true
        showPrivacyDialog.value = false
        sessionStorage.setItem('privacy_accepted', 'true')

        return await generateApplicationNumber()
    }

    const decline = () => {
        sessionStorage.removeItem('privacy_accepted')
        window.location.href = '/applications/index'
    }

    return {
        showPrivacyDialog,
        hasAgreedPrivacy,
        checkConsent,
        accept,
        decline
    }
}