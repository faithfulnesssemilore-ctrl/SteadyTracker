<template>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <div class="auth-page">
    <aside class="auth-hero">
      <div class="brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <h1 class="hero-heading">Create a new password,<br /><span class="accent">take back control</span></h1>
      <p class="hero-sub">Your new password must be strong and something only you know.</p>
      <p class="hero-footer">© 2026 SteadyTracker. All rights reserved.</p>
    </aside>

    <main class="auth-form-panel">
      <div class="mobile-brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>

      <div class="auth-card verify-card" v-if="step === 'form'">
        <div class="verify-icon">🔓</div>
        <h2 class="card-heading">Reset password</h2>
        <p class="card-sub">Create a new password for your account.</p>

        <form @submit.prevent="handleSubmit" novalidate>
          <label class="field">
            <span class="field-label">New password</span>
            <div class="password-wrap">
              <input :type="showPassword ? 'text' : 'password'" v-model="password" placeholder="Enter new password" autocomplete="new-password" required />
              <button type="button" class="toggle-visibility" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                {{ showPassword ? '🙈' : '👁' }}
              </button>
            </div>
            <ul class="requirements">
              <li :class="{ met: hasMinLength }">{{ hasMinLength ? '✓' : '○' }} 8+ characters</li>
              <li :class="{ met: hasNumber }">{{ hasNumber ? '✓' : '○' }} One number</li>
              <li :class="{ met: hasSpecialChar }">{{ hasSpecialChar ? '✓' : '○' }} One special character</li>
            </ul>
          </label>

          <label class="field">
            <span class="field-label">Confirm new password</span>
            <input :type="showPassword ? 'text' : 'password'" v-model="passwordConfirmation" placeholder="Confirm new password" autocomplete="new-password" required />
          </label>

          <p v-if="error" class="error-text">{{ error }}</p>

          <button type="submit" class="btn-primary" :disabled="loading || !canSubmit">
            {{ loading ? 'Resetting…' : 'Reset password' }}
          </button>
        </form>

        <p class="card-footer"><RouterLink to="/login" class="link">Back to login</RouterLink></p>
      </div>

      <div class="auth-card verify-card" v-else>
        <div class="verify-icon">🔒</div>
        <h2 class="card-heading">Password reset!</h2>
        <p class="card-sub">Your password has been updated successfully.</p>

        <button type="button" class="btn-primary" @click="router.push('/login')">Go to login</button>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api, { ensureCsrfCookie, getApiError } from '../services/api';
import '../styles/auth.css';

const query = new URLSearchParams(window.location.search);
const router = useRouter();
const email = query.get('email') ?? '';
const token = query.get('token') ?? '';

const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const step = ref('form');
const loading = ref(false);
const error = ref('');

const hasMinLength = computed(() => password.value.length >= 8);
const hasNumber = computed(() => /\d/.test(password.value));
const hasSpecialChar = computed(() => /[^A-Za-z0-9]/.test(password.value));
const canSubmit = computed(() =>
  hasMinLength.value && hasNumber.value && hasSpecialChar.value &&
  password.value === passwordConfirmation.value
);

async function handleSubmit() {
  loading.value = true;
  error.value = '';

  try {
    await ensureCsrfCookie();
    await api.post('/reset-password', {
      token,
      email,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    step.value = 'success';
  } catch (e) {
    error.value = getApiError(e, 'Invalid or expired reset link.').message;
  } finally {
    loading.value = false;
  }
}
</script>