import { createRouter, createWebHistory } from 'vue-router'
import { authGuard } from '@auth0/auth0-vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: () => import('../components/PendingQueue.vue'),
      beforeEnter: authGuard,
    },
    {
      path: '/callback',
      component: () => import('../pages/Callback.vue'),
    },
  ],
})

export default router
