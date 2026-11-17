import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)
  const hasAgreement    = computed(() => !!user.value?.active_agreement)
  const walletBalance   = computed(() => user.value?.wallet_balance ?? 0)

  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
  }

  async function fetchMe() {
    const { data } = await api.get('/auth/me')
    user.value = data
  }

  async function updateLocale(locale) {
    await api.patch('/auth/locale', { locale })
    if (user.value) user.value.preferred_locale = locale
  }

  function refreshBalance(newBalance) {
    if (user.value) user.value.wallet_balance = newBalance
  }

  return { user, token, isAuthenticated, hasAgreement, walletBalance,
           login, register, logout, fetchMe, updateLocale, refreshBalance }
})
