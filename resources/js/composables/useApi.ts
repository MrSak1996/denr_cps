import axios from 'axios';
import { ref } from 'vue';
export function useApi() {
    interface Option {
        id: number;
        name: string;
    }
    interface Province {
        id: number;
        prov_name: string;
    }

    const prov_name = ref<Option[]>([]);
    const application_no = ref<string>('');

    const getApplicationNumber = async (
        form: { application_no: string; application_id?: number },
        chainsaw_form: { application_no: string; application_id?: number },
    ): Promise<void> => {
        try {
            const response = await axios.get<{
                application_no: string;
                application_id: number;
            }>('/generateApplicationNumber');

            if (response.data) {
                const { application_no, application_id } = response.data;

                // ✅ assign application number
                form.application_no = application_no;
                chainsaw_form.application_no = application_no;

                // ✅ assign application ID (IMPORTANT)
                form.application_id = application_id;
                chainsaw_form.application_id = application_id;

                // optional debug
                console.log('Application No:', application_no);
                console.log('Application ID:', application_id);
            } else {
                console.error('Invalid response structure');
            }
        } catch (error) {
            console.error('Error fetching application number:', error);
        }
    };

    const getProvinceCode = async (): Promise<void> => {
        try {
            const res = await axios.get<Province[]>('http://localhost:8000/api/getProvinces');
            prov_name.value = res.data.map((item) => ({
                id: Number(item.prov_code),
                name: item.prov_name,
            }));
        } catch (error) {
            console.error('Error fetching provinces:', error);
        }
    };

    return {
        application_no,
        prov_name,
        getApplicationNumber,
        getProvinceCode,
    };
}
