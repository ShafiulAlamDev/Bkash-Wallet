<template>
  <div class="min-h-screen bg-gradient-to-br from-bkash to-bkash-dark flex items-center justify-center p-4">
    <div class="w-full max-w-md">

      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4">
          <span class="text-bkash font-black text-3xl">b</span>
        </div>
        <h1 class="text-white text-2xl font-bold">bKash Wallet</h1>
        <p class="text-pink-200 text-sm mt-1">{{ t('auth.login') }}</p>
      </div>

      <div class="card">
        <div v-if="serverError"
             class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
          <span>⚠️</span><span>{{ serverError }}</span>
        </div>

        <form @submit.prevent="handleLogin" novalidate class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.email') }}</label>
            <input v-model="form.email" type="email" :placeholder="t('auth.email')"
              autocomplete="email" :class="fieldClass('email')"
              @blur="touch('email')" @input="touch('email')" />
            <p v-if="touched.email && errors.email" class="text-red-600 text-xs mt-1 flex items-center gap-1">
              <span>⚠</span>{{ errors.email }}
            </p>
            <p v-else-if="touched.email && !errors.email && form.email" class="text-green-600 text-xs mt-1 flex items-center gap-1">
              <span>✓</span> {{ t('auth.looks_good') }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.password') }}</label>
            <div class="relative">
              <input v-model="form.password" :type="showPass ? 'text' : 'password'"
                placeholder="••••••••" autocomplete="current-password"
                :class="[fieldClass('password'), 'pr-10']"
                @blur="touch('password')" @input="touch('password')" />
              <button type="button" @click="showPass = !showPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm"
                :aria-label="showPass ? t('auth.toggle_pass_hide') : t('auth.toggle_pass_show')">
                {{ showPass ? '🙈' : '👁' }}
              </button>
            </div>
            <p v-if="touched.password && errors.password" class="text-red-600 text-xs mt-1 flex items-center gap-1">
              <span>⚠</span>{{ errors.password }}
            </p>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="loading" class="animate-spin inline-block">⏳</span>
            {{ t('auth.sign_in') }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-5">
          {{ t('auth.no_account') }}
          <RouterLink to="/register" class="text-bkash font-semibold hover:underline">{{ t('auth.sign_up') }}</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/store/modules/auth'
import { useValidation, required, email } from '@/composables/useValidation'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const form    = reactive({ email: '', password: '' })
const touched = reactive({ email: false, password: false })
const loading     = ref(false)
const serverError = ref('')
const showPass    = ref(false)

const { errors, validate, validateAll } = useValidation({
  email:    [required(t('validation.email_required')), email(t('validation.email_invalid'))],
  password: [required(t('validation.password_required'))],
})

function touch(field) {
  touched[field] = true
  validate(field, form[field], form)
}

function fieldClass(field) {
  const base = 'input w-full'
  if (!touched[field]) return base
  if (errors[field]) return base + ' !border-red-400 bg-red-50'
  return base + ' !border-green-400'
}

async function handleLogin() {
  Object.keys(touched).forEach(f => { touched[f] = true })
  if (!validateAll(form)) return
  loading.value = true
  serverError.value = ''
  try {
    await auth.login(form)
    router.push('/dashboard')
  } catch (e) {
    serverError.value = e.response?.data?.message || t('validation.credentials')
  } finally {
    loading.value = false
  }
}
</script>
