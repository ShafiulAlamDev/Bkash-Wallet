<template>
  <div>
    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="i in 5" :key="i" class="h-12 bg-gray-100 rounded-lg animate-pulse" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!transactions.length" class="text-center py-16 text-gray-400">
      <div class="text-4xl mb-3">📭</div>
      <p class="text-sm">{{ t('transactions.no_data') }}</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.date') }}</th>
            <th v-if="!compact" class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.description') }}</th>
            <th v-if="!compact" class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.trx_id') }}</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.type') }}</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.status') }}</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.amount') }}</th>
            <th v-if="!compact" class="text-right px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">{{ t('transactions.balance_after') }}</th>
            <th v-if="!compact" class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="txn in transactions" :key="txn.id" class="hover:bg-gray-50 transition-colors">

            <td class="px-4 py-3 text-gray-600">
              <div class="font-medium">{{ formatDate(txn.created_at) }}</div>
              <div class="text-xs text-gray-400">{{ formatTime(txn.created_at) }}</div>
            </td>

            <td v-if="!compact" class="px-4 py-3 text-gray-600 max-w-[180px] truncate">
              {{ txn.description || '—' }}
            </td>

            <td v-if="!compact" class="px-4 py-3">
              <span class="font-mono text-xs text-gray-400">{{ txn.trx_id || txn.payment_id || '—' }}</span>
            </td>

            <td class="px-4 py-3"><TypeBadge :type="txn.type" /></td>
            <td class="px-4 py-3"><StatusBadge :status="txn.status" /></td>

            <td class="px-4 py-3 text-right font-bold"
                :class="txn.type === 'credit' ? 'text-green-600' : 'text-red-500'">
              {{ txn.type === 'credit' ? '+' : '−' }}৳{{ Number(txn.amount).toFixed(2) }}
            </td>

            <td v-if="!compact" class="px-4 py-3 text-right text-gray-500">
              ৳{{ Number(txn.balance_after).toFixed(2) }}
            </td>

            <td v-if="!compact" class="px-4 py-3 text-right">
              <button
                v-if="txn.type === 'credit' && txn.status === 'completed' && Number(txn.refunded_amount) < Number(txn.amount)"
                @click="$emit('refund', txn)"
                class="text-xs text-bkash border border-bkash rounded-lg px-2.5 py-1 hover:bg-bkash-light transition-colors">
                {{ t('transactions.refund') }}
              </button>
            </td>

          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import TypeBadge   from './TypeBadge.vue'
import StatusBadge from './StatusBadge.vue'

const { t, locale } = useI18n()

defineProps({
  transactions: { type: Array,   default: () => [] },
  loading:      { type: Boolean, default: false },
  compact:      { type: Boolean, default: false },
})
defineEmits(['refund'])

function formatDate(dt) {
  return new Date(dt).toLocaleDateString(locale.value === 'bn' ? 'bn-BD' : 'en-BD', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}
function formatTime(dt) {
  return new Date(dt).toLocaleTimeString(locale.value === 'bn' ? 'bn-BD' : 'en-BD', {
    hour: '2-digit', minute: '2-digit'
  })
}
</script>
