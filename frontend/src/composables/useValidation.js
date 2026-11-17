import { reactive } from 'vue'

export function useValidation(rules) {
  const errors = reactive({})

  function validate(field, value, formValues = {}) {
    const fieldRules = rules[field]
    if (!fieldRules) { errors[field] = null; return true }
    for (const rule of fieldRules) {
      const msg = rule(value, formValues)
      if (msg) { errors[field] = msg; return false }
    }
    errors[field] = null
    return true
  }

  function validateAll(formValues) {
    let valid = true
    for (const field of Object.keys(rules)) {
      if (!validate(field, formValues[field] ?? '', formValues)) valid = false
    }
    return valid
  }

  function clearErrors() {
    for (const k of Object.keys(errors)) errors[k] = null
  }

  return { errors, validate, validateAll, clearErrors }
}

// ── Rule factories — all accept a custom message as first arg ─────────────────

export const required = (msg = 'This field is required.') =>
  (v) => (!v && v !== 0 || (typeof v === 'string' && !v.trim())) ? msg : null

export const email = (msg = 'Enter a valid email address.') =>
  (v) => v && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? msg : null

export const minLength = (n, msg) =>
  (v) => v && v.length < n ? (msg || `Must be at least ${n} characters.`) : null

export const maxLength = (n, msg) =>
  (v) => v && v.length > n ? (msg || `Must not exceed ${n} characters.`) : null

export const bdPhone = (msg = 'Enter a valid Bangladeshi phone number.') =>
  (v) => v && !/^01[3-9]\d{8}$/.test(v) ? msg : null

export const passwordStrong = (msg) =>
  (v) => {
    if (!v) return msg || 'Password is required.'
    if (v.length < 8 || !/[A-Z]/.test(v) || !/[0-9]/.test(v))
      return msg || 'Password must be at least 8 chars, include uppercase and a number.'
    return null
  }

export const confirmed = (passwordField = 'password', msg = 'Passwords do not match.') =>
  (v, form) => v !== form[passwordField] ? msg : null

export const numericMin = (min, msg) =>
  (v) => { const n = Number(v); return isNaN(n) || n < min ? (msg || `Minimum value is ${min}.`) : null }

export const numericMax = (max, msg) =>
  (v) => { const n = Number(v); return isNaN(n) || n > max ? (msg || `Maximum value is ${max}.`) : null }

export const integer = (msg = 'Must be a whole number.') =>
  (v) => { const n = Number(v); return !Number.isInteger(n) ? msg : null }

export const dateNotAfter = (otherField, msg = 'Start date cannot be after end date.') =>
  (v, form) => { const other = form[otherField]; return v && other && v > other ? msg : null }
