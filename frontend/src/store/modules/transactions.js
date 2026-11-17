import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useTransactionStore = defineStore('transactions', () => {
  const transactions = ref([])
  const meta         = ref({})
  const loading      = ref(false)

  async function fetchTransactions({ page = 1, type = '', per_page = 15 } = {}) {
    loading.value = true
    try {
      const params = { page, per_page }
      if (type) params.type = type
      const { data } = await api.get('/transactions', { params })
      transactions.value = data.data
      meta.value = {
        current_page:  data.current_page,
        last_page:     data.last_page,
        total:         data.total,
        per_page:      data.per_page,
      }
    } finally {
      loading.value = false
    }
  }

  async function refundTransaction(transactionId, amount, reason = '') {
    const { data } = await api.post(`/transactions/${transactionId}/refund`, { amount, reason })
    return data
  }

  async function downloadStatement({ from, to, locale }) {
    const response = await api.get('/transactions/statement/download', {
      params: { from, to, locale },
      responseType: 'blob',
    })
    // Trigger browser download
    const url  = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href  = url
    link.download = `statement_${from}_${to}.pdf`
    link.click()
    URL.revokeObjectURL(url)
  }

  return { transactions, meta, loading, fetchTransactions, refundTransaction, downloadStatement }
})
