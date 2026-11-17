import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useBkashStore = defineStore('bkash', () => {
  const agreementStatus = ref(null) // { has_agreement, created_at }
  const loading         = ref(false)

  async function fetchAgreementStatus() {
    const { data } = await api.get('/bkash/agreement/status')
    agreementStatus.value = data
    return data
  }

  async function initiateAgreement() {
    loading.value = true
    try {
      const { data } = await api.post('/bkash/agreement/create')
      return data // { bkash_url, payment_id }
    } finally {
      loading.value = false
    }
  }

  async function initiatePayment(amount, description = '') {
    loading.value = true
    try {
      const { data } = await api.post('/bkash/payment/create', { amount, description })
      return data // { bkash_url, payment_id }
    } finally {
      loading.value = false
    }
  }

  return { agreementStatus, loading, fetchAgreementStatus, initiateAgreement, initiatePayment }
})
