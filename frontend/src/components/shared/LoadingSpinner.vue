<template>
  <div :class="['flex items-center justify-center', fullPage ? 'min-h-screen' : 'py-16']">
    <div class="flex flex-col items-center gap-4">
      <div class="relative w-12 h-12">
        <div class="absolute inset-0 rounded-full border-4 border-bkash-light" />
        <div class="absolute inset-0 rounded-full border-4 border-bkash border-t-transparent animate-spin" />
      </div>
      <p v-if="label || showDefault" class="text-sm text-gray-500">
        {{ label || t('loading.default') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
defineProps({
  fullPage:    { type: Boolean, default: false },
  label:       { type: String,  default: '' },
  showDefault: { type: Boolean, default: false },
})
</script>
