<template>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<main class="dashboard-page">
		<h1>Welcome to SteadyTracker</h1>
		<form @submit.prevent="submitActivity">
			<input v-model="form.title" type="text" placeholder="Activity title" required />
			<textarea v-model="form.description" placeholder="Description"></textarea>
			<select v-model="form.priority">
				<option value="low">Low priority</option>
				<option value="medium">Medium priority</option>
				<option value="high">High priority</option>
			</select>
			<input v-model="form.due_at" type="datetime-local" />
			<label>
				<input v-model="form.is_habit" type="checkbox" />
				Track as a daily habit (streak)
			</label>
			<button type="submit">Add activity</button>
		</form>
		<ActivityList />
		<button type="button" @click="handleLogout">Log out</button>
	</main>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useActivitiesStore } from '../stores/activities';
import { useAuthStore } from '../stores/auth';
import ActivityList from './ActivityList.vue';

const authStore = useAuthStore();
const router = useRouter();
const activitiesStore = useActivitiesStore();
const form = reactive({
	title: '',
	description: '',
	priority: 'medium',
	due_at: '',
	is_habit: false,
});

async function submitActivity() {
	await activitiesStore.create({
		title: form.title,
		description: form.description,
		priority: form.priority,
		due_at: form.due_at || null,
		is_habit: form.is_habit,
	});

	form.title = '';
	form.description = '';
	form.priority = 'medium';
	form.due_at = '';
	form.is_habit = false;
}

async function handleLogout() {
	try {
		await authStore.logout();
		router.push('/login');
	} catch (error) {
		authStore.error = error instanceof Error ? error.message : 'Unable to log out. Please try again.';
	}
}
</script>
