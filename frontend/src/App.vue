<template>
  <div :lang="locale">
    <RouterView />
    <!-- Global toast -->
    <Transition name="slide-up">
      <div v-if="toast.show"
           :class="['fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium',
                    toast.type === 'success' ? 'bg-green-600' : toast.type === 'error' ? 'bg-red-600' : 'bg-gray-700']">
        <span>{{ toast.message }}</span>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { provide, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/store/modules/auth'

const { locale } = useI18n()
const auth = useAuthStore()

// ── Global toast ───────────────────────────────────────────────────────────────
const toast = reactive({ show: false, message: '', type: 'success' })
let toastTimer = null

function showToast(message, type = 'success', duration = 3500) {
  clearTimeout(toastTimer)
  toast.message = message
  toast.type    = type
  toast.show    = true
  toastTimer    = setTimeout(() => { toast.show = false }, duration)
}
provide('showToast', showToast)

// ── Bootstrap ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (auth.isAuthenticated && !auth.user) {
    try {
      await auth.fetchMe()
      // Sync locale from user preference
      if (auth.user?.preferred_locale) {
        locale.value = auth.user.preferred_locale
      }
    } catch {}
  }
})
</script>

<style>
.slide-up-enter-active, .slide-up-leave-active { transition: all .3s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(16px); }
</style>
