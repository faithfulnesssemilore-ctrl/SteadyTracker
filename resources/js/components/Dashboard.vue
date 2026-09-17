<template>
  <div class="dashboard-shell">
    <aside class="sidebar">
      <div class="brand-row">
        <div class="brand-mark">✓</div>
        <div class="brand-name">SteadyTracker</div>
      </div>

      <nav class="sidebar-nav">
        <button class="nav-item active">
          <span class="nav-icon">⌂</span>
          <span>Home</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">✓</span>
          <span>My Activities</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">◫</span>
          <span>Calendar</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">▣</span>
          <span>Analytics</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">◎</span>
          <span>Goals</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">◍</span>
          <span>Learning</span>
        </button>
        <button class="nav-item">
          <span class="nav-icon">⚙</span>
          <span>Settings</span>
        </button>
      </nav>

      <div class="mini-card">
        <div class="mini-card-icon">✦</div>
        <p>Small steps</p>
        <small>today, big results tomorrow.</small>
      </div>

      <div class="profile-card">
        <div class="avatar small">S</div>
        <span>Semilore S.</span>
      </div>
    </aside>

    <main class="dashboard-main">
      <header class="topbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input type="text" placeholder="Search activities..." />
        </div>

        <div class="topbar-actions">
          <div class="streak-pill">
            <span class="streak-count">12 day streak</span>
            <span class="streak-meta">Level 4 • 660 / 1000 XP</span>
          </div>
          <button class="bell-button">◌</button>
          <div class="avatar large">S</div>
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
              <button class="tab active">Today</button>
              <button class="tab">Upcoming</button>
              <button class="tab">Completed</button>
              <button class="tab">All</button>
            </div>

            <div class="filter-row">
              <button class="filter-chip active">All</button>
              <button class="filter-chip">Personal</button>
              <button class="filter-chip">Learning</button>
              <button class="filter-chip">Health</button>
              <button class="filter-chip">Work</button>
            </div>

            <button class="add-button">+ New Activity</button>
          </div>

          <ActivityList :selected-id="selectedActivityId" @select="selectedActivityId = $event.id" />
        </section>

        <aside class="detail-panel">
          <div class="detail-actions">
            <button class="secondary-ghost">✓ Mark complete</button>
            <button class="secondary-ghost">⇪ Share</button>
            <button class="icon-button">✦</button>
            <button class="icon-button">⋯</button>
            <button class="icon-button close">×</button>
          </div>

          <div v-if="selectedActivity" class="detail-card">
            <div class="detail-header">
              <div class="detail-icon">◫</div>
              <h2>{{ selectedActivity.title }}</h2>
              <div class="detail-context">
                <span class="tag subtle">{{ selectedActivity.category?.name ?? 'Learning' }}</span>
                <button class="more-menu">⋯</button>
              </div>
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

            <div class="detail-line">
              <span class="line-label">Project</span>
              <span class="line-value soft">{{ selectedActivity.category?.name ?? 'Personal' }}</span>
            </div>

            <div class="detail-section">
              <h3>Description</h3>
              <p>{{ selectedActivity.description || 'This is a personal study session to learn and understand system design concepts. Focus on the basics, real-world examples, and practice problems.' }}</p>
            </div>

            <div class="detail-section comments">
              <h3>Comments</h3>
              <div class="comment-input-row">
                <div class="avatar small">S</div>
                <input type="text" placeholder="Add a comment..." />
              </div>

              <div class="comment-item">
                <div class="avatar small alt">S</div>
                <div class="comment-content">
                  <div class="comment-head">
                    <strong>Semilore S.</strong>
                    <span>2h ago</span>
                  </div>
                  <p>Let’s do this! <span class="emoji">🔥</span></p>
                </div>
              </div>
            </div>

            <div class="streak-banner">
              <span class="leaf">✦</span>
              <div>
                <strong>Keep going!</strong>
                <p>You’re on a 12 day streak.</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useActivitiesStore } from '../stores/activities';
import ActivityList from './ActivityList.vue';

const store = useActivitiesStore();
const selectedActivityId = ref(null);

onMounted(() => store.fetchAll());

const selectedActivity = computed(() => {
  return store.list.find((activity) => activity.id === selectedActivityId.value) ?? store.list[0] ?? null;
});

watch(
  () => store.list,
  (items) => {
    if (!selectedActivityId.value && items.length) {
      selectedActivityId.value = items[0].id;
    }
  },
  { immediate: true },
);

function formatDate(value) {
  if (!value) return 'May 25, 2025';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'May 25, 2025';

  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}
</script>
