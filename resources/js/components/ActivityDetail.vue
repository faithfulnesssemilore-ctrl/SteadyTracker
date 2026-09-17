<template>
  <div class="detail-panel">
    <button @click="$emit('close')">×</button>
    <h2>{{ activity.title }}</h2>
    <p>{{ activity.description }}</p>

    <div class="field">
      <label>Due date</label>
      <span>{{ activity.due_at ?? '—' }}</span>
    </div>

    <div class="field">
      <label>Status</label>
      <select :value="activity.activity_status" @change="onStatusChange">
        <option value="pending">Pending</option>
        <option value="in_progress">In progress</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div class="field">
      <label>Priority</label>
      <span>{{ activity.priority }}</span>
    </div>

    <div class="field">
      <label>Category</label>
      <span>{{ activity.category?.name ?? 'Uncategorized' }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useActivitiesStore } from '../stores/activities'

interface Activity {
  id: number
  title: string
  description?: string | null
  due_at?: string | null
  activity_status: string
  priority: string
  category?: { name: string } | null
}

const props = defineProps<{ activity: Activity }>()
const store = useActivitiesStore()

function onStatusChange(e: Event) {
  const target = e.target as HTMLSelectElement
  const value = target.value

  if (value === 'in_progress') {
store.start(props.activity.id)
}

  if (value === 'completed') {
store.complete(props.activity.id)
}
}
</script>