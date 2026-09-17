<template>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <div class="auth-page">
    <aside class="auth-hero">
      <div class="brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <h1 class="hero-heading">Create your account,<br /><span class="accent">build your best life.</span></h1>
      <p class="hero-sub">Join SteadyTracker and start tracking your activities, building consistency, and becoming a better you.</p>
      <ul class="hero-features">
        <li><span class="feature-icon">📅</span><div><strong>Plan your day</strong><p>Organize your activities and stay on track.</p></div></li>
        <li><span class="feature-icon">📈</span><div><strong>Track progress</strong><p>Monitor your habits and see your growth.</p></div></li>
        <li><span class="feature-icon">🛡️</span><div><strong>Stay consistent</strong><p>Build discipline with daily tracking and reminders.</p></div></li>
      </ul>
      <p class="hero-footer">© 2026 SteadyTracker. All rights reserved.</p>
    </aside>

    <main class="auth-form-panel">
      <div class="mobile-brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>

      <div class="auth-card">
        <h2 class="card-heading">Create your account</h2>
        <p class="card-sub">Start your journey to a better you.</p>

        <nav class="tabs">
          <RouterLink to="/login" class="tab">Login</RouterLink>
          <RouterLink to="/register" class="tab tab-active">Register</RouterLink>
        </nav>

        <form @submit.prevent="handleSubmit" novalidate>
          <div class="name-row">
            <label class="field">
              <span class="field-label">First name</span>
              <input v-model="firstName" type="text" placeholder="John" autocomplete="given-name" required />
              <span v-if="authStore.fieldErrors.name" class="field-error">{{ authStore.fieldErrors.name[0] }}</span>
            </label>
            <label class="field">
              <span class="field-label">Last name</span>
              <input v-model="lastName" type="text" placeholder="Doe" autocomplete="family-name" required />
            </label>
          </div>

          <label class="field">
            <span class="field-label">Email address</span>
            <input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" required />
            <span v-if="authStore.fieldErrors.email" class="field-error">{{ authStore.fieldErrors.email[0] }}</span>
          </label>

          <label class="field">
            <span class="field-label">Password</span>
            <div class="password-wrap">
              <input :type="showPassword ? 'text' : 'password'" v-model="password" placeholder="Create a password" autocomplete="new-password" required />
              <button type="button" class="toggle-visibility" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                {{ showPassword ? '🙈' : '👁' }}
              </button>
            </div>
            <ul class="requirements">
              <li :class="{ met: hasMinLength }">{{ hasMinLength ? '✓' : '○' }} 8+ characters</li>
              <li :class="{ met: hasNumber }">{{ hasNumber ? '✓' : '○' }} One number</li>
              <li :class="{ met: hasSpecialChar }">{{ hasSpecialChar ? '✓' : '○' }} One special character</li>
            </ul>
            <span v-if="authStore.fieldErrors.password" class="field-error">{{ authStore.fieldErrors.password[0] }}</span>
          </label>

          <label class="field">
            <span class="field-label">Confirm password</span>
            <input :type="showPassword ? 'text' : 'password'" v-model="passwordConfirmation" placeholder="Confirm your password" autocomplete="new-password" required />
          </label>

          <label class="terms-check">
            <input type="checkbox" v-model="agreedToTerms" required />
            <span>I agree to the <a href="/terms" class="link">Terms of Service</a> and <a href="/privacy" class="link">Privacy Policy</a></span>
          </label>

          <p v-if="authStore.error" class="error-text">{{ authStore.error }}</p>
          <p v-if="successMessage" class="success-text">{{ successMessage }}</p>

          <button type="submit" class="btn-primary" :disabled="authStore.loading || !canSubmit">
            {{ authStore.loading ? 'Creating account…' : 'Create account' }}
          </button>
        </form>

        <!--
        <div class="divider"><span>or continue with</span></div>
        <a :href="googleRedirectUrl" class="btn-google">
          <span class="google-g">G</span> Continue with Google
        </a> -->

        <p class="card-footer">Already have an account? <RouterLink to="/login" class="link">Login</RouterLink></p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import '../styles/auth.css';

const authStore = useAuthStore();
const router = useRouter();

const firstName = ref('');
const lastName = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const agreedToTerms = ref(false);
const showPassword = ref(false);
const successMessage = ref('');

const hasMinLength = computed(() => password.value.length >= 8);
const hasNumber = computed(() => /\d/.test(password.value));
const hasSpecialChar = computed(() => /[^A-Za-z0-9]/.test(password.value));

const canSubmit = computed(() =>
  firstName.value && lastName.value && email.value &&
  hasMinLength.value && hasNumber.value && hasSpecialChar.value &&
  password.value === passwordConfirmation.value && agreedToTerms.value
);

async function handleSubmit() {
  const userName = `${firstName.value.trim()} ${lastName.value.trim()}`.trim();

  const ok = await authStore.register({
    userName,
    email: email.value,
    password: password.value,
    passwordConfirmation: passwordConfirmation.value,
  });

  if (ok) {
    successMessage.value = 'Account created! Redirecting to verify your email…';
    setTimeout(() => router.push('/verify-email'), 1200);
  }
}
</script>

