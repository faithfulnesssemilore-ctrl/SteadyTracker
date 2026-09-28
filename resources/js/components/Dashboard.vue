<template>
  <div class="dashboard-shell">
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
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input v-model="search" type="search" placeholder="Search..." />
        </div>

        <div class="topbar-actions">
<div class="avatar large">{{ userInitial }}</div>
 <span class="home-user-name">{{ displayName }}</span>

        </div>
      </header>

      <div class="dashboard-content">
        <section class="list-panel">
          <div class="page-header-row">
            <div>
              <h1>My Activities</h1>
              <p>Stay consistent. Build the life you want.</p>
            </div>
          </div>

          <div class="toolbar">
            <div class="tab-list">
              <button class="tab" :class="{ active: currentView === 'today' }" @click="currentView = 'today'">Today</button>
              <button class="tab" :class="{ active: currentView === 'overdue' }" @click="currentView = 'overdue'">Overdue</button>
              <button class="tab" :class="{ active: currentView === 'completed' }" @click="currentView = 'completed'">Completed</button>
         
            </div>

            <button class="add-button" @click="$router.push({ path: '/dashboard', query: { create: '1' } })">+ New Activity</button>
          </div>

          <ActivityList :selected-id="selectedActivityId" :view="currentView" :search="search" @select="selectedActivityId = $event.id" />
        </section>

        <aside class="detail-panel">
          <div class="detail-actions">
            <button
              v-if="selectedActivity && selectedActivity.activity_status !== 'completed'"
              class="secondary-ghost"
              :disabled="isMutating"
              @click="completeSelected"
            > <CircleCheckBig /> Mark complete</button>
            <button
              v-if="selectedActivity && selectedActivity.activity_status === 'pending'"
              class="secondary-ghost"
              :disabled="isMutating"
              @click="startSelected"
              > <Play />Start</button>
            <button v-if="selectedActivity" class="secondary-ghost" @click="beginEditing">✎ Edit</button>
            
          </div>

          <div v-if="selectedActivity && editing" class="detail-card">
            <div class="detail-header">
              <h2>Edit activity</h2>
            </div>
            <form class="detail-section" @submit.prevent="saveEdit">
              <label>Title<input v-model="editForm.title" required maxlength="255" /></label>
              <label>Description<textarea v-model="editForm.description" rows="5" maxlength="5000"></textarea></label>
              <label>Due date<input v-model="editForm.due_at" type="datetime-local" /></label>
              <label>Priority
                <select v-model="editForm.priority">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </label>
              <div class="detail-actions">
                <button class="secondary-ghost" type="submit" :disabled="isMutating">{{ isMutating ? 'Saving...' : 'Save changes' }}</button>
                <button class="secondary-ghost" type="button" @click="editing = false">Cancel</button>
              </div>
            </form>
          </div>
          <div v-else-if="selectedActivity" class="detail-card">
            <div class="detail-header">
              <div class="detail-icon">  <CircleUserRound /></div>
              <h2>{{ selectedActivity.title }}</h2>
            </div>

            <div class="detail-meta-grid">
              <div class="meta-item">
                <label>Priority</label>
                <span class="pill priority-pill medium">{{ selectedActivity.priority || 'Medium' }}</span>
              </div>
              <div class="meta-item">
                <label>Status</label>
                <span class="pill status-pill pending">{{ selectedActivity.activity_status || 'Pending' }}</span>
              </div>
            </div>

            <div class="detail-line">
              <span class="line-label">Due date</span>
              <span class="line-value">{{ formatDate(selectedActivity.due_at) }}</span>
            </div>

            <div class="detail-section">
              <h3>Description</h3>
              <p>{{ selectedActivity.description || 'No description added yet.' }}</p>
            </div>

          </div>
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { House, CircleCheckBig, Mail, Settings, Import, Play, CircleUserRound } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useActivitiesStore } from '../stores/activities';
import { useAuthStore } from '../stores/auth';
import ActivityList from './ActivityList.vue';

interface Activity {
  id: number;
  title: string;
  description?: string | null;
  activity_status?: string;
  priority?: string;
  due_at?: string | null;
}

interface ActivityEditForm {
  title: string;
  description: string;
  due_at: string;
  priority: string;
}

const router = useRouter();
const store = useActivitiesStore();
const authStore = useAuthStore();
const selectedActivityId = ref<number | null>(null);
const isMutating = ref(false);
const editing = ref(false);
const search = ref('');
const currentView = ref<'today' | 'overdue' | 'completed'>('today');
const displayName = computed(() => authStore.user?.name || authStore.user?.user_name || 'Your account');
const userInitial = computed(() => displayName.value.charAt(0).toUpperCase());

onMounted(async () => {
  await store.fetchAll();

  if (authStore.user?.id && typeof Echo !== 'undefined') {
store.listenForUpdates(authStore.user.id);
}
});

onBeforeUnmount(() => {
  if (authStore.user?.id && typeof Echo !== 'undefined') {
store.stopListeningForUpdates(authStore.user.id);
}
});





const selectedActivity = computed<Activity | null>(() => {
  const query = search.value.trim().toLowerCase();
  const activities = store.list as Activity[];
  const matches = query
    ? activities.filter((activity) => `${activity.title} ${activity.description ?? ''}`.toLowerCase().includes(query))
    : activities;

  return matches.find((activity) => activity.id === selectedActivityId.value) ?? matches[0] ?? null;
});
const editForm = ref<ActivityEditForm>({ title: '', description: '', due_at: '', priority: 'medium' });

watch(
  () => store.list as Activity[],
  (items: Activity[]) => {
    if (!selectedActivityId.value && items.length) {
      selectedActivityId.value = items[0].id;
    }

    if (editing.value && selectedActivity.value) {
      editForm.value = toEditForm(selectedActivity.value);
    }
  },
  { immediate: true },
);

function formatDate(value?: string | null): string {
  if (!value) {
return 'No due date';
}

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
return 'No due date';
}

  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

async function startSelected() {
  if (!selectedActivity.value) {
return;
}

  isMutating.value = true;

  try {
    await store.start(selectedActivity.value.id);
  } finally {
    isMutating.value = false;
  }
}

async function completeSelected() {
  if (!selectedActivity.value) {
return;
}

  isMutating.value = true;

  try {
    await store.complete(selectedActivity.value.id);
  } finally {
    isMutating.value = false;
  }
}

function toEditForm(activity: Activity): ActivityEditForm {
  return {
    title: activity.title || '',
    description: activity.description || '',
    due_at: activity.due_at ? toDateTimeLocal(activity.due_at) : '',
    priority: String(activity.priority || 'medium').toLowerCase(),
  };
}

function toDateTimeLocal(value: string): string {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
return '';
}

  const pad = (part: number) => String(part).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function beginEditing() {
  if (!selectedActivity.value) {
return;
}

  editForm.value = toEditForm(selectedActivity.value);
  editing.value = true;
}

function go(path: string): void {
  router.push(path);
}

async function saveEdit() {
  if (!selectedActivity.value) {
return;
}

  isMutating.value = true;

  try {
    await store.update(selectedActivity.value.id, {
      ...editForm.value,
      description: editForm.value.description || null,
      due_at: editForm.value.due_at || null,
    });
    editing.value = false;
  } finally {
    isMutating.value = false;
  }
}

</script>
