<template>
  <div class="min-h-screen bg-gray-50">
    <AppNavbar />

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

      <!-- Row 1: Balance + Agreement -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Balance (takes 2 cols) -->
        <div class="md:col-span-2">
          <WalletCard
            :balance="auth.walletBalance"
            :subtitle="`${t('dashboard.welcome')}, ${auth.user?.name || ''} 👋`"
          />
        </div>

        <!-- Agreement -->
        <AgreementCard
          :has-agreement="auth.hasAgreement"
          :created-at="auth.user?.active_agreement?.created_at"
          :loading="agreementLoading"
          @create="handleCreateAgreement"
        />
      </div>

      <!-- Row 2: Top-Up -->
      <TopUpForm
        :has-agreement="auth.hasAgreement"
        :loading="paymentLoading"
        @submit="handleTopup"
      />

      <!-- Row 3: Recent Transactions -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-gray-800 text-lg">{{ t('dashboard.recent') }}</h2>
          <RouterLink to="/transactions" class="text-bkash text-sm font-semibold hover:underline">
            {{ t('nav.history') }} →
          </RouterLink>
        </div>
        <TransactionTable :transactions="recentTxns" :loading="txnLoading" compact />
      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/store/modules/auth'
import { useTransactionStore } from '@/store/modules/transactions'
import AppNavbar from '@/components/shared/AppNavbar.vue'
import WalletCard from '@/components/dashboard/WalletCard.vue'
import AgreementCard from '@/components/dashboard/AgreementCard.vue'
import TopUpForm from '@/components/dashboard/TopUpForm.vue'
import TransactionTable from '@/components/transactions/TransactionTable.vue'
import api from '@/api'

const { t } = useI18n()
const auth           = useAuthStore()
const txnStore       = useTransactionStore()
const route          = useRoute()
const showToast      = inject('showToast')

const paymentLoading   = ref(false)
const agreementLoading = ref(false)
const txnLoading       = ref(false)
const recentTxns       = ref([])

// ── Handle bKash redirect query params ────────────────────────────────────────
onMounted(async () => {
  const { payment, agreement } = route.query

  if (payment === 'success') {
    showToast(t('toast.payment_success'), 'success')
    await auth.fetchMe()
  } else if (['failed', 'failure'].includes(payment)) {
    showToast(t('toast.payment_failed'), 'error')
  } else if (payment === 'cancel') {
    showToast(t('toast.payment_cancel'), 'info')
  }

  if (agreement === 'success') {
    showToast(t('toast.agreement_success'), 'success')
    await auth.fetchMe()
  } else if (agreement === 'failed') {
    showToast(t('toast.agreement_failed'), 'error')
  }

  // Load recent 5
  txnLoading.value = true
  try {
    await txnStore.fetchTransactions({ per_page: 5 })
    recentTxns.value = txnStore.transactions
  } finally {
    txnLoading.value = false
  }
})

// ── Agreement ─────────────────────────────────────────────────────────────────
async function handleCreateAgreement() {
  agreementLoading.value = true
  try {
    const { data } = await api.post('/bkash/agreement/create')
    window.location.href = data.bkash_url
  } catch (e) {
    showToast(e.response?.data?.message || t('toast.agreement_failed'), 'error')
    agreementLoading.value = false
  }
}

// ── Top-Up ────────────────────────────────────────────────────────────────────
async function handleTopup(amount) {
  paymentLoading.value = true
  try {
    const { data } = await api.post('/bkash/payment/create', { amount, description: 'Wallet top-up' })
    window.location.href = data.bkash_url
  } catch (e) {
    showToast(e.response?.data?.message || t('toast.payment_failed'), 'error')
    paymentLoading.value = false
  }
}
</script>
