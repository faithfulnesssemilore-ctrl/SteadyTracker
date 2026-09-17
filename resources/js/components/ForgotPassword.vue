<template>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <div class="auth-page">
    <aside class="auth-hero">
      <div class="brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <h1 class="hero-heading">Reset your password,<br /><span class="accent">regain access</span></h1>
      <p class="hero-sub">Enter your email address and we'll send you a link to reset your password.</p>
      <p class="hero-footer">© 2026 SteadyTracker. All rights reserved.</p>
    </aside>

    <main class="auth-form-panel">
      <div class="mobile-brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>

      <div class="auth-card verify-card" v-if="step === 'form'">
        <div class="verify-icon">🔒</div>
        <h2 class="card-heading">Forgot password?</h2>
        <p class="card-sub">No worries! It happens. Enter your email and we'll send you reset instructions.</p>

        <form @submit.prevent="handleSubmit" novalidate>
          <label class="field">
            <span class="field-label">Email address</span>
            <input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" required />
          </label>

          <p v-if="error" class="error-text">{{ error }}</p>

          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? 'Sending…' : 'Send reset link' }}
          </button>
        </form>

        <p class="card-footer">Remembered your password? <RouterLink to="/login" class="link">Back to login</RouterLink></p>
      </div>

      <div class="auth-card verify-card" v-else>
        <div class="verify-icon">✉️</div>
        <h2 class="card-heading">Check your email</h2>
        <p class="card-sub">We've sent a password reset link to</p>
        <div class="email-chip">{{ email }}</div>
        <p class="verify-instructions">If you don't see it, check your spam folder.</p>

        <button type="button" class="btn-secondary" @click="handleSubmit" :disabled="loading">
          {{ loading ? 'Sending…' : '↻ Resend email' }}
        </button>

        <p class="card-footer"><RouterLink to="/login" class="link">Back to login</RouterLink></p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { ensureCsrfCookie, getApiError } from '../services/api';
import '../styles/auth.css';

const email = ref('');
const step = ref('form');
const loading = ref(false);
const error = ref('');

async function handleSubmit() {
  loading.value = true;
  error.value = '';

  try {
    await ensureCsrfCookie();
    await api.post('/forgot-password', { email: email.value });
    step.value = 'sent';
  } catch (e) {
    error.value = getApiError(e, 'Unable to send reset link.').message;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.btn-secondary {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: white;
  color: var(--ink);
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  margin: 1rem 0;
}
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }
</style>