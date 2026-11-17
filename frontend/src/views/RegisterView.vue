<template>
  <div class="min-h-screen bg-gradient-to-br from-bkash to-bkash-dark flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4">
          <span class="text-bkash font-black text-3xl">b</span>
        </div>
        <h1 class="text-white text-2xl font-bold">bKash Wallet</h1>
        <p class="text-pink-200 text-sm mt-1">{{ t('auth.register') }}</p>
      </div>

      <div class="card">
        <div v-if="serverError"
             class="flex items-start gap-2 bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
          <span>⚠️</span><span>{{ serverError }}</span>
        </div>

        <form @submit.prevent="handleRegister" novalidate class="space-y-4">

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.name') }}</label>
            <input v-model="form.name" type="text" :placeholder="t('auth.name')"
              :class="fieldClass('name')" @blur="touch('name')" @input="touch('name')" />
            <FieldMsg :touched="touched.name" :error="errors.name" :value="form.name" :ok="t('auth.looks_good')" />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.email') }}</label>
            <input v-model="form.email" type="email" :placeholder="t('auth.email')"
              autocomplete="email" :class="fieldClass('email')"
              @blur="touch('email')" @input="touch('email')" />
            <FieldMsg :touched="touched.email" :error="errors.email" :value="form.email" :ok="t('auth.looks_good')" />
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.phone') }}</label>
            <input v-model="form.phone" type="tel" placeholder="01XXXXXXXXX" maxlength="11"
              :class="fieldClass('phone')" @blur="touch('phone')" @input="touch('phone')" />
            <p class="text-gray-400 text-xs mt-1">{{ t('auth.phone_hint') }}</p>
            <FieldMsg :touched="touched.phone" :error="errors.phone" :value="form.phone" :ok="t('auth.looks_good')" />
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.password') }}</label>
            <div class="relative">
              <input v-model="form.password" :type="showPass ? 'text' : 'password'"
                :placeholder="t('auth.pass_hint')"
                :class="[fieldClass('password'), 'pr-10']"
                @blur="touch('password')" @input="onPassInput" />
              <button type="button" @click="showPass = !showPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm"
                :aria-label="t('auth.toggle_pass')">{{ showPass ? '🙈' : '👁' }}</button>
            </div>
            <!-- Strength bar -->
            <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-300"
                   :style="{ width: strengthWidth, background: strengthColor }" />
            </div>
            <p v-if="strengthLabel" class="text-xs mt-1 font-medium" :style="{ color: strengthColor }">
              {{ strengthLabel }}
            </p>
            <FieldMsg :touched="touched.password" :error="errors.password" :value="form.password" :ok="t('auth.looks_good')" />
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('auth.confirm_password') }}</label>
            <input v-model="form.password_confirmation" type="password" placeholder="••••••••"
              :class="fieldClass('password_confirmation')"
              @blur="touch('password_confirmation')" @input="touch('password_confirmation')" />
            <FieldMsg :touched="touched.password_confirmation" :error="errors.password_confirmation"
              :value="form.password_confirmation" :ok="t('auth.looks_good')" />
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="loading" class="animate-spin inline-block">⏳</span>
            {{ t('auth.sign_up') }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-5">
          {{ t('auth.have_account') }}
          <RouterLink to="/login" class="text-bkash font-semibold hover:underline">{{ t('auth.sign_in') }}</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/store/modules/auth'
import { useValidation, required, email, minLength, bdPhone, passwordStrong, confirmed } from '@/composables/useValidation'

const { t } = useI18n()
const auth   = useAuthStore()
const router = useRouter()

const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const touched = reactive({ name: false, email: false, phone: false, password: false, password_confirmation: false })
const loading     = ref(false)
const serverError = ref('')
const showPass    = ref(false)

const { errors, validate, validateAll } = useValidation({
  name:                  [required(t('validation.name_required')), minLength(2, t('validation.name_min'))],
  email:                 [required(t('validation.email_required')), email(t('validation.email_invalid'))],
  phone:                 [required(t('validation.phone_required')), bdPhone(t('validation.phone_invalid'))],
  password:              [required(t('validation.password_required')), passwordStrong(t('validation.pass_weak'))],
  password_confirmation: [required(t('validation.pass_confirm')), confirmed('password', t('validation.pass_mismatch'))],
})

// Password strength
const strengthScore = ref(0)
const strengthMap = computed(() => [
  { w: '0%',   c: '#e5e7eb', l: '' },
  { w: '25%',  c: '#e24b4a', l: t('strength.weak') },
  { w: '50%',  c: '#ef9f27', l: t('strength.fair') },
  { w: '75%',  c: '#378add', l: t('strength.good') },
  { w: '100%', c: '#639922', l: t('strength.strong') },
])
const strengthWidth = computed(() => strengthMap.value[strengthScore.value].w)
const strengthColor = computed(() => strengthMap.value[strengthScore.value].c)
const strengthLabel = computed(() => strengthMap.value[strengthScore.value].l)

function onPassInput() {
  const v = form.password
  let s = 0
  if (v.length >= 8) s++
  if (/[A-Z]/.test(v)) s++
  if (/[0-9]/.test(v)) s++
  if (/[^A-Za-z0-9]/.test(v)) s++
  strengthScore.value = s
  touch('password')
  if (touched.password_confirmation) touch('password_confirmation')
}

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

// Inline field message component
const FieldMsg = {
  props: ['touched', 'error', 'value', 'ok'],
  template: `
    <p v-if="touched && error" class="text-red-600 text-xs mt-1 flex items-center gap-1"><span>⚠</span>{{ error }}</p>
    <p v-else-if="touched && !error && value" class="text-green-600 text-xs mt-1 flex items-center gap-1"><span>✓</span>{{ ok }}</p>
  `
}

async function handleRegister() {
  Object.keys(touched).forEach(f => { touched[f] = true })
  if (!validateAll(form)) return
  loading.value = true
  serverError.value = ''
  try {
    await auth.register(form)
    router.push('/dashboard')
  } catch (e) {
    const errs = e.response?.data?.errors
    serverError.value = errs
      ? Object.values(errs).flat().join(' ')
      : (e.response?.data?.message || t('validation.register_failed'))
  } finally {
    loading.value = false
  }
}
</script>
