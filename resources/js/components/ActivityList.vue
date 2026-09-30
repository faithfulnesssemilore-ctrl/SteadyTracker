<template>
    <div class="activity-groups">
        <div v-for="group in groups" :key="group.title" class="activity-group">
            <div class="group-header">
                <h2>{{ group.title }}</h2>
                <span>{{ group.items.length }}</span>
            </div>

            <div
                v-for="activity in group.items"
                :key="activity.id"
                class="activity-row"
                :class="{
                    active: selectedId === activity.id,
                    overdue: isOverdue(activity),
                }"
                @click="$emit('select', activity)"
            >
                <div class="activity-left">
                    <div class="activity-icon">✓</div>

                    <div class="activity-copy">
                        <div class="activity-title-row">
                            <h3>{{ activity.title }}</h3>
                        </div>
                        <div class="meta-line">
                            <span
                                :class="{ 'overdue-text': isOverdue(activity) }"
                                >{{ formatRange(activity) }}</span
                            >
                        </div>
                    </div>
                </div>

                <div class="activity-right">
                    <span
                        class="pill priority-pill"
                        :class="priorityClass(activity.priority)"
                    >
                        {{ activity.priority || 'Medium' }}
                    </span>
                    <span
                        v-if="isOverdue(activity)"
                        class="pill status-pill overdue-pill"
                    >
                        Overdue
                    </span>
                    <span
                        v-else
                        class="pill status-pill"
                        :class="statusClass(activity.activity_status)"
                    >
                        {{ activity.activity_status || 'Pending' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useActivitiesStore } from '../stores/activities';

interface Activity {
    id: number;
    title: string;
    description?: string | null;
    due_at?: string | null;
    activity_status?: string;
    priority?: string;
}

type GroupTitle = 'Today' | 'Upcoming' | 'Later' | 'Overdue' | 'Completed';

interface ActivityGroup {
    title: GroupTitle;
    items: Activity[];
}

const props = withDefaults(
    defineProps<{
        selectedId: number | null;
        view: 'today' | 'overdue' | 'completed';
        search: string;
    }>(),
    {
        selectedId: null,
        view: 'today',
        search: '',
    },
);

defineEmits<{
    select: [activity: Activity];
}>();
const store = useActivitiesStore();
const now = ref(new Date());
let clock: number | undefined;
const visibleActivities = computed<Activity[]>(() => {
    const query = props.search.trim().toLowerCase();
    const activities = store.list as Activity[];

    if (!query) {
        return activities;
    }

    return activities.filter((activity) =>
        `${activity.title} ${activity.description ?? ''}`
            .toLowerCase()
            .includes(query),
    );
});

onMounted(() => {
    clock = window.setInterval(() => {
        now.value = new Date();
    }, 30000);
});

onBeforeUnmount(() => {
    if (clock) {
        window.clearInterval(clock);
    }
});

const groups = computed<ActivityGroup[]>(() => {
    const map: Record<GroupTitle, Activity[]> = {
        Today: [],
        Upcoming: [],
        Later: [],
        Overdue: [],
        Completed: [],
    };

    for (const activity of visibleActivities.value) {
        const status = String(
            activity.activity_status ?? 'pending',
        ).toLowerCase();
        const due = activity.due_at ? new Date(activity.due_at) : null;
        const isCompleted = status === 'completed';
        const isActivityOverdue =
            due !== null && due < now.value && !isCompleted;

        if (props.view === 'completed') {
            if (isCompleted) {
                map.Completed.push(activity);
            }

            continue;
        }

        if (props.view === 'overdue') {
            if (isActivityOverdue) {
                map.Overdue.push(activity);
            }

            continue;
        }

        if (isCompleted || isActivityOverdue) {
            continue;
        }

        if (!due) {
            map.Upcoming.push(activity);
            continue;
        }

        const diffDays = Math.ceil((due.getTime() - Date.now()) / 86400000);

        if (diffDays <= 1) {
            map.Today.push(activity);
        } else if (diffDays <= 7) {
            map.Upcoming.push(activity);
        } else {
            map.Later.push(activity);
        }
    }

    const allGroups = Object.entries(map) as [GroupTitle, Activity[]][];
    const visibleGroups =
        props.view === 'today'
            ? allGroups.filter(([title]) =>
                  ['Today', 'Upcoming', 'Later'].includes(title),
              )
            : allGroups;

    return visibleGroups
        .filter(([, items]) => items.length)
        .map(([title, items]) => ({ title, items }));
});

function formatRange(activity: Activity): string {
    if (!activity.due_at) {
        return 'No date assigned';
    }

    const date = new Date(activity.due_at);

    return date.toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
    });
}

function isOverdue(activity: Activity): boolean {
    if (!activity.due_at) {
        return false;
    }

    return (
        new Date(activity.due_at) < now.value &&
        String(activity.activity_status ?? '').toLowerCase() !== 'completed'
    );
}

function statusClass(value?: string): string {
    const status = String(value ?? 'pending').toLowerCase();

    if (status.includes('complete')) {
        return 'complete';
    }

    if (status.includes('progress')) {
        return 'in-progress';
    }

    return 'pending';
}

function priorityClass(value?: string): string {
    const priority = String(value ?? 'medium').toLowerCase();

    if (priority === 'high') {
        return 'high';
    }

    if (priority === 'low') {
        return 'low';
    }

    return 'medium';
}
</script>
