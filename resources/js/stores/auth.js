import { defineStore } from 'pinia';
import api, { ensureCsrfCookie, getApiError } from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    /** @type {{ id?: number, name?: string, email?: string, email_verified_at?: string | null } | null} */
    user: null,
    emailVerified: false,
    loading: false,
    /** @type {string | null} */
    error: null,
    /** @type {Record<string, string[]>} */
    fieldErrors: {},
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
  },

  actions: {
    async register({ userName, email, password, passwordConfirmation }) {
      this.loading = true;
      this.error = null;
      this.fieldErrors = {};

      try {
        await ensureCsrfCookie();
        await api.post('/register', {
          name: userName,
          email,
          password,
          password_confirmation: passwordConfirmation,
        });
        await this.fetchUser();

        return true;
      } catch (e) {
        const failure = getApiError(e, 'Registration failed');
        this.error = failure.message;
        this.fieldErrors = failure.fieldErrors;

        return false;
      } finally {
        this.loading = false;
      }
    },

    async login({ email, password, remember }) {
      this.loading = true;
      this.error = null;
      this.fieldErrors = {};

      try {
        await ensureCsrfCookie();
        await api.post('/login', { email, password, remember });
        await this.fetchUser();

        return true;
      } catch (e) {
        this.error = getApiError(e, 'Invalid credentials').message;

        return false;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser() {
      try {
        const { data } = await api.get('/api/user');
        this.user = data;
        this.emailVerified = Boolean(data.email_verified_at);
      } catch {
        this.user = null;
        this.emailVerified = false;
      }
    },

    async logout() {
      await ensureCsrfCookie();
      await api.post('/logout');
      this.user = null;
      this.emailVerified = false;
      this.error = null;
    },
  },
});