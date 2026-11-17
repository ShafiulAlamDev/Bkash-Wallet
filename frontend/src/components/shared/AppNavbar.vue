<template>
  <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">

      <!-- Brand -->
      <RouterLink to="/dashboard" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-bkash flex items-center justify-center">
          <span class="text-white font-bold text-sm">b</span>
        </div>
        <span class="font-bold text-gray-800 text-lg tracking-tight">bKash Wallet</span>
      </RouterLink>

      <!-- Nav links -->
      <div class="hidden sm:flex items-center gap-1">
        <RouterLink v-for="link in links" :key="link.to" :to="link.to"
          class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-bkash-light hover:text-bkash transition-colors"
          active-class="bg-bkash-light text-bkash">
          {{ t(link.label) }}
        </RouterLink>
      </div>

      <!-- Right controls -->
      <div class="flex items-center gap-3">
        <!-- Language Toggle -->
        <button @click="toggleLocale"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:border-bkash hover:text-bkash transition-colors">
          <span>🌐</span>
          <span>{{ locale === 'en' ? 'বাংলা' : 'English' }}</span>
        </button>

        <!-- Balance chip -->
        <div class="hidden sm:flex items-center gap-1 px-3 py-1.5 bg-bkash-light rounded-lg">
          <span class="text-xs text-bkash font-medium">৳</span>
          <span class="text-sm font-bold text-bkash">{{ Number(auth.walletBalance).toFixed(2) }}</span>
        </div>

        <!-- Logout -->
        <button @click="handleLogout"
          class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
          {{ t('nav.logout') }}
        </button>
      </div>

    </div>
  </nav>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/modules/auth'

const { t, locale } = useI18n()
const auth   = useAuthStore()
const router = useRouter()

const links = [
  { to: '/dashboard',    label: 'nav.dashboard' },
  { to: '/transactions', label: 'nav.history'   },
]

async function toggleLocale() {
  const next = locale.value === 'en' ? 'bn' : 'en'
  locale.value = next
  localStorage.setItem('locale', next)
  if (auth.isAuthenticated) await auth.updateLocale(next)
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
