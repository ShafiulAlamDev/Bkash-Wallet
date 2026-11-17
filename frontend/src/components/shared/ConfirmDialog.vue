<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
             @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
          <div :class="['w-12 h-12 rounded-full flex items-center justify-center mx-auto text-2xl',
                        typeStyles[type]?.bg || 'bg-gray-100']">
            {{ typeStyles[type]?.icon || '❓' }}
          </div>
          <div class="text-center">
            <h3 class="font-bold text-gray-800 text-lg">{{ title || t('confirm.title') }}</h3>
            <p class="text-gray-500 text-sm mt-1">{{ message || t('confirm.message') }}</p>
          </div>
          <div class="flex gap-3 pt-2">
            <button @click="$emit('update:modelValue', false)" class="btn-secondary flex-1">
              {{ cancelLabel || t('confirm.cancel') }}
            </button>
            <button @click="confirm"
                    :class="['flex-1', type === 'danger'
                      ? 'bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg transition-colors'
                      : 'btn-primary']">
              {{ confirmLabel || t('confirm.ok') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  modelValue:   { type: Boolean, default: false },
  title:        { type: String,  default: '' },
  message:      { type: String,  default: '' },
  confirmLabel: { type: String,  default: '' },
  cancelLabel:  { type: String,  default: '' },
  type:         { type: String,  default: 'info' },
})
const emit = defineEmits(['update:modelValue', 'confirm'])

const typeStyles = {
  info:    { bg: 'bg-blue-100',  icon: 'ℹ️' },
  danger:  { bg: 'bg-red-100',   icon: '⚠️' },
  success: { bg: 'bg-green-100', icon: '✅' },
}

function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
