import { defineStore } from 'pinia'

export const useApplicationStore = defineStore('application', {
  state: () => ({
    applicationId: null as number | null,

    applicant: {
      first_name: '',
      last_name: '',
      middle_name: '',
      mobile_no: '',
      address: ''
    },

    chainsaw: {},
    payment: {}
  }),

  actions: {
    setApplicant(data: any) {
      this.applicant = data
    },

    setChainsaw(data: any) {
      this.chainsaw = data
    },

    setPayment(data: any) {
      this.payment = data
    },

    setApplicationId(id: number) {
      this.applicationId = id
    },

    reset() {
      this.$reset()
    }
  }
})