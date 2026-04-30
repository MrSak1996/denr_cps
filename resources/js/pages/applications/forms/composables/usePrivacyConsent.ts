import { ref } from 'vue'
import axios from 'axios'

export function usePrivacyConsent() {
    const showPrivacyDialog = ref(false)
    const hasAgreedPrivacy = ref(false)

    const checkConsent = (applicationId: any) => {
        if (!applicationId) {
            showPrivacyDialog.value = true
            return false
        }

        const saved = sessionStorage.getItem('privacy_accepted')
        const hasConsent = saved === 'true'

        hasAgreedPrivacy.value = hasConsent
        showPrivacyDialog.value = !hasConsent   // ✅ THIS IS THE FIX

        return hasConsent
    }

    const generateApplicationNumber = async (userId: any) => {
    const res = await axios.get('/generateApplicationNumber', {
        params: { user_id: userId }
    });

        return {
            application_no: res.data.application_no,
            application_id: res.data.application_id
        }
    }

    const accept = async (userId: any) => {
        hasAgreedPrivacy.value = true
        showPrivacyDialog.value = false
        sessionStorage.setItem('privacy_accepted', 'true')

        return await generateApplicationNumber(userId)
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