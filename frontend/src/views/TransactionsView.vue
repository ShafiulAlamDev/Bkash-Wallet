<template>
  <div class="min-h-screen bg-gray-50">
    <AppNavbar />

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

      <!-- Header + Download -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ t('transactions.title') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
          <input v-model="dateFrom" type="date" class="input w-auto text-sm py-2" />
          <span class="text-gray-400">→</span>
          <input v-model="dateTo" type="date" class="input w-auto text-sm py-2" />
          <button @click="handleDownload" :disabled="downloading" class="btn-primary text-sm py-2">
            <span v-if="downloading" class="animate-spin">⏳</span>
            <span v-else>📄</span>
            {{ t('transactions.download') }}
          </button>
        </div>
      </div>

      <!-- Download validation error -->
      <div v-if="downloadError"
           class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
        <span>⚠️</span><span>{{ downloadError }}</span>
      </div>

      <!-- Stats -->
      <StatsSummary :transactions="allTxns" />

      <!-- Filter Tabs -->
      <div class="flex gap-2 flex-wrap">
        <button v-for="tab in tabs" :key="tab.value"
                @click="selectTab(tab.value)"
                :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-colors',
                         activeTab === tab.value
                           ? 'bg-bkash text-white shadow-sm'
                           : 'bg-white border border-gray-200 text-gray-600 hover:border-bkash hover:text-bkash']">
          {{ t(tab.label) }}
        </button>
      </div>

      <!-- Table -->
      <div class="card p-0 overflow-hidden">
        <TransactionTable
          :transactions="txnStore.transactions"
          :loading="txnStore.loading"
          @refund="openRefundModal"
        />
        <AppPagination
          :current-page="currentPage"
          :last-page="txnStore.meta.last_page || 1"
          :total="txnStore.meta.total || 0"
          @change="changePage"
        />
      </div>

    </main>

    <RefundModal
      v-if="refundModal.open"
      :transaction="refundModal.transaction"
      @close="refundModal.open = false"
      @success="onRefundSuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTransactionStore } from '@/store/modules/transactions'
import { useAuthStore } from '@/store/modules/auth'
import AppNavbar        from '@/components/shared/AppNavbar.vue'
import AppPagination    from '@/components/shared/AppPagination.vue'
import StatsSummary     from '@/components/transactions/StatsSummary.vue'
import TransactionTable from '@/components/transactions/TransactionTable.vue'
import RefundModal      from '@/components/transactions/RefundModal.vue'

const { t, locale } = useI18n()
const txnStore  = useTransactionStore()
const auth      = useAuthStore()
const showToast = inject('showToast')

const activeTab     = ref('')
const currentPage   = ref(1)
const downloading   = ref(false)
const downloadError = ref('')
const allTxns       = ref([])
const dateFrom      = ref(new Date(new Date().setDate(1)).toISOString().slice(0, 10))
const dateTo        = ref(new Date().toISOString().slice(0, 10))

const tabs = [
  { label: 'transactions.all',     value: '' },
  { label: 'transactions.credits', value: 'credit' },
  { label: 'transactions.debits',  value: 'debit' },
  { label: 'transactions.refunds', value: 'refund' },
]

const refundModal = reactive({ open: false, transaction: null })

function openRefundModal(txn) {
  refundModal.transaction = txn
  refundModal.open = true
}

async function load() {
  await txnStore.fetchTransactions({ page: currentPage.value, type: activeTab.value })
  if (!activeTab.value) allTxns.value = txnStore.transactions
}

async function selectTab(value) {
  activeTab.value = value
  currentPage.value = 1
  await load()
}

async function changePage(page) {
  currentPage.value = page
  await load()
}

async function handleDownload() {
  downloadError.value = ''
  if (!dateFrom.value) { downloadError.value = t('validation.date_from'); return }
  if (!dateTo.value)   { downloadError.value = t('validation.date_to');   return }
  if (dateFrom.value > dateTo.value) { downloadError.value = t('validation.date_order'); return }
  const diff = (new Date(dateTo.value) - new Date(dateFrom.value)) / 86400000
  if (diff > 365) { downloadError.value = t('validation.date_range'); return }

  downloading.value = true
  try {
    await txnStore.downloadStatement({ from: dateFrom.value, to: dateTo.value, locale: locale.value })
  } catch {
    showToast(t('toast.pdf_failed'), 'error')
  } finally {
    downloading.value = false
  }
}

async function onRefundSuccess() {
  refundModal.open = false
  showToast(t('toast.refund_success'), 'success')
  await auth.fetchMe()
  await load()
}

onMounted(load)
</script>
