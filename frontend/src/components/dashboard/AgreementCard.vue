<template>
  <div class="card h-full flex flex-col justify-between">
    <div>
      <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4',
                    hasAgreement ? 'bg-green-100' : 'bg-orange-100']">
        {{ hasAgreement ? '✅' : '🔗' }}
      </div>
      <h3 class="font-bold text-gray-800 text-base">
        {{ hasAgreement ? t('dashboard.agreement_active') : t('dashboard.create_agreement') }}
      </h3>
      <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
        {{ hasAgreement ? t('dashboard.agreement_info') : t('dashboard.no_agreement') }}
      </p>
      <p v-if="hasAgreement && createdAt" class="text-xs text-gray-400 mt-2">
        {{ t('dashboard.agreement_active') }} · {{ formatDate(createdAt) }}
      </p>
    </div>
    <div class="mt-4">
      <button v-if="!hasAgreement" @click="$emit('create')" :disabled="loading" class="btn-primary w-full text-sm py-2">
        <span v-if="loading" class="animate-spin inline-block">⏳</span>
        <span v-else>🔗</span>
        {{ t('dashboard.create_agreement') }}
      </button>
      <div v-else class="flex items-center gap-2 text-green-600 text-sm font-semibold">
        <span>✓</span><span>{{ t('dashboard.agreement_active') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t, locale } = useI18n()
defineProps({
  hasAgreement: { type: Boolean, default: false },
  createdAt:    { type: String,  default: null },
  loading:      { type: Boolean, default: false },
})
defineEmits(['create'])
function formatDate(dt) {
  return new Date(dt).toLocaleDateString(locale.value === 'bn' ? 'bn-BD' : 'en-BD', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}
</script>
