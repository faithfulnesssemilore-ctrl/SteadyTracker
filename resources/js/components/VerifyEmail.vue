<template>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <div class="auth-page">
    <aside class="auth-hero">
      <div class="brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>
      <h1 class="hero-heading">Background Design</h1>
      <p class="hero-sub">Silk · Warm · Calm · Focused</p>
    </aside>

    <main class="auth-form-panel">
      <div class="mobile-brand">
        <span class="brand-badge">✓</span>
        <span class="brand-name">Steady<span class="accent">Tracker</span></span>
      </div>

      <div class="auth-card verify-card">
        <div class="verify-icon">✉️</div>
        <h2 class="card-heading">Verify your email</h2>
        <p class="card-sub">We've sent a verification link to</p>

        <div class="email-chip">{{ authStore.user?.email }}</div>

        <p class="verify-instructions">
          Please check your inbox and click on the link to verify your email address.
        </p>

        <ul class="verify-steps">
          <li>
            <span class="step-icon">📧</span>
            <span>Check your inbox</span>
          </li>
          <li>
            <span class="step-icon">🔗</span>
            <span>Click the verification link</span>
          </li>
          <li>
            <span class="step-icon">🛡️</span>
            <span>Your email will be verified</span>
          </li>
          <li>
            <span class="step-icon">📈</span>
            <span>Start tracking your life</span>
          </li>
        </ul>

        <p class="resend-row">
          Didn't receive the email?
          <button type="button" class="link-button" @click="handleResend" :disabled="resending">
            {{ resending ? 'Sending…' : 'Resend email' }}
          </button>
        </p>

        <p v-if="resendMessage" class="success-text">{{ resendMessage }}</p>
        <p v-if="verifiedMessage" class="success-text">{{ verifiedMessage }}</p>
        <p v-if="resendError" class="error-text">{{ resendError }}</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { ensureCsrfCookie, getApiError } from '../services/api';
import { useAuthStore } from '../stores/auth';
import '../styles/auth.css';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const resending = ref(false);
const resendMessage = ref('');
const resendError = ref('');
const verifiedMessage = ref('');
let pollTimer: ReturnType<typeof setInterval> | null = null;
let pollAttempts = 0;
const maxPollAttempts = 75;

function stopPolling() {
  if (pollTimer !== null) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

async function checkVerification() {
  await authStore.fetchUser();
  pollAttempts += 1;

  if (authStore.emailVerified) {
    stopPolling();
    router.push('/dashboard');
  } else if (pollAttempts >= maxPollAttempts) {
    stopPolling();
    resendError.value = 'We could not confirm verification yet. Refresh this page after verifying your email.';
  }
}

onMounted(() => {
  if (route.query.verified === '1') {
    verifiedMessage.value = 'Your email has been verified. Redirecting to your dashboard...';
    setTimeout(() => router.push('/dashboard'), 900);
  }

  pollTimer = setInterval(checkVerification, 4000);
});

onUnmounted(() => {
  stopPolling();
});

async function handleResend() {
  resending.value = true;
  resendMessage.value = '';
  resendError.value = '';

  try {
    await ensureCsrfCookie();
    const { data } = await api.post('/email/verification-notification');
    resendMessage.value = data.message ?? 'Verification email sent.';
  } catch (e) {
    resendError.value = getApiError(e, 'Could not resend email.').message;
  } finally {
    resending.value = false;
  }
}
</script>

<style scoped>
.verify-card {
  text-align: center;
}

.verify-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.email-chip {
  display: inline-block;
  padding: 0.5rem 1rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
  margin: 0.75rem 0 1rem;
}

.verify-instructions {
  color: var(--muted);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.verify-steps {
  list-style: none;
  padding: 0;
  margin: 0 0 1.5rem;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.verify-steps li {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--muted);
}

.step-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--cream-deep);
  font-size: 1.1rem;
}

.resend-row {
  font-size: 0.85rem;
  color: var(--muted);
  margin-bottom: 0.5rem;
}

.link-button {
  background: none;
  border: none;
  color: var(--accent);
  font-weight: 600;
  cursor: pointer;
  font-size: 0.85rem;
  padding: 0;
}

.link-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (min-width: 900px) {
  .verify-steps {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>