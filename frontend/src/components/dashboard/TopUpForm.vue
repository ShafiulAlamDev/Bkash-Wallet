<template>
  <div class="card">
    <h2 class="font-bold text-gray-800 text-lg mb-5">{{ t('topup.title') }}</h2>

    <!-- No agreement warning -->
    <div v-if="!hasAgreement"
         class="flex items-start gap-2 bg-orange-50 border border-orange-200 rounded-xl p-3 text-sm text-orange-700 mb-4">
      <span>⚠️</span>
      <span>{{ t('dashboard.no_agreement') }}</span>
    </div>

    <!-- Preset chips -->
    <div class="flex flex-wrap gap-2 mb-4">
      <button v-for="preset in presets" :key="preset" type="button"
              @click="selectPreset(preset)"
              :class="['px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                       amount === preset
                         ? 'bg-bkash text-white border-bkash'
                         : 'bg-white border-gray-200 text-gray-600 hover:border-bkash hover:text-bkash']">
        ৳{{ preset.toLocaleString() }}
      </button>
    </div>

    <!-- Amount input -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold pointer-events-none">৳</span>
          <input v-model.number="amount" type="number" min="1" max="25000" step="1"
            :placeholder="t('topup.amount')"
            :class="['input pl-8', touched && fieldError ? '!border-red-400 bg-red-50' : (touched && amount ? '!border-green-400' : '')]"
            @blur="touched = true" @input="touched = true" @keyup.enter="submit" />
        </div>
        <p v-if="touched && fieldError" class="text-red-600 text-xs mt-1 flex items-center gap-1">
          <span>⚠</span>{{ fieldError }}
        </p>
        <p v-else class="text-xs text-gray-400 mt-1">{{ t('topup.min') }} &nbsp;·&nbsp; {{ t('topup.max') }}</p>
      </div>

      <button @click="submit" :disabled="!canSubmit" class="btn-primary sm:w-52">
        <span v-if="loading" class="animate-spin">⏳</span>
        <template v-else>💳 {{ t('topup.proceed') }}</template>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const props = defineProps({
  hasAgreement: { type: Boolean, default: false },
  loading:      { type: Boolean, default: false },
})
const emit = defineEmits(['submit'])

const amount  = ref(null)
const touched = ref(false)
const presets = [100, 250, 500, 1000, 2000, 5000]

const fieldError = computed(() => {
  const v = amount.value
  if (v === null || v === '') return t('validation.amount_required')
  if (isNaN(Number(v)) || Number(v) <= 0) return t('validation.amount_invalid')
  if (Number(v) < 1)     return t('validation.amount_min')
  if (Number(v) > 25000) return t('validation.amount_max')
  if (!Number.isInteger(Number(v))) return t('validation.amount_integer')
  return null
})

const canSubmit = computed(() =>
  props.hasAgreement && !props.loading && amount.value && !fieldError.value
)

function selectPreset(val) {
  amount.value  = val
  touched.value = true
}

function submit() {
  touched.value = true
  if (!canSubmit.value) return
  emit('submit', amount.value)
}
</script>
