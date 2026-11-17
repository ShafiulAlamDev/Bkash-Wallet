<template>
  <div class="relative overflow-hidden rounded-2xl p-6 text-white"
       :class="gradient ? 'bg-gradient-to-br from-bkash to-bkash-dark' : 'bg-bkash'">
    <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/10 pointer-events-none" />
    <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full bg-white/10 pointer-events-none" />
    <div class="relative">
      <p class="text-pink-200 text-sm font-medium mb-1">{{ label || t('dashboard.balance') }}</p>
      <p class="text-4xl font-black tracking-tight">৳ {{ formattedBalance }}</p>
      <p v-if="subtitle" class="text-pink-200 text-xs mt-2">{{ subtitle }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const props = defineProps({
  balance:  { type: [Number, String], default: 0 },
  label:    { type: String,  default: '' },
  subtitle: { type: String,  default: '' },
  gradient: { type: Boolean, default: true },
})
const formattedBalance = computed(() =>
  Number(props.balance).toLocaleString('en-BD', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
)
</script>
