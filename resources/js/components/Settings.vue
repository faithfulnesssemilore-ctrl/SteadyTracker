<template>
    <div class="dashboard-shell settings-shell">
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
                <div class="topbar-spacer"></div>

                <div class="topbar-actions">
                    <div class="avatar large">{{ userInitial }}</div>
                    <span class="home-user-name">{{ displayName }}</span>
                </div>
            </header>

            <section class="settings-content">
                <div class="settings-heading">
                    <h1>Settings</h1>
                    <p>Manage your account settings.</p>
                </div>

                <section
                    class="settings-card"
                    aria-labelledby="profile-heading"
                >
                    <div class="settings-card-heading">
                        <h2 id="profile-heading">Profile</h2>
                        <p>Update your name and email address.</p>
                    </div>
                    <form @submit.prevent="updateProfile">
                        <label>
                            <span>Name</span>
                            <input
                                v-model="profileName"
                                type="text"
                                required
                                maxlength="255"
                            />
                        </label>
                        <label>
                            <span>Email</span>
                            <input
                                v-model="profileEmail"
                                type="email"
                                required
                                maxlength="255"
                            />
                        </label>
                        <button
                            class="settings-primary"
                            type="submit"
                            :disabled="profileSaving"
                        >
                            {{ profileSaving ? 'Saving...' : 'Save profile' }}
                        </button>
                    </form>
                    <p v-if="profileMessage" class="settings-success">
                        {{ profileMessage }}
                    </p>
                    <p v-if="profileError" class="settings-error">
                        {{ profileError }}
                    </p>
                </section>

                <section
                    class="settings-card"
                    aria-labelledby="password-heading"
                >
                    <div class="settings-card-heading">
                        <h2 id="password-heading">Password</h2>
                        <p>Change the password used to sign in.</p>
                    </div>
                    <form @submit.prevent="updatePassword">
                        <label>
                            <span>Current password</span>
                            <input
                                v-model="currentPassword"
                                type="password"
                                required
                                autocomplete="current-password"
                            />
                        </label>
                        <label>
                            <span>New password</span>
                            <input
                                v-model="newPassword"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                        </label>
                        <label>
                            <span>Confirm new password</span>
                            <input
                                v-model="confirmPassword"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                        </label>
                        <button
                            class="settings-primary"
                            type="submit"
                            :disabled="passwordSaving"
                        >
                            {{
                                passwordSaving
                                    ? 'Updating...'
                                    : 'Update password'
                            }}
                        </button>
                    </form>
                    <p v-if="passwordMessage" class="settings-success">
                        {{ passwordMessage }}
                    </p>
                    <p v-if="passwordError" class="settings-error">
                        {{ passwordError }}
                    </p>
                </section>

                <button
                    class="logout-card"
                    :disabled="loggingOut"
                    @click="logout"
                >
                    <span class="logout-icon">⇥</span>
                    <span class="logout-copy">
                        <strong>{{
                            loggingOut ? 'Logging out...' : 'Log out'
                        }}</strong>
                        <small>Sign out of your account.</small>
                    </span>
                    <span class="logout-arrow">›</span>
                </button>

                <section
                    class="settings-card danger-card"
                    aria-labelledby="delete-heading"
                >
                    <div class="settings-card-heading">
                        <h2 id="delete-heading">Delete account</h2>
                        <p>Permanently delete your account and activities.</p>
                    </div>
                    <label v-if="authStore.user?.has_password">
                        <span>Confirm with your current password</span>
                        <input
                            v-model="deletePassword"
                            type="password"
                            required
                            autocomplete="current-password"
                        />
                    </label>
                    <button
                        class="settings-danger"
                        type="button"
                        :disabled="
                            deleting ||
                            (authStore.user?.has_password && !deletePassword)
                        "
                        @click="deleteAccount"
                    >
                        {{
                            deleting
                                ? 'Deleting...'
                                : 'Delete account permanently'
                        }}
                    </button>
                    <p v-if="deleteError" class="settings-error">
                        {{ deleteError }}
                    </p>
                </section>

                <p v-if="error" class="settings-error">{{ error }}</p>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import { CircleCheckBig, House, Import, Mail, Settings } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import api, { ensureCsrfCookie, getApiError } from '../services/api';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const loggingOut = ref(false);
const error = ref('');
const profileName = ref(
    authStore.user?.name || authStore.user?.user_name || '',
);
const profileEmail = ref(authStore.user?.email || '');
const profileSaving = ref(false);
const profileMessage = ref('');
const profileError = ref('');
const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const passwordSaving = ref(false);
const passwordMessage = ref('');
const passwordError = ref('');
const deletePassword = ref('');
const deleting = ref(false);
const deleteError = ref('');

watch(
    () => authStore.user,
    (user) => {
        profileName.value = user?.name || user?.user_name || '';
        profileEmail.value = user?.email || '';
    },
);
const displayName = computed(
    () => authStore.user?.name || authStore.user?.user_name || 'Your account',
);
const userInitial = computed(
    () => displayName.value.charAt(0).toUpperCase() || 'S',
);

function go(path: string): void {
    router.push(path);
}

async function updateProfile(): Promise<void> {
    profileSaving.value = true;
    profileMessage.value = '';
    profileError.value = '';

    try {
        await ensureCsrfCookie();
        await api.put('/user/profile-information', {
            name: profileName.value,
            email: profileEmail.value,
        });
        await authStore.fetchUser();
        profileMessage.value = 'Profile updated.';
    } catch (exception) {
        profileError.value = getApiError(
            exception,
            'Unable to update profile.',
        ).message;
    } finally {
        profileSaving.value = false;
    }
}

async function updatePassword(): Promise<void> {
    passwordSaving.value = true;
    passwordMessage.value = '';
    passwordError.value = '';

    try {
        await ensureCsrfCookie();
        await api.put('/user/password', {
            current_password: currentPassword.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        });
        currentPassword.value = '';
        newPassword.value = '';
        confirmPassword.value = '';
        passwordMessage.value = 'Password updated.';
    } catch (exception) {
        passwordError.value = getApiError(
            exception,
            'Unable to update password.',
        ).message;
    } finally {
        passwordSaving.value = false;
    }
}

async function deleteAccount(): Promise<void> {
    if (
        !window.confirm('Permanently delete your account and all activities?')
    ) {
        return;
    }

    deleting.value = true;
    deleteError.value = '';

    try {
        await ensureCsrfCookie();
        await api.delete('/api/user', {
            data: { current_password: deletePassword.value },
        });
        authStore.user = null;
        authStore.emailVerified = false;
        await router.push('/login');
    } catch (exception) {
        deleteError.value = getApiError(
            exception,
            'Unable to delete account.',
        ).message;
    } finally {
        deleting.value = false;
    }
}

async function logout() {
    loggingOut.value = true;
    error.value = '';

    try {
        await authStore.logout();
        await router.push('/login');
    } catch {
        error.value = 'Unable to log out right now. Please try again.';
    } finally {
        loggingOut.value = false;
    }
}
</script>
