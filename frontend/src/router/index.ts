import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: {
      title: 'Главная'
    }
  },
  {
    path: '/proposals',
    name: 'Proposals',
    component: () => import('@/views/ProposalsList.vue'),
    meta: {
      title: 'Обращения'
    }
  },
  {
    path: '/proposals/create',
    name: 'CreateProposal',
    component: () => import('@/views/CreateProposal.vue'),
    meta: {
      title: 'Создать обращение'
    }
  },
  {
    path: '/proposals/:id',
    name: 'ProposalDetail',
    component: () => import('@/views/ProposalDetail.vue'),
    meta: {
      title: 'Детали обращения'
    }
  },
  {
    path: '/analytics',
    name: 'Analytics',
    component: () => import('@/views/Analytics.vue'),
    meta: {
      title: 'Аналитика'
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue'),
    meta: {
      title: 'Страница не найдена'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Set page title
router.beforeEach((to, from, next) => {
  if (to.meta?.title) {
    document.title = `${to.meta.title} - Система управления обращениями`
  }
  next()
})

export default router

