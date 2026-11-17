<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />

      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-800">{{ t('transactions.refund_modal') }}</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
            :aria-label="t('transactions.cancel')">×</button>
        </div>

        <!-- Fully refunded notice -->
        <div v-if="maxRefundable <= 0"
             class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
          <span>⚠️</span>
          <span>{{ t('transactions.fully_refunded') }}</span>
        </div>

        <!-- Partial refund info -->
        <div v-else-if="transaction.refunded_amount > 0"
             class="flex items-start gap-2 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-700">
          <span>ℹ️</span>
          <span>
            ৳{{ Number(transaction.refunded_amount).toFixed(2) }}
            {{ t('transactions.partial_info') }}
            ৳{{ maxRefundable.toFixed(2) }}
            {{ t('transactions.more') }}
          </span>
        </div>

        <!-- Summary -->
        <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">{{ t('transactions.trx_id') }}</span>
            <span class="font-mono font-medium text-gray-700 text-xs">{{ transaction.trx_id || '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ t('transactions.original_amt') }}</span>
            <span class="font-bold text-gray-800">৳{{ Number(transaction.amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ t('transactions.already_refund') }}</span>
            <span class="font-medium text-gray-600">৳{{ Number(transaction.refunded_amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
            <span class="text-gray-500 font-medium">{{ t('transactions.max_refund') }}</span>
            <span class="font-bold text-bkash">৳{{ maxRefundable.toFixed(2) }}</span>
          </div>
        </div>

        <template v-if="maxRefundable > 0">
          <!-- Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('transactions.refund_amount') }}</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold pointer-events-none">৳</span>
              <input v-model.number="refundAmount" type="number" :max="maxRefundable" min="1" step="0.01"
                placeholder="0.00"
                :class="['input pl-8', amountTouched && amountError ? '!border-red-400 bg-red-50'
                         : (amountTouched && refundAmount && !amountError ? '!border-green-400' : '')]"
                @input="amountTouched = true" @blur="amountTouched = true" />
            </div>
            <p v-if="amountTouched && amountError" class="text-red-600 text-xs mt-1 flex items-center gap-1">
              <span>⚠</span>{{ amountError }}
            </p>
            <p v-else-if="amountTouched && !amountError && refundAmount"
               class="text-green-600 text-xs mt-1 flex items-center gap-1">
              <span>✓</span> ৳{{ Number(refundAmount).toFixed(2) }} {{ t('transactions.refund_source') }}
            </p>
          </div>

          <!-- Reason -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              {{ t('transactions.reason') }}
              <span class="text-gray-400 font-normal">{{ t('transactions.reason_opt') }}</span>
            </label>
            <input v-model="reason" type="text" class="input"
              :placeholder="t('transactions.reason_ph')" maxlength="255" />
            <p class="text-xs text-gray-400 text-right mt-1">{{ reason.length }} / 255</p>
          </div>

          <!-- Server error -->
          <div v-if="serverError"
               class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2 text-sm text-red-700">
            <span>⚠️</span><span>{{ serverError }}</span>
          </div>
        </template>

        <div class="flex gap-3 pt-2">
          <button @click="$emit('close')" class="btn-secondary flex-1">{{ t('transactions.cancel') }}</button>
          <button v-if="maxRefundable > 0"
                  @click="handleRefund"
                  :disabled="loading || !!amountError || !refundAmount"
                  class="btn-primary flex-1">
            <span v-if="loading" class="animate-spin">⏳</span>
            {{ t('transactions.confirm') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTransactionStore } from '@/store/modules/transactions'

const { t } = useI18n()
const props    = defineProps({ transaction: { type: Object, required: true } })
const emit     = defineEmits(['close', 'success'])
const txnStore = useTransactionStore()

const refundAmount  = ref(null)
const reason        = ref('')
const loading       = ref(false)
const serverError   = ref('')
const amountTouched = ref(false)

const maxRefundable = computed(() =>
  Math.max(0, Number(props.transaction.amount) - Number(props.transaction.refunded_amount))
)

const amountError = computed(() => {
  const v = Number(refundAmount.value)
  if (!refundAmount.value && refundAmount.value !== 0) return t('validation.refund_required')
  if (isNaN(v) || v <= 0)          return t('validation.amount_invalid')
  if (v < 1)                       return t('validation.refund_min')
  if (v > maxRefundable.value)     return `${t('validation.refund_exceeds')} ৳${maxRefundable.value.toFixed(2)}.`
  return null
})

async function handleRefund() {
  amountTouched.value = true
  if (amountError.value) return
  loading.value = true
  serverError.value = ''
  try {
    await txnStore.refundTransaction(props.transaction.id, refundAmount.value, reason.value)
    emit('success')
  } catch (e) {
    serverError.value = e.response?.data?.message || t('toast.refund_failed')
  } finally {
    loading.value = false
  }
}
</script>
