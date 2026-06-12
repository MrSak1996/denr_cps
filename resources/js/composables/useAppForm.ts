import { reactive } from "vue";

export function useAppForm() {

const company_form = reactive({
    id: "",
    application_no: "",
    status_id:"",

    permit_no: "",
    application_type: "Company",
    date_applied: new Date().toISOString().slice(0,10),
    geo_code: "",
    type_of_transaction: "G2B",
    classification: "Highly Technical",

    company_name: "",
    company_address: "",
    company_mobile_no: "",
    authorized_representative: "",

    request_letter: null as File | null,
    soc_certificate: null as File | null,

    c_region: "REGION IV-A (CALABARZON)",
    c_province: null as string | null,
    c_city_mun: null as string | null,
    c_barangay: null as string | null,

    p_place_of_operation_address: "",
    p_region: "REGION IV-A (CALABARZON)",
    p_province: "",
    p_city_mun: "",
    p_barangay: "",

    encoded_by: null as number | null,

    errors: {
        region: "",
        c_province: "",
        c_city_mun: "",
        c_barangay: "",
        address: "",
        p_region: "",
        p_province: "",
        p_city_mun: "",
        p_barangay: "",
        p_place_of_operation_address: "",
    }
});


const chainsaw_form = reactive({
    application_id: 0,
    application_attachment_id: 0,
    permit_chainsaw_no: "",

    brand: "",
    model: "",
    engine_serial_no: "",
    quantity: "",

    supplier_name: "",
    supplier_address: "",

    purpose: "",
    permit_validity: new Date().toISOString().slice(0,10),

    other_details: "",

    mayorDTI: null as File | null,
    affidavit: null as File | null,
    otherDocs: null as File | null,
    permit: null as File | null,

    errors: {} as Record<string,string>,
    updated_at: null as number | null,
    created_at: Date.now()
});


const createChainsaw = () => ({
    application_id: 0,
    application_attachment_id: 0,
    permit_chainsaw_no: "",

    brand: "",
    model: "",
    engine_serial_no: "",
    quantity: "",

    supplier_name: "",
    supplier_address: "",

    purpose: "",
    permit_validity: new Date().toISOString().slice(0,10),

    other_details: "",

    mayorDTI: null as File | null,
    affidavit: null as File | null,
    otherDocs: null as File | null,
    permit: null as File | null,

    errors: {} as Record<string,string>,
    updated_at: null as number | null,
    created_at: Date.now()
});


const individual_form = reactive({
    application_id: "",
    application_no: "",
    permit_no: "",
    status_title: "",
    status_id:"",

    application_type: "Individual",
    application_status: "",
    date_applied: new Date().toISOString().slice(0,10),

    type_of_transaction: "G2C",
    classification: "Highly Technical",
    geo_code: "",

    last_name: "",
    first_name: "",
    middle_name: "",
    sex: "",

    mobile_no: "",
    telephone_no: "",
    email_address: "",

    gov_id_type: "",
    gov_id_number: "",

    i_region: "REGION IV-A (CALABARZON)",
    i_province: null as string | null,
    i_city_mun: null as string | null,
    i_barangay: null as string | null,
    i_complete_address: "",

    p_place_of_operation_address: "",
    p_region: "REGION IV-A (CALABARZON)",
    p_province: "",
    p_city_mun: "",
    p_barangay: "",

    encoded_by: null as number | null,

    errors: {
        application_no: "",
        status_title: "",
        status_id:"",
        application_type: "",
        date_applied: "",
        type_of_transaction: "",
        geo_code: "",

        last_name: "",
        first_name: "",
        middle_name: "",
        sex: "",

        mobile_no: "",
        telephone_no: "",
        email_address: "",

        gov_id_type: "",
        gov_id_number: "",

        i_region: "",
        i_province: "",
        i_city_mun: "",
        i_barangay: "",
        i_complete_address: "",

        p_place_of_operation_address: "",
        p_region: "",
        p_province: "",
        p_city_mun: "",
        p_barangay: "",

        encoded_by: ""
    }
});


const payment_form = reactive({
    application_id: 0,
    application_attachment_id: 0,
    official_receipt: "",

    permit_fee: 500,
    date_of_payment: new Date().toISOString().slice(0,10),

    or_ccopy: null as File | null,
    remarks: ""
});

return {
    createChainsaw,
    company_form,
    chainsaw_form,
    individual_form,
    payment_form
};

}