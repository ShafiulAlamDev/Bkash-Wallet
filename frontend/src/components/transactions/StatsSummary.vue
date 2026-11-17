<template>
  <div class="grid grid-cols-3 gap-3">
    <div v-for="stat in stats" :key="stat.key"
         :class="['rounded-xl p-4 border', stat.bg, stat.border]">
      <p :class="['text-xs font-semibold uppercase tracking-wide', stat.labelColor]">
        {{ stat.label }}
      </p>
      <p :class="['text-xl font-black mt-1', stat.valueColor]">
        ৳{{ Number(stat.value).toLocaleString('en-BD', { minimumFractionDigits: 2 }) }}
      </p>
      <p :class="['text-xs mt-0.5', stat.subColor]">
        {{ stat.count }} {{ t('transactions.total') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  transactions: { type: Array, default: () => [] },
})

const stats = computed(() => {
  const credits = props.transactions.filter(tx => tx.type === 'credit')
  const debits  = props.transactions.filter(tx => tx.type === 'debit')
  const refunds = props.transactions.filter(tx => tx.type === 'refund')
  return [
    {
      key: 'credit', label: t('transactions.credits'),
      value: credits.reduce((s, tx) => s + Number(tx.amount), 0), count: credits.length,
      bg: 'bg-green-50', border: 'border-green-200',
      labelColor: 'text-green-700', valueColor: 'text-green-700', subColor: 'text-green-500',
    },
    {
      key: 'debit', label: t('transactions.debits'),
      value: debits.reduce((s, tx) => s + Number(tx.amount), 0), count: debits.length,
      bg: 'bg-orange-50', border: 'border-orange-200',
      labelColor: 'text-orange-700', valueColor: 'text-orange-700', subColor: 'text-orange-500',
    },
    {
      key: 'refund', label: t('transactions.refunds'),
      value: refunds.reduce((s, tx) => s + Number(tx.amount), 0), count: refunds.length,
      bg: 'bg-blue-50', border: 'border-blue-200',
      labelColor: 'text-blue-700', valueColor: 'text-blue-700', subColor: 'text-blue-500',
    },
  ]
})
</script>
