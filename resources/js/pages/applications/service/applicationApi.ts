import axios from 'axios'

export const saveApplicant = async (payload: any) => {
    const res = await axios.post('/api/chainsaw/apply', payload)
    return res.data.data
}

export const saveChainsaw = async (payload: any, id: any) => {
    return await axios.post('/api/chainsaw/insertChainsawInfo', {
        ...payload,
        id
    })
}

export const savePayment = async (payload: any, id: any) => {
    return await axios.post('/api/chainsaw/insert_payment', {
        ...payload,
        id
    })
}
export const saveSupplierInfo = async (payload: any) => {
    return await axios.post('/api/chainsaw-permit/store', payload)
}

