import axios from 'axios'

export const saveApplicant = async (payload: any) => {
    const formData = new FormData()

    Object.keys(payload).forEach((key) => {
        formData.append(key, payload[key])
    })

    const res = await axios.post('/api/chainsaw/apply', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })

    return res.data
}


export const saveCompanyApplication = async (payload: any) => {
    const formData = new FormData()

    Object.keys(payload).forEach((key) => {
        formData.append(key, payload[key])
    })

    const res = await axios.post('/api/chainsaw/company_apply', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })

    return res.data
}

export const saveChainsaw = async (payload: any, id: any) => {
    const formData = new FormData();

    formData.append('id', String(id));
    formData.append('application_type', payload.application_type ?? '');


    Object.keys(payload).forEach((key) => {
        const value = payload[key];

        // 🔥 HANDLE FILES PROPERLY
        if (value instanceof File) {
            formData.append(key, value);
        }
        else if (value?.file instanceof File) {
            formData.append(key, value.file);
        }
        else if (Array.isArray(value) && value.length && value[0] instanceof File) {
            formData.append(key, value[0]); // take first file
        }
        else if (Array.isArray(value)) {
            formData.append(key, JSON.stringify(value));
        }
        else if (value !== null && value !== undefined) {
            formData.append(key, String(value));
        }
    });

    // 🔍 DEBUG: check what's actually sent
    for (let pair of formData.entries()) {
        console.log(pair[0], pair[1]);
    }

    return await axios.post('/api/chainsaw/insertChainsawInfo', formData);
};

export const savePayment = async (payload: any, id: any) => {
    const formData = new FormData();

    formData.append('id', String(id));

    Object.keys(payload).forEach((key) => {
        const value = payload[key];

        if (value instanceof File) {
            formData.append(key, value);
        }
        else if (value !== null && value !== undefined) {
            formData.append(key, String(value));
        }
    });

    return await axios.post('/api/chainsaw/insert_payment', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
};

export const saveSupplierInfo = async (payload: any) => {
    return await axios.post('/api/chainsaw-permit/store', payload)
}

export const getApplicationReview = async (id: any) => {
    const [appRes, supplierRes, fileRes] = await Promise.all([
        axios.get(`/api/getApplicationDetails/${id}`),
        axios.get(`/api/chainsaw/${id}/supplier`),
        axios.get(`/api/getApplicantFile/${id}`)
    ])

    return {
        application: appRes.data.data || {},
        suppliers: supplierRes.data || [],
        files: fileRes.data.data || []
    }
}

export const getRoutingHistory = async (id: any) => {
    const res = await axios.get(`/api/application-routing/${id}`)
    return res.data || []
}

