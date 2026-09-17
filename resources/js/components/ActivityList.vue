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
        :class="{ active: selectedId === activity.id }"
        @click="$emit('select', activity)"
      >
        <div class="activity-left">
          <div class="activity-icon" :class="activityCategoryClass(activity)">
            {{ activityIcon(activity) }}
          </div>

          <div class="activity-copy">
            <div class="activity-title-row">
              <h3>{{ activity.title }}</h3>
              <button class="row-menu" @click.stop>⋯</button>
            </div>
            <div class="meta-line">
              <span>{{ activity.category?.name || 'Personal' }}</span>
              <span>•</span>
              <span>{{ formatRange(activity) }}</span>
            </div>
          </div>
        </div>

        <div class="activity-right">
          <span class="pill priority-pill" :class="priorityClass(activity.priority)">
            {{ activity.priority || 'Medium' }}
          </span>
          <span class="pill status-pill" :class="statusClass(activity.activity_status)">
            {{ activity.activity_status || 'Pending' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useActivitiesStore } from '../stores/activities';

const props = defineProps({
  selectedId: { type: [Number, null], default: null },
});

const emit = defineEmits(['select']);
const store = useActivitiesStore();

const groups = computed(() => {
  const map = {
    Today: [],
    Upcoming: [],
    Later: [],
  };

  for (const activity of store.list) {
    const status = String(activity.activity_status ?? 'pending').toLowerCase();
    const due = activity.due_at ? new Date(activity.due_at) : null;

    if (status === 'completed') {
      map.Today.push(activity);
      continue;
    }

    if (!due) {
      map.Upcoming.push(activity);
      continue;
    }

    const diffDays = Math.ceil((due - new Date()) / 86400000);

    if (diffDays <= 1) {
      map.Today.push(activity);
    } else if (diffDays <= 7) {
      map.Upcoming.push(activity);
    } else {
      map.Later.push(activity);
    }
  }

  return Object.entries(map)
    .filter(([, items]) => items.length)
    .map(([title, items]) => ({ title, items }));
});

function formatRange(activity) {
  if (!activity.due_at) {
    return 'No date assigned';
  }

  const date = new Date(activity.due_at);
  return date.toLocaleTimeString([], {
    hour: 'numeric',
    minute: '2-digit',
  });
}

function statusClass(value) {
  const status = String(value ?? 'pending').toLowerCase();

  if (status.includes('complete')) return 'complete';
  if (status.includes('progress')) return 'in-progress';
  return 'pending';
}

function priorityClass(value) {
  const priority = String(value ?? 'medium').toLowerCase();

  if (priority === 'high') return 'high';
  if (priority === 'low') return 'low';
  return 'medium';
}

function activityCategoryClass(activity) {
  const category = (activity.category?.name || '').toLowerCase();

  if (category.includes('work')) return 'work';
  if (category.includes('health')) return 'health';
  if (category.includes('learning')) return 'learning';
  return 'personal';
}

function activityIcon(activity) {
  const category = (activity.category?.name || '').toLowerCase();

  if (category.includes('work')) return '✦';
  if (category.includes('health')) return '✚';
  if (category.includes('learning')) return '◫';
  return '☰';
}
</script>
