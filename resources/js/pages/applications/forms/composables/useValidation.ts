export function validateApplicant(data:any){

    if(!data.first_name) return 'First name required'
    if(!data.last_name) return 'Last name required'

    return true
}