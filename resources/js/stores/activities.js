import { defineStore } from 'pinia'
import api from '../api/activities'

export const useActivitiesStore = defineStore('activities', {
  state: () => ({
    activities: {},   // normalized by id — single source of truth
    loading: false,
  }),
  getters: {
    list: (state) => Object.values(state.activities)
      .sort((a, b) => (a.due_at ?? '') > (b.due_at ?? '') ? 1 : -1),
  },
  actions: {
    setActivity(activity) {
      this.activities[activity.id] = activity
    },
    async fetchAll() {
      this.loading = true
      const { data } = await api.fetchAll()
      data.forEach(a => this.setActivity(a))
      this.loading = false
    },
    async create(payload) {
      const { data } = await api.create(payload)
      this.setActivity(data)
    },
    async start(id) {
      const { data } = await api.start(id)
      this.setActivity(data)
    },
    async complete(id) {
      const { data } = await api.complete(id)
      this.setActivity(data)
    },
    listenForUpdates(userId) {
      Echo.private(`user.${userId}`)
        .listen('.ActivityUpdated', (a) => this.setActivity(a))
        .listen('.ActivityCreated', (a) => this.setActivity(a))
    },
  },
})