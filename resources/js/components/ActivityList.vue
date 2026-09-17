<template>
  <div class="activity-list">
    <div
      v-for="activity in store.list"
      :key="activity.id"
      class="activity-row"
      @click="selected = activity"
    >
      <input
        type="checkbox"
        :checked="activity.activity_status === 'completed'"
        @click.stop="onToggle(activity)"
      />
      <span :class="{ done: activity.activity_status === 'completed' }">
        {{ activity.title }}
      </span>
      <span v-if="activity.is_habit && activity.streak > 0" class="streak">
        🔥 {{ activity.streak }}
      </span>
      <span class="category" v-if="activity.category">{{ activity.category.name }}</span>
      <span class="due" v-if="activity.due_at">{{ formatDue(activity.due_at) }}</span>
      <span class="priority" :class="activity.priority">{{ activity.priority }}</span>
    </div>

    <ActivityDetail
      v-if="selected"
      :activity="selected"
      @close="selected = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useActivitiesStore } from '../stores/activities'
import ActivityDetail from './ActivityDetail.vue'

const store = useActivitiesStore()

interface Activity {
  id: number
  activity_status: string
  title: string
  due_at?: string | null
  category?: { name: string } | null
  priority: string
  is_habit?: boolean
  streak?: number
}

const selected = ref<Activity | null>(null)

onMounted(() => store.fetchAll())

function onToggle(activity: Activity) {
  if (activity.activity_status !== 'completed') {
    store.complete(activity.id)
  }
}

function formatDue(due: string) {
  return new Date(due).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>