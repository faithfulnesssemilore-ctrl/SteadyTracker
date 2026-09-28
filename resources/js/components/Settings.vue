<template>
  <div class="dashboard-shell settings-shell">
    <aside class="sidebar">
      <div class="brand-row">
        <div class="brand-mark">✓</div>
        <div class="brand-name">SteadyTracker</div>
      </div>

      <nav class="sidebar-nav" aria-label="Main navigation">
        <button class="nav-item" type="button" @click="go('/dashboard')">
          <span class="nav-icon"><House :size="18" /></span>
          <span>Home</span>
        </button>
        <button class="nav-item active" type="button" aria-current="page" @click="go('/inbox')">
          <span class="nav-icon"><Mail :size="18" /></span>
          <span>Inbox</span>
        </button>
        <button class="nav-item" type="button" @click="go('/activities')">
          <span class="nav-icon"><CircleCheckBig :size="18" /></span>
          <span>My Tasks</span>
        </button>
        <button class="nav-item" type="button" @click="go('/import')">
          <span class="nav-icon"><Import :size="18" /></span>
          <span>Import</span>
        </button>
        <button class="nav-item" type="button" @click="go('/settings')">
          <span class="nav-icon"><Settings :size="18" /></span>
          <span>Settings</span>
        </button>
      </nav>
      

      <div class="home-sidebar-art" aria-hidden="true"></div>

   <div class="profile-card">
        <div class="avatar small">{{ userInitial }}</div>
        <span>{{ displayName }}</span>
      </div>
    </aside>

    <main class="dashboard-main home-main">
      <header class="topbar">
        <div class="topbar-spacer"></div>

        <div class="topbar-actions">
          <div class="avatar large">{{ userInitial }}</div>
          <span class="home-user-name">{{ displayName }}</span>
        </div>
      </header>

      <section class="settings-content">
        <div class="settings-heading">
          <h1>Settings</h1>
          <p>Manage your account settings.</p>
        </div>

        <button class="logout-card" :disabled="loggingOut" @click="logout">
          <span class="logout-icon">⇥</span>
          <span class="logout-copy"><strong>{{ loggingOut ? 'Logging out...' : 'Log out' }}</strong><small>Sign out of your account.</small></span>
          <span class="logout-arrow">›</span>
        </button>

        <p v-if="error" class="settings-error">{{ error }}</p>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { CircleCheckBig, House, Import, Mail, Settings } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const loggingOut = ref(false);
const error = ref('');
const displayName = computed(() => authStore.user?.name || authStore.user?.user_name || 'Your account');
const userInitial = computed(() => displayName.value.charAt(0).toUpperCase() || 'S');

function go(path: string): void {
  router.push(path);
}

async function logout() {
  loggingOut.value = true;
  error.value = '';

  try {
    await authStore.logout();
    await router.push('/login');
  } catch {
    error.value = 'Unable to log out right now. Please try again.';
  } finally {
    loggingOut.value = false;
  }
}
</script>
