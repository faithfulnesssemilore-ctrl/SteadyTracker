<template>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <div class="auth-page">
    <!-- Marketing panel: hidden on mobile, shown from tablet up -->
    <aside class="auth-hero">
      <div class="brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <h1 class="hero-heading">Track your day.<br /><span class="accent">Build your future.</span></h1>
      <p class="hero-sub">Plan your activities, stay consistent, and become a better version of yourself.</p>
      <ul class="hero-features">
        <li><span class="feature-icon">📅</span><div><strong>Plan your day</strong><p>Create and schedule activities that matter.</p></div></li>
        <li><span class="feature-icon">✅</span><div><strong>Track & Reflect</strong><p>Mark progress and write your daily reviews.</p></div></li>
        <li><span class="feature-icon">📈</span><div><strong>See your growth</strong><p>Understand your patterns and stay motivated.</p></div></li>
      </ul>
      <p class="hero-footer">© 2026 SteadyTracker. All rights reserved.</p>
    </aside>

    <!-- Form panel -->
    <main class="auth-form-panel">
      <div class="mobile-brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <div class="auth-card">
        <h2 class="card-heading">Welcome back 👋</h2>
        <p class="card-sub">Sign in to continue to your account</p>
        <nav class="tabs">
          <RouterLink to="/login" class="tab tab-active">Login</RouterLink>
          <RouterLink to="/register" class="tab">Register</RouterLink>
        </nav>
        <form @submit.prevent="handleSubmit" novalidate>
          <label class="field">
            <span class="field-label">Email address</span>
            <input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" required />
          </label>
          <label class="field">
            <span class="field-label">Password</span>
            <div class="password-wrap">
              <input :type="showPassword ? 'text' : 'password'" v-model="password" placeholder="Enter your password" autocomplete="current-password" required />
              <button type="button" class="toggle-visibility" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                {{ showPassword ? '🙈' : '👁' }}
              </button>
            </div>
          </label>
          <div class="row-between">
            <label class="remember">
              <input type="checkbox" v-model="remember" />
              <span>Remember me</span>
            </label>
            <RouterLink to="/forgot-password" class="link">Forgot password?</RouterLink>
          </div>
          <p v-if="authStore.error" class="error-text">{{ authStore.error }}</p>
          <button type="submit" class="btn-primary" :disabled="authStore.loading">
            {{ authStore.loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>
        <!-- Google OAuth is not enabled with Fortify. -->
        <!--
          <svg class="google-icon" viewBox="0 0 48 48" width="18" height="18">
  <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-4z"/>
  <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6.1 29.6 4 24 4c-7.7 0-14.4 4.3-17.7 10.7z"/>
  <path fill="#4CAF50" d="M24 44c5.5 0 10.4-2.1 14.1-5.6l-6.5-5.5C29.6 34.7 27 35.6 24 35.6c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.6 39.6 16.3 44 24 44z"/>
  <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.2 5.7l6.5 5.5C41.5 36 44 30.9 44 24c0-1.3-.1-2.7-.4-3.5z"/>
</svg>  Continue with Google
        </a> -->
        <p class="card-footer">Don't have an account? <RouterLink to="/register" class="link">Create one</RouterLink></p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import '../styles/auth.css';

const authStore = useAuthStore();
const router = useRouter();
const email = ref('');
const password = ref('');
const remember = ref(true);
const showPassword = ref(false);

async function handleSubmit() {
  const ok = await authStore.login({
    email: email.value,
    password: password.value,
    remember: remember.value,
  });

  if (ok) {
    router.push(authStore.emailVerified ? '/dashboard' : '/verify-email');
  }
}
</script>
