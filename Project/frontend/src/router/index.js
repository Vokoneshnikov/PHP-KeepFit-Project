import { createRouter, createWebHistory } from 'vue-router'
import { authService } from '@/services/authService'

import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import DiaryView from '@/views/DiaryView.vue'
import ProfileView from '@/views/ProfileView.vue'
import ProfileEditView from '@/views/ProfileEditView.vue'
import RecalculateView from '@/views/RecalculateView.vue'
import FoodAddView from '@/views/FoodAddView.vue'
import FoodProductView from '@/views/FoodProductView.vue'
import MealEditView from '@/views/MealEditView.vue'
import StatisticsView from '@/views/StatisticsView.vue'

const routes = [
  {
    path: '/',
    redirect: '/diary',
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guestOnly: true },
  },
  {
    path: '/diary',
    name: 'diary',
    component: DiaryView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/edit',
    name: 'profile-edit',
    component: ProfileEditView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/recalculate',
    name: 'profile-recalculate',
    component: RecalculateView,
    meta: { requiresAuth: true },
  },
  {
    path: '/food/add',
    name: 'food-add',
    component: FoodAddView,
    meta: { requiresAuth: true },
  },
  {
    path: '/food/:id/add',
    name: 'food-product-add',
    component: FoodProductView,
    meta: { requiresAuth: true },
  },
  {
    path: '/meals/:id',
    name: 'meal-edit',
    component: MealEditView,
    meta: { requiresAuth: true },
  },
  {
    path: '/statistics',
    name: 'statistics',
    component: StatisticsView,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const isAuth = authService.isAuthenticated()

  if (to.meta.requiresAuth && !isAuth) {
    return '/login'
  }

  if (to.meta.guestOnly && isAuth) {
    return '/diary'
  }
})

export default router
