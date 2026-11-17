<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold', styles[type]]">
    {{ t(`type.${type}`) }}
  </span>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
defineProps({ type: String })
const styles = {
  credit: 'bg-green-100 text-green-700',
  debit:  'bg-orange-100 text-orange-700',
  refund: 'bg-blue-100 text-blue-700',
}
</script>
