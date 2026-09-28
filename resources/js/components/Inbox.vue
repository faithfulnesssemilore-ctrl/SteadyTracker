<template>
  <div class="dashboard-shell inbox-shell">
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

      <div class="profile-card inbox-profile">
        <div class="avatar small">{{ userInitial }}</div>
        <span>{{ displayName }}</span>
        <ChevronRight class="profile-chevron" :size="15" />
      </div>
    </aside>

    <main class="dashboard-main inbox-main">
      <header class="topbar inbox-topbar">
        <label class="search-box inbox-search">
          <Search :size="16" aria-hidden="true" />
          <input v-model="search" type="search" placeholder="Search notifications..." aria-label="Search notifications" />
        </label>

        <div class="inbox-top-actions">
          <button class="inbox-bell" type="button" aria-label="Notifications" @click="fetchNotifications">
            <Bell :size="19" />
            <span v-if="unreadCount" class="inbox-unread-dot" aria-label="Unread notifications"></span>
          </button>
          <div class="inbox-user">
            <div class="avatar large">{{ userInitial }}</div>
            <span>{{ shortName }}</span>
            <ChevronDown :size="14" />
          </div>
        </div>
      </header>

      <section class="inbox-content" aria-labelledby="inbox-title">
        <div class="inbox-heading-row">
          <div>
            <h1 id="inbox-title">Inbox</h1>
            <p>Here are your recent notifications and updates.</p>
          </div>
          <button class="inbox-refresh" type="button" :disabled="isLoading" aria-label="Refresh notifications" @click="fetchNotifications">
            <RefreshCw :size="16" :class="{ 'spin': isLoading }" />
          </button>
        </div>

        <div v-if="error" class="inbox-state inbox-error" role="alert">
          <p>{{ error }}</p>
          <button type="button" @click="fetchNotifications">Try again</button>
        </div>
        <div v-else-if="isLoading && !notifications.length" class="inbox-state" role="status">
          <LoaderCircle class="spin" :size="20" />
          <span>Loading notifications</span>
        </div>
        <div v-else-if="!filteredNotifications.length" class="inbox-state inbox-empty">
          <Bell :size="22" />
          <strong>{{ search ? 'No matching notifications' : 'Your inbox is clear' }}</strong>
          <span>{{ search ? 'Try another search.' : 'Activity updates will appear here.' }}</span>
        </div>
        <div v-else class="inbox-list" aria-live="polite">
          <article v-for="notification in filteredNotifications" :key="notification.id" class="inbox-item">
            <span class="inbox-item-icon" :class="`tone-${notificationTone(notification)}`" aria-hidden="true">
              <component :is="notificationIcon(notification)" :size="17" />
            </span>
            <div class="inbox-item-copy">
              <h2>{{ notification.title }}</h2>
              <p>{{ notification.message }}</p>
            </div>
            <time class="inbox-item-time" :datetime="notification.created_at || undefined">
              {{ formatRelativeTime(notification.created_at) }}
            </time>
            <ChevronRight class="inbox-item-chevron" :size="16" aria-hidden="true" />
          </article>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import {
  Bell,
  CalendarClock,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  CircleCheckBig,
  Info,
  Import,
  LoaderCircle,
  Mail,
  RefreshCw,
  Search,
  Settings,
  UserRound,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import notificationsApi from '../api/notifications';
import { useAuthStore } from '../stores/auth';

interface NotificationItem {
  id: string;
  title: string;
  message: string;
  status: string | null;
  type?: string;
  created_at: string | null;
  read_at?: string | null;
}

const router = useRouter();
const authStore = useAuthStore();
const notifications = ref<NotificationItem[]>([]);
const search = ref('');
const isLoading = ref(false);
const error = ref('');
const displayName = computed(() => authStore.user?.name || authStore.user?.user_name || 'Your account');
const shortName = computed(() => displayName.value.split(' ').slice(0, 2).join(' '));
const userInitial = computed(() => displayName.value.charAt(0).toUpperCase());
const unreadCount = computed(() => notifications.value.filter((item) => !item.read_at).length);
const filteredNotifications = computed(() => {
  const query = search.value.trim().toLowerCase();

  if (!query) {
return notifications.value;
}

  return notifications.value.filter((item) => `${item.title} ${item.message}`.toLowerCase().includes(query));
});
let notificationChannel: ReturnType<typeof Echo.private> | null = null;

function go(path: string): void {
  router.push(path);
}

async function fetchNotifications(): Promise<void> {
  isLoading.value = true;
  error.value = '';

  try {
    const { data } = await notificationsApi.fetchAll();
    notifications.value = (Array.isArray(data) ? data : data.data ?? []).map((item: NotificationItem) => ({
      ...item,
      title: item.title || 'New update',
      message: item.message || '',
      status: item.status || null,
      created_at: item.created_at || null,
    }));
  } catch {
    error.value = 'Notifications could not be loaded. Check your connection and try again.';
  } finally {
    isLoading.value = false;
  }
}

function notificationTone(notification: NotificationItem): string {
  const title = notification.title.toLowerCase();
  const status = notification.status?.toLowerCase();

  if (status === 'completed' || title.includes('complete')) {
return 'green';
}

  if (title.includes('overdue') || title.includes('reminder')) {
return 'amber';
}

  if (title.includes('import')) {
return 'blue';
}

  if (status === 'in_progress' || title.includes('upcoming')) {
return 'orange';
}

  if (title.includes('welcome')) {
return 'red';
}

  return 'violet';
}

function notificationIcon(notification: NotificationItem) {
  const title = notification.title.toLowerCase();
  const status = notification.status?.toLowerCase();

  if (status === 'completed' || title.includes('complete')) {
return CheckCircle2;
}

  if (title.includes('overdue') || title.includes('reminder')) {
return Bell;
}

  if (title.includes('import')) {
return Import;
}

  if (status === 'in_progress' || title.includes('upcoming')) {
return CalendarClock;
}

  if (title.includes('welcome')) {
return UserRound;
}

  return Info;
}

function formatRelativeTime(value: string | null): string {
  if (!value) {
return 'Just now';
}

  const timestamp = new Date(value).getTime();

  if (Number.isNaN(timestamp)) {
return 'Recently';
}

  const elapsedSeconds = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));

  if (elapsedSeconds < 60) {
return 'Just now';
}

  const elapsedMinutes = Math.floor(elapsedSeconds / 60);

  if (elapsedMinutes < 60) {
return `${elapsedMinutes}m ago`;
}

  const elapsedHours = Math.floor(elapsedMinutes / 60);

  if (elapsedHours < 24) {
return `${elapsedHours}h ago`;
}

  const elapsedDays = Math.floor(elapsedHours / 24);

  return `${elapsedDays}d ago`;
}

function handleNotificationBroadcast(): void {
  fetchNotifications();
}

onMounted(async () => {
  await fetchNotifications();

  if (authStore.user?.id && typeof Echo !== 'undefined') {
    notificationChannel = Echo.private(`user.${authStore.user.id}`).listen('.NotificationCreated', handleNotificationBroadcast);
  }
});

onBeforeUnmount(() => {
  if (authStore.user?.id && notificationChannel && typeof Echo !== 'undefined') {
    Echo.leave(`user.${authStore.user.id}`);
    notificationChannel = null;
  }
});
</script>
