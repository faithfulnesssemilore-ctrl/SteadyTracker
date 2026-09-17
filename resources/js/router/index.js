import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: () => import('../components/Login.vue'), meta: { guestOnly: true } },
  { path: '/register', component: () => import('../components/Register.vue'), meta: { guestOnly: true } },
  { path: '/verify-email', component: () => import('../components/VerifyEmail.vue'), meta: { requiresAuth: true } },
  { path: '/forgot-password', component: () => import('../components/ForgotPassword.vue'), meta: { guestOnly: true } },
{ path: '/reset-password', component: () => import('../components/ResetPassword.vue'), meta: { guestOnly: true } },
  {
  
    path: '/dashboard',
    component: () => import('../components/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      requiresVerified: true,
    },
  },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (!auth.user) {
    await auth.fetchUser(); // checks the session cookie once per hard load
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return '/login';
  }

  if (to.meta.requiresVerified && !auth.emailVerified) {
    return '/verify-email';
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.emailVerified ? '/dashboard' : '/verify-email';
  }
});

export default router;