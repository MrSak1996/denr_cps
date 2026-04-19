import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAuth() {
    const page = usePage()

    const id = computed(() => page.props.auth.user.id)
    const user = computed(() => page.props.auth.user)
    const userId = computed(() => user.value?.id)
    const roleId = computed(() => user.value?.role_id)

    return { id,user, userId,roleId }
}
