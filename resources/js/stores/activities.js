import { defineStore } from 'pinia';
import api from '../api/activities';

let activityChannel = null;

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        activities: {}, // normalized by id — single source of truth
        loading: false,
    }),
    getters: {
        list: (state) =>
            Object.values(state.activities).sort((a, b) =>
                (a.due_at ?? '') > (b.due_at ?? '') ? 1 : -1,
            ),
    },
    actions: {
        setActivity(activity) {
            this.activities[activity.id] = activity;
        },
        async fetchAll() {
            this.loading = true;

            try {
                const { data } = await api.fetchAll();
                const activities = Array.isArray(data)
                    ? data
                    : (data.data ?? []);

                activities.forEach((a) => this.setActivity(a));
            } finally {
                this.loading = false;
            }
        },
        async create(payload) {
            const { data } = await api.create(payload);
            this.setActivity(data);
        },
        async update(id, payload) {
            const { data } = await api.update(id, payload);
            this.setActivity(data);
        },
        async remove(id) {
            await api.destroy(id);
            delete this.activities[id];
        },
        async start(id) {
            const { data } = await api.start(id);
            this.setActivity(data);
        },
        async complete(id) {
            const { data } = await api.complete(id);
            this.setActivity(data);
        },
        async importCsv(file) {
            const { data } = await api.importCsv(file);

            if (data?.imported > 0) {
                await this.fetchAll();
            }

            return data;
        },
        listenForUpdates(userId) {
            this.stopListeningForUpdates(userId);
            activityChannel = Echo.private(`user.${userId}`)
                .listen('.ActivityUpdated', (a) => this.setActivity(a))
                .listen('.ActivityCreated', (a) => this.setActivity(a))
                .listen(
                    '.ActivityDeleted',
                    (a) => delete this.activities[a.id],
                );
        },
        stopListeningForUpdates(userId) {
            if (activityChannel) {
                Echo.leave(`user.${userId}`);
                activityChannel = null;
            }
        },
    },
});
