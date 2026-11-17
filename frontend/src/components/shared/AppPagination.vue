<template>
  <div v-if="lastPage > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
    <p class="text-sm text-gray-500">
      <strong>{{ total }}</strong> {{ t('transactions.total') }}
    </p>
    <div class="flex items-center gap-1">
      <button @click="$emit('change', 1)"              :disabled="currentPage === 1"        class="page-btn">«</button>
      <button @click="$emit('change', currentPage - 1)" :disabled="currentPage === 1"        class="page-btn">‹</button>
      <button v-for="page in visiblePages" :key="page"
              @click="$emit('change', page)"
              :class="['page-btn', page === currentPage ? 'bg-bkash text-white border-bkash' : '']">
        {{ page }}
      </button>
      <button @click="$emit('change', currentPage + 1)" :disabled="currentPage === lastPage" class="page-btn">›</button>
      <button @click="$emit('change', lastPage)"        :disabled="currentPage === lastPage" class="page-btn">»</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  lastPage:    { type: Number, default: 1 },
  total:       { type: Number, default: 0 },
})
defineEmits(['change'])

const visiblePages = computed(() => {
  const range = [], delta = 2
  const left  = Math.max(1, props.currentPage - delta)
  const right = Math.min(props.lastPage, props.currentPage + delta)
  for (let i = left; i <= right; i++) range.push(i)
  return range
})
</script>

<style scoped>
.page-btn {
  @apply w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200
         text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-bkash
         hover:text-bkash transition-colors disabled:opacity-40 disabled:cursor-not-allowed;
}
</style>
