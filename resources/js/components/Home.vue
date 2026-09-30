<template>
    <div class="dashboard-shell home-shell">
        <aside class="sidebar">
            <div class="brand-row">
                <div class="brand-mark">✓</div>
                <div class="brand-name">SteadyTracker</div>
            </div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <button
                    class="nav-item"
                    type="button"
                    @click="go('/dashboard')"
                >
                    <span class="nav-icon"><House :size="18" /></span>
                    <span>Home</span>
                </button>
                <button
                    class="nav-item active"
                    type="button"
                    aria-current="page"
                    @click="go('/inbox')"
                >
                    <span class="nav-icon"><Mail :size="18" /></span>
                    <span>Inbox</span>
                </button>
                <button
                    class="nav-item"
                    type="button"
                    @click="go('/activities')"
                >
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
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search..."
                    />
                </div>

                <div>
                    <div>
                        <span></span>
                        <span><strong></strong></span>
                    </div>

                    <div class="avatar large">{{ userInitial }}</div>
                    <span class="home-user-name">{{ displayName }}</span>
                </div>
            </header>

            <div class="home-content">
                <section class="home-heading">
                    <h1>
                        Hey yoo, {{ firstName }}
                        <span aria-hidden="true"></span>
                    </h1>
                    <p>Let get started today, consitentcy matters.</p>
                </section>

                <section class="stats-grid" aria-label="Activity summary">
                    <article class="stat-card stat-orange">
                        <div class="stat-icon">◉</div>
                        <strong>{{ todayItems.length }}</strong>
                        <span>Tasks today</span>
                    </article>
                    <article class="stat-card stat-purple">
                        <div class="stat-icon">▣</div>
                        <strong>{{ overdueCount }}</strong>
                        <span>Overdue</span>
                    </article>
                    <article class="stat-card stat-green">
                        <div class="stat-icon">▥</div>
                        <strong>{{ completedThisWeek }}</strong>
                        <span>Completed this week</span>
                    </article>
                </section>

                <div class="home-grid">
                    <section class="today-section">
                        <div class="section-heading">
                            <div>
                                <h2>
                                    Today <small>{{ todayLabel }}</small>
                                </h2>
                            </div>
                            <button
                                class="view-all-button"
                                @click="go('/activities')"
                            >
                                View all ›
                            </button>
                        </div>

                        <div class="today-list">
                            <div
                                v-if="!filteredToday.length"
                                class="home-empty"
                            >
                                <strong>No tasks for today</strong>
                                <span
                                    >Add a small step to keep your momentum
                                    going.</span
                                >
                            </div>
                            <button
                                v-for="activity in filteredToday"
                                :key="activity.id"
                                class="today-item"
                                @click="go('/activities')"
                            >
                                <span
                                    class="task-check"
                                    :class="{ complete: isCompleted(activity) }"
                                    >{{
                                        isCompleted(activity) ? '✓' : ''
                                    }}</span
                                >
                                <span class="task-title">{{
                                    activity.title
                                }}</span>
                                <span class="task-time">{{
                                    formatTime(activity.due_at)
                                }}</span>
                            </button>
                        </div>
                    </section>

                    <aside class="encouragement-card">
                        <div class="encouragement-art" aria-hidden="true">
                            ☼
                        </div>
                        <strong>You're doing great!</strong>
                        <p>Consistency builds the life you want.</p>
                        <button class="home-add-button" @click="openCreate">
                            + Add new task
                        </button>
                    </aside>
                </div>

                <section class="quick-actions">
                    <h2>Quick actions</h2>
                    <div class="quick-action-grid">
                        <button class="quick-action" @click="openCreate">
                            <span class="quick-icon orange">+</span
                            ><span>Add task</span>
                        </button>
                        <button class="quick-action" @click="go('/settings')">
                            <span class="quick-icon gray">⚙</span
                            ><span>Settings</span>
                        </button>
                    </div>
                </section>
            </div>
        </main>

        <div
            v-if="showCreate"
            class="home-modal-backdrop"
            @click.self="showCreate = false"
        >
            <form class="home-modal" @submit.prevent="createActivity">
                <div class="modal-heading">
                    <div>
                        <span class="eyebrow">New task</span>
                        <h2>Add a task</h2>
                    </div>
                    <button
                        type="button"
                        class="modal-close"
                        aria-label="Close"
                        @click="showCreate = false"
                    >
                        ×
                    </button>
                </div>
                <label
                    >Title<input
                        v-model="newActivity.title"
                        required
                        maxlength="255"
                        placeholder="What needs your attention?"
                /></label>
                <label
                    >Description<textarea
                        v-model="newActivity.description"
                        rows="4"
                        maxlength="5000"
                        placeholder="Add context, notes, or the next step..."
                    ></textarea>
                </label>
                <label
                    >Due date<input
                        v-model="newActivity.due_at"
                        type="datetime-local"
                /></label>
                <label
                    >Priority<select v-model="newActivity.priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select></label
                >
                <button
                    class="home-add-button modal-submit"
                    :disabled="creating"
                >
                    {{ creating ? 'Adding...' : 'Add task' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { CircleCheckBig, House, Import, Mail, Settings } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useActivitiesStore } from '../stores/activities';
import { useAuthStore } from '../stores/auth';

interface Activity {
    id: number;
    title: string;
    description?: string | null;
    activity_status?: string;
    due_at?: string | null;
    completed_at?: string | null;
}

interface NewActivity {
    title: string;
    description: string;
    due_at: string;
    priority: string;
}

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const store = useActivitiesStore();
const search = ref('');
const showCreate = ref(false);
const creating = ref(false);
const now = ref(new Date());
let clock: number | undefined;
const newActivity = ref<NewActivity>({
    title: '',
    description: '',
    due_at: '',
    priority: 'medium',
});

onMounted(async () => {
    clock = window.setInterval(() => {
        now.value = new Date();
    }, 30000);

    if (route.query.create === '1') {
        showCreate.value = true;
        router.replace({
            path: route.path,
            query: { ...route.query, create: undefined },
        });
    }

    await store.fetchAll();

    if (authStore.user?.id && typeof Echo !== 'undefined') {
        store.listenForUpdates(authStore.user.id);
    }
});

onBeforeUnmount(() => {
    if (clock) {
        window.clearInterval(clock);
    }

    if (authStore.user?.id && typeof Echo !== 'undefined') {
        store.stopListeningForUpdates(authStore.user.id);
    }
});

const displayName = computed(
    () => authStore.user?.name || authStore.user?.user_name || 'Your account',
);
const firstName = computed(
    () =>
        authStore.user?.name?.split(' ')[0] ||
        authStore.user?.user_name?.split(' ')[0] ||
        'there',
);
const userInitial = computed(
    () => firstName.value.charAt(0).toUpperCase() || 'S',
);
const todayLabel = computed(() =>
    new Date().toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }),
);
const todayItems = computed(() =>
    (store.list as Activity[]).filter((activity) => {
        if (isCompleted(activity)) {
            return false;
        }

        if (!activity.due_at) {
            return true;
        }

        const date = new Date(activity.due_at);

        return date.toDateString() === now.value.toDateString();
    }),
);
const filteredToday = computed(() =>
    todayItems.value.filter((activity: Activity) =>
        activity.title.toLowerCase().includes(search.value.toLowerCase()),
    ),
);
const overdueCount = computed(
    () =>
        (store.list as Activity[]).filter(
            (activity) =>
                activity.due_at &&
                new Date(activity.due_at) < now.value &&
                !isCompleted(activity),
        ).length,
);
const completedThisWeek = computed(
    () =>
        (store.list as Activity[]).filter(
            (activity) =>
                isCompleted(activity) &&
                (!activity.completed_at ||
                    new Date(activity.completed_at) >= weekStart()),
        ).length,
);
function weekStart() {
    const date = new Date();
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() - date.getDay());

    return date;
}

function isCompleted(activity: Activity): boolean {
    return String(activity.activity_status || '').toLowerCase() === 'completed';
}

function formatTime(value?: string | null): string {
    if (!value) {
        return 'Anytime';
    }

    return new Date(value).toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function go(path: string): void {
    router.push(path);
}

function openCreate() {
    showCreate.value = true;
}

async function createActivity() {
    creating.value = true;

    try {
        await store.create({
            ...newActivity.value,
            due_at: newActivity.value.due_at || null,
        });
        newActivity.value = {
            title: '',
            description: '',
            due_at: '',
            priority: 'medium',
        };
        showCreate.value = false;
    } finally {
        creating.value = false;
    }
}
</script>
